/**
 * Drawing lifecycle of the charts on a page.
 *
 * - A chart is drawn only once its box has a size: charts in hidden tabs, accordions and popups
 *   wait until they are shown.
 * - Charts are drawn one per task, so a page with many charts doesn't freeze while loading.
 *   Charts in or near the viewport go first; every other chart is still drawn right after,
 *   without scrolling (print, PDF tools and anchor links need them).
 * - One ResizeObserver keeps every chart fitted to its box, and switches the compact (phone)
 *   layout on and off.
 * - A MutationObserver finds charts added later (AJAX, popups, infinite scroll, carousel copies)
 *   and lets go of removed ones.
 * - A chart that fails to draw keeps its data table and doesn't stop the others.
 *
 * Chart configs are JSON in the page and are treated as untrusted input.
 */

/**
 * Internal dependencies
 */
import { buildOption, isCompact } from '../charts/build-option';
import { hideDrawing, withMotionPreference } from '../charts/display';
import { disableDownload, enableDownload } from './download';
import { hideTable, showTable } from './table';

const FIGURE = '.easy-charts-chart';
const BOX = '.easy-charts-chart__canvas';
const CONFIG = '.easy-charts-chart__config';
const ECHARTS_ID = '_echarts_instance_';

/**
 * @typedef {Object} ChartState
 * @property {HTMLElement} figure  Chart figure.
 * @property {Object|null} config  Parsed config, or null if it is missing or malformed.
 * @property {Object|null} chart   ECharts instance once drawn.
 * @property {Object|null} option  Option of the current drawing.
 * @property {boolean}     compact Whether the current drawing uses the compact layout.
 * @property {boolean}     near    In or near the viewport.
 * @property {string}      size    Box size of the current drawing, `width x height`.
 */

/** @type {Map<HTMLElement, ChartState>} Watched chart boxes. */
const charts = new Map();

/** @type {HTMLElement[]} Boxes waiting to be drawn. */
const queue = [];

let echarts = null;
let started = false;
let taskPending = false;
let resizeObserver = null;
let viewportObserver = null;

/** @type {Set<(chart: Object, box: HTMLElement) => void>} Called after a chart is drawn or resized, e.g. for print. */
const afterDrawHooks = new Set();

/**
 * Run something after a chart is drawn or resized.
 *
 * @param {(chart: Object, box: HTMLElement) => void} hook Called with the chart and its box.
 */
export function onAfterDraw( hook ) {
	afterDrawHooks.add( hook );
}

/**
 * Read a chart's JSON config: a plain object, or null.
 *
 * @param {HTMLElement} figure Chart figure.
 * @return {Object|null} Config.
 */
function readConfig( figure ) {
	const script = figure.querySelector( `:scope > ${ CONFIG }` );
	if ( ! script ) {
		return null;
	}
	try {
		const config = JSON.parse( script.textContent );
		return config && typeof config === 'object' && ! Array.isArray( config )
			? config
			: null;
	} catch {
		return null;
	}
}

/**
 * Whether a box can be drawn into now.
 *
 * @param {HTMLElement} box Chart box.
 * @return {boolean} True when it is on the page and has a size.
 */
function hasSize( box ) {
	return box.isConnected && box.clientWidth > 0 && box.clientHeight > 0;
}

/**
 * A box's size, to tell whether a drawing still fits it.
 *
 * @param {HTMLElement} box Chart box.
 * @return {string} `width x height` in px.
 */
function sizeOf( box ) {
	return `${ box.clientWidth }x${ box.clientHeight }`;
}

/**
 * Report a chart that could not be drawn, and show its data table instead.
 *
 * @param {HTMLElement} box   Chart box.
 * @param {ChartState}  state Chart state.
 * @param {unknown}     error What went wrong.
 */
function fail( box, state, error ) {
	try {
		state.chart?.dispose();
	} catch {
		// Already broken; the box is cleared below.
	}
	state.chart = null;
	state.option = null;
	state.size = '';
	box.removeAttribute( ECHARTS_ID );
	box.replaceChildren();
	state.figure.classList.remove( 'is-rendered' );
	state.figure.classList.add( 'is-failed' );
	showTable( state.figure );
	disableDownload( state.figure );
	// eslint-disable-next-line no-console
	console.error( 'Easy Charts: could not draw a chart.', error );
}

/**
 * Run the after-draw hooks for a chart.
 *
 * @param {Object}      chart ECharts instance.
 * @param {HTMLElement} box   Chart box.
 */
