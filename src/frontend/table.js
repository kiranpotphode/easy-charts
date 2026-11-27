/**
 * The data table under each chart: visible without JavaScript and when a chart can't be drawn,
 * visually hidden (still read by screen readers) once the chart is drawn.
 *
 * The hiding is also set inline: "remove unused CSS" optimisers drop the `.is-rendered` rule in
 * frontend.css, because only this script adds that class. Styles go through CSSOM, which a strict
 * Content Security Policy allows.
 */

const DATA = '.easy-charts-chart__data';

const VISUALLY_HIDDEN = {
	position: 'absolute',
	width: '1px',
	height: '1px',
	margin: '-1px',
	padding: '0',
	overflow: 'hidden',
	'clip-path': 'inset(50%)',
	'white-space': 'nowrap',
	border: '0',
};

/**
 * Hide a chart's data table visually.
 *
 * @param {HTMLElement} figure Chart figure.
 */
export function hideTable( figure ) {
	const data = figure.querySelector( `:scope > ${ DATA }` );
	if ( ! data ) {
		return;
	}
	Object.entries( VISUALLY_HIDDEN ).forEach( ( [ property, value ] ) =>
		data.style.setProperty( property, value, 'important' )
	);
}

/**
 * Show a chart's data table again (the chart could not be drawn).
 *
 * @param {HTMLElement} figure Chart figure.
 */
export function showTable( figure ) {
	const data = figure.querySelector( `:scope > ${ DATA }` );
	if ( ! data ) {
		return;
	}
	Object.keys( VISUALLY_HIDDEN ).forEach( ( property ) =>
		data.style.removeProperty( property )
	);
}
