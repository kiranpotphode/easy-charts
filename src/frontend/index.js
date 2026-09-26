/**
 * Front-end chart renderer (script handle: easy-charts-frontend).
 *
 * Each chart is server-rendered as a <figure class="easy-charts-chart"> holding an empty chart
 * box, a JSON config and a data table. This script draws the chart into the box, then marks the
 * figure as rendered so CSS hides the table visually (screen readers still read it).
 */

/**
 * Internal dependencies
 */
import { buildOption } from '../charts/build-option';
import { hideDrawing, withMotionPreference } from '../charts/display';
import './frontend.css';

const SELECTOR = '.easy-charts-chart';

/**
 * Read a chart's JSON config.
 *
 * @param {HTMLElement} figure Chart figure.
 * @return {Object|null} Config, or null if missing or malformed.
 */
function readConfig( figure ) {
	const script = figure.querySelector( '.easy-charts-chart__config' );
	if ( ! script ) {
		return null;
	}
	try {
		return JSON.parse( script.textContent );
	} catch {
		return null;
	}
}

/**
 * Turn a title into a safe file name.
 *
 * @param {string} title Chart title.
 * @return {string} File name without extension.
 */
function fileName( title ) {
	const slug = String( title || '' )
		.toLowerCase()
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-|-$/g, '' );
	return slug || 'chart';
}

/**
 * Save the chart as a PNG by drawing it once, off screen, with the canvas renderer.
 *
 * @param {Object}      echarts ECharts runtime.
 * @param {Object}      option  ECharts option used on screen.
 * @param {HTMLElement} box     On-screen chart box, for the size.
 * @param {string}      name    File name without extension.
 */
function downloadPng( echarts, option, box, name ) {
	const holder = document.createElement( 'div' );
	holder.style.cssText = `position:fixed;left:-10000px;top:0;width:${ box.clientWidth }px;height:${ box.clientHeight }px;`;
	document.body.appendChild( holder );

	const chart = echarts.init( holder, null, { renderer: 'canvas' } );
	chart.setOption( { ...option, animation: false } );
	const url = chart.getDataURL( {
		type: 'png',
		pixelRatio: 2,
		backgroundColor: option.backgroundColor || '#ffffff',
	} );
	chart.dispose();
	holder.remove();

	const link = document.createElement( 'a' );
	link.href = url;
	link.download = `${ name }.png`;
	document.body.appendChild( link );
	link.click();
	link.remove();
}

/**
 * Draw one chart.
 *
 * @param {HTMLElement} figure  Chart figure.
 * @param {Object}      echarts ECharts runtime.
 */
function renderChart( figure, echarts ) {
	const config = readConfig( figure );
	const box = figure.querySelector( '.easy-charts-chart__canvas' );
	if ( ! config || ! box ) {
		return;
	}

	const option = withMotionPreference( buildOption( config ) );
	const chart = echarts.init( box, null, { renderer: 'svg' } );
	chart.setOption( option );
	hideDrawing( box );
	figure.classList.add( 'is-rendered' );

	if (
		config.settings?.size?.responsive !== false &&
		window.ResizeObserver
	) {
		new window.ResizeObserver( () => chart.resize() ).observe( box );
	}

	const button = figure.querySelector( '.easy-charts-chart__download' );
	if ( button ) {
		button.hidden = false;
		button.addEventListener( 'click', () =>
			downloadPng( echarts, option, box, fileName( config.title ) )
		);
	}
}

/**
 * Draw every chart on the page.
 */
function renderAll() {
	const echarts = window.easyChartsECharts;
	if ( ! echarts ) {
		return;
	}
	document
		.querySelectorAll( `${ SELECTOR }:not(.is-rendered)` )
		.forEach( ( figure ) => renderChart( figure, echarts ) );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', renderAll );
} else {
	renderAll();
}
