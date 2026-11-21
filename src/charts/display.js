/**
 * Display helpers shared by the front end and the editor preview.
 */

/**
 * Turn off chart animation for people who ask for reduced motion.
 *
 * @param {Object} option ECharts option.
 * @param {Window} [view] Window the chart is drawn in (the editor canvas is an iframe).
 * @return {Object} Option, without animation when reduced motion is preferred.
 */
export function withMotionPreference( option, view = window ) {
	const reduce = view?.matchMedia?.(
		'(prefers-reduced-motion: reduce)'
	).matches;
	return reduce ? { ...option, animation: false } : option;
}

/**
 * Hide the drawn SVG from screen readers: the chart box is an image with a short description,
 * and the SVG would read out every axis label and value again.
 *
 * @param {HTMLElement} box Chart box.
 */
export function hideDrawing( box ) {
	box.firstElementChild?.setAttribute( 'aria-hidden', 'true' );
}
