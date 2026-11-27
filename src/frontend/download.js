/**
 * The "Download" button under a chart: saves the chart as a PNG.
 */

/**
 * Buttons that already have a click listener. A WeakSet, not a data attribute: a copied chart
 * (carousel clones) copies attributes but not listeners.
 *
 * @type {WeakSet<HTMLButtonElement>}
 */
const bound = new WeakSet();

/**
 * Turn a title into a safe file name.
 *
 * @param {string} title Chart title.
 * @return {string} File name without extension.
 */
export function fileName( title ) {
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
	try {
		chart.setOption( { ...option, animation: false } );
		const url = chart.getDataURL( {
			type: 'png',
			pixelRatio: 2,
			backgroundColor: option.backgroundColor || '#ffffff',
		} );
		const link = document.createElement( 'a' );
		link.href = url;
		link.download = `${ name }.png`;
		document.body.appendChild( link );
		link.click();
		link.remove();
	} finally {
		chart.dispose();
		holder.remove();
	}
}

/**
 * Show a figure's download button and make it save the chart's current drawing.
 *
 * @param {HTMLElement}   figure   Chart figure.
 * @param {() => ?Object} getChart Returns `{ echarts, option, box, title }` for the current
 *                                 drawing, or null when the chart isn't drawn.
 */
export function enableDownload( figure, getChart ) {
	const button = figure.querySelector(
		':scope > .easy-charts-chart__download'
	);
	if ( ! button ) {
		return;
	}
	button.hidden = false;
	if ( bound.has( button ) ) {
		return;
	}
	bound.add( button );
	button.addEventListener( 'click', () => {
		const current = getChart();
		if ( current?.option ) {
			downloadPng(
				current.echarts,
				current.option,
				current.box,
				fileName( current.title )
			);
		}
	} );
}

/**
 * Hide a figure's download button (the chart could not be drawn).
 *
 * @param {HTMLElement} figure Chart figure.
 */
export function disableDownload( figure ) {
	const button = figure.querySelector(
		':scope > .easy-charts-chart__download'
	);
	if ( button ) {
		button.hidden = true;
	}
}