function afterDraw( chart, box ) {
	afterDrawHooks.forEach( ( hook ) => hook( chart, box ) );
}

/**
 * Draw a chart, or draw it again with a new layout.
 *
 * @param {HTMLElement} box Chart box.
 */
function draw( box ) {
	const state = charts.get( box );
	if ( ! state || ! hasSize( box ) ) {
		return;
	}
	const { figure, config } = state;
	try {
		if ( ! config ) {
			throw new Error( 'Missing or malformed chart config.' );
		}
		const width = box.clientWidth;
		// Only the known fields of the untrusted config.
		const option = withMotionPreference(
			buildOption( {
				type: config.type,
				data: config.data,
				settings: config.settings,
				title: config.title,
				strings: config.strings,
				// For the "site font" setting: the font of the text around the chart.
				siteFont: window.getComputedStyle( figure ).fontFamily,
				width,
			} )
		);
		const chart =
			state.chart ?? echarts.init( box, null, { renderer: 'svg' } );
		state.chart = chart;
		chart.setOption( option, true );
		hideDrawing( box );
		state.option = option;
		state.compact = isCompact( width );
		state.size = sizeOf( box );
		figure.classList.remove( 'is-failed' );
		figure.classList.add( 'is-rendered' );
		hideTable( figure );
		enableDownload( figure, () =>
			state.chart
				? { echarts, option: state.option, box, title: config.title }
				: null
		);
		afterDraw( chart, box );
	} catch ( error ) {
		fail( box, state, error );
	}
}

/**
 * Fit a drawn chart to its box, or queue a waiting one that now has a size.
 *
 * @param {HTMLElement} box Chart box.
 */
function fit( box ) {
	const state = charts.get( box );
	if ( ! state || ! hasSize( box ) ) {
		// Not watched, or hidden again (a closed tab): nothing to fit.
		return;
	}
	if ( ! state.chart ) {
		schedule( box );
		return;
	}
	if ( sizeOf( box ) === state.size ) {
		// Same size as drawn (e.g. the first report after observing): nothing to redo.
		return;
	}
	if ( isCompact( box.clientWidth ) !== state.compact ) {
		// Crossed the phone width: rebuild the layout.
		draw( box );
		return;
	}
	try {
		state.chart.resize();
		state.size = sizeOf( box );
		afterDraw( state.chart, box );
	} catch ( error ) {
		fail( box, state, error );
	}
}

/**
 * Run a function in a later task, so the browser can handle input and paint in between.
 *
 * @param {() => void} callback Callback.
 */
function nextTask( callback ) {
	if ( window.scheduler?.postTask ) {
		window.scheduler
			.postTask( callback, { priority: 'user-visible' } )
			.catch( () => {} );
		return;
	}
	window.setTimeout( callback, 0 );
}

/**
 * Index in the queue of the chart to draw next: one near the viewport if any, else the first
 * one that can be drawn.
 *
 * @return {number} Index, or -1 if none can be drawn now.
 */
function nextIndex() {
	let first = -1;
	for ( let i = 0; i < queue.length; i++ ) {
		if ( hasSize( queue[ i ] ) ) {
			if ( charts.get( queue[ i ] )?.near ) {
				return i;
			}
			if ( first === -1 ) {
				first = i;
			}
		}
	}
	return first;
}

/**
 * Draw the next waiting chart, then come back in another task for the one after it.
 */
function drawNext() {
	taskPending = false;
	const index = nextIndex();
	if ( index === -1 ) {
		return;
	}
	const [ box ] = queue.splice( index, 1 );
	draw( box );
	if ( nextIndex() !== -1 ) {
		requestDrawing();
	}
}

/**
 * Make sure a drawing task is coming.
 */
function requestDrawing() {
	if ( ! taskPending ) {
		taskPending = true;
		nextTask( drawNext );
	}
}

/**
 * Queue a chart for drawing.
 *
 * @param {HTMLElement} box Chart box.
 */
function schedule( box ) {
	if ( ! queue.includes( box ) ) {
		queue.push( box );
	}
	requestDrawing();
}

/**
 * Draw every waiting chart now, in this task (before printing).
 */
export function drawAllNow() {
	while ( queue.length ) {
		const index = nextIndex();
		if ( index === -1 ) {
			return;
		}
		draw( queue.splice( index, 1 )[ 0 ] );
	}
}

/**
 * Start watching a chart figure.
 *
 * @param {HTMLElement} figure Chart figure.
 */
