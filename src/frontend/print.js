/**
 * Printing and "Save as PDF".
 *
 * On screen ECharts sizes its SVG in px and positions it absolutely, so on paper a chart kept its
 * screen width and was cut off. A `viewBox` lets frontend.css scale the SVG to the page width
 * instead. ECharts' SVG renderer never sets or removes `viewBox` on screen, so it stays; it is set
 * again after every draw and resize, when the size changes.
 *
 * Before printing, every chart still waiting is drawn at once; afterwards all are fitted again.
 */

/**
 * Internal dependencies
 */
import { drawAllNow, onAfterDraw, refresh } from './lifecycle';

/**
 * Give a chart's SVG a viewBox matching its size.
 *
 * @param {Object}      chart ECharts instance.
 * @param {HTMLElement} box   Chart box.
 */
function setViewBox( chart, box ) {
	const svg = box.querySelector( 'svg' );
	const width = chart.getWidth();
	const height = chart.getHeight();
	if ( svg && width > 0 && height > 0 ) {
		svg.setAttribute( 'viewBox', `0 0 ${ width } ${ height }` );
	}
}

/**
 * Prepare charts for printing.
 */
export function setUpPrinting() {
	onAfterDraw( setViewBox );
	window.addEventListener( 'beforeprint', () => drawAllNow() );
	window.addEventListener( 'afterprint', () => refresh() );
}
