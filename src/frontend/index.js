/**
 * Front-end chart renderer (script handle: easy-charts-frontend).
 *
 * Each chart is server-rendered as a <figure class="easy-charts-chart"> holding an empty chart
 * box, a JSON config and a data table. This script draws the chart into the box (see
 * lifecycle.js), then hides the table visually (screen readers still read it).
 *
 * Public hooks, for themes and plugins that show charts in ways the script can't notice:
 * `window.easyCharts.refresh()` or `document.dispatchEvent( new Event( 'easy-charts:refresh' ) )`
 * finds new charts, lets go of removed ones and fits all of them to their boxes.
 */

/**
 * Internal dependencies
 */
import { refresh, start } from './lifecycle';
import './frontend.css';

// Optimisers may run this before the ECharts runtime: start with whichever comes first.
const tryStart = () => start();
document.addEventListener( 'easy-charts:echarts-ready', tryStart );
window.addEventListener( 'load', tryStart );
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', tryStart );
} else {
	tryStart();
}

// The hooks take no input; an existing `window.easyCharts` from something else is left alone.
document.addEventListener( 'easy-charts:refresh', () => refresh() );
if ( ! window.easyCharts ) {
	window.easyCharts = Object.freeze( { refresh: () => refresh() } );
}