function watch( figure ) {
	const box = figure.querySelector( `:scope > ${ BOX }` );
	if ( ! box || charts.has( box ) ) {
		return;
	}

	// A copy of a drawn chart (e.g. a carousel clone) brings the old drawing and ECharts' id but
	// no chart instance of its own: clear it and draw it as a chart of its own.
	if ( box.hasAttribute( ECHARTS_ID ) ) {
		const instance = echarts.getInstanceByDom( box );
		if ( ! instance || instance.getDom() !== box ) {
			box.removeAttribute( ECHARTS_ID );
			box.replaceChildren();
			figure.classList.remove( 'is-rendered', 'is-failed' );
			showTable( figure );
		}
	}

	charts.set( box, {
		figure,
		config: readConfig( figure ),
		chart: null,
		option: null,
		compact: false,
		near: false,
		size: '',
	} );
	resizeObserver?.observe( box );
	viewportObserver?.observe( box );
	schedule( box );
}

/**
 * Watch every chart figure in or under an element.
 *
 * @param {Element|Document} root Element or document.
 */
function scan( root ) {
	if ( root instanceof window.Element && root.matches( FIGURE ) ) {
		watch( root );
	}
	root.querySelectorAll?.( FIGURE ).forEach( watch );
}

/**
 * Let go of charts whose box is no longer on the page.
 */
function forget() {
	charts.forEach( ( state, box ) => {
		if ( box.isConnected ) {
			return;
		}
		try {
			state.chart?.dispose();
		} catch {
			// Nothing left to clean up.
		}
		resizeObserver?.unobserve( box );
		viewportObserver?.unobserve( box );
		charts.delete( box );
		const index = queue.indexOf( box );
		if ( index !== -1 ) {
			queue.splice( index, 1 );
		}
	} );
}

/**
 * Re-scan the page, let go of removed charts and fit all others. Also the public refresh.
 */
export function refresh() {
	if ( ! start() ) {
		return;
	}
	scan( document );
	forget();
	charts.forEach( ( _, box ) => fit( box ) );
}

/**
 * Observe sizes, the viewport and page changes.
 */
function observe() {
	const resized = new Set();
	let resizeFrame = 0;
	if ( window.ResizeObserver ) {
		resizeObserver = new window.ResizeObserver( ( entries ) => {
			entries.forEach( ( entry ) => resized.add( entry.target ) );
			// Batched into a frame: resizing inside the callback can cause "ResizeObserver
			// loop" errors.
			resizeFrame ||= window.requestAnimationFrame( () => {
				resizeFrame = 0;
				const boxes = [ ...resized ];
				resized.clear();
				boxes.forEach( fit );
			} );
		} );
	} else {
		let frame = 0;
		const refreshSoon = () => {
			frame ||= window.requestAnimationFrame( () => {
				frame = 0;
				refresh();
			} );
		};
		window.addEventListener( 'resize', refreshSoon );
		window.addEventListener( 'load', refreshSoon );
	}

	if ( window.IntersectionObserver ) {
		viewportObserver = new window.IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					const state = charts.get( entry.target );
					if ( state && entry.isIntersecting ) {
						state.near = true;
					}
				} );
				if ( queue.length ) {
					requestDrawing();
				}
			},
			{ rootMargin: '200px' }
		);
	}

	const added = new Set();
	let removed = false;
	let mutationFrame = 0;
	new window.MutationObserver( ( records ) => {
		records.forEach( ( record ) => {
			// ECharts' own drawing, tooltip and other changes inside a chart box.
			if ( record.target.closest?.( BOX ) ) {
				return;
			}
			record.addedNodes.forEach( ( node ) => {
				if ( node instanceof window.HTMLElement ) {
					added.add( node );
				}
			} );
			removed ||= record.removedNodes.length > 0;
		} );
		if ( ! added.size && ! removed ) {
			return;
		}
		mutationFrame ||= window.requestAnimationFrame( () => {
			mutationFrame = 0;
			added.forEach( ( node ) => node.isConnected && scan( node ) );
			added.clear();
			if ( removed ) {
				removed = false;
				forget();
			}
		} );
	} ).observe( document.documentElement, { childList: true, subtree: true } );
}

/**
 * Start once the ECharts runtime is there.
 *
 * @return {boolean} Whether the lifecycle is running.
 */
export function start() {
	if ( started ) {
		return true;
	}
	const runtime = window.easyChartsECharts;
	if (
		typeof runtime?.init !== 'function' ||
		typeof runtime?.getInstanceByDom !== 'function'
	) {
		return false;
	}
	echarts = runtime;
	started = true;
	observe();
	scan( document );
	return true;
}
