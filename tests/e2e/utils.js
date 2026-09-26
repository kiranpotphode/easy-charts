/**
 * External dependencies
 */
const { execSync } = require( 'child_process' );
const AxeBuilder = require( '@axe-core/playwright' ).default;

// The chart block inside the iframed editor canvas, as an axe frame selector.
const EDITOR_CHART = [
	'iframe[name="editor-canvas"]',
	'.wp-block-easy-charts-chart',
];

const WCAG_TAGS = [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ];

/**
 * Run axe (WCAG 2.2 A/AA rules) on part of a page.
 *
 * @param {import('@playwright/test').Page} page    Page.
 * @param {string|string[]}                 include Selector, or [ frame selector, selector ] for an iframe.
 * @return {Promise<Object[]>} Violations, simplified for readable test failures.
 */
async function axe( page, include ) {
	const results = await new AxeBuilder( { page } )
		.include( include )
		.withTags( WCAG_TAGS )
		.analyze();

	return results.violations.map( ( violation ) => ( {
		id: violation.id,
		impact: violation.impact,
		help: violation.help,
		targets: violation.nodes.map( ( node ) => node.target.join( ' ' ) ),
	} ) );
}

/**
 * Run WP-CLI on the test site.
 *
 * @param {string} args WP-CLI arguments.
 * @return {string} Output.
 */
function wp( args ) {
	return execSync( `npm run -s test-env:wp -- ${ args }`, {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'ignore' ],
	} ).trim();
}

/**
 * Find a chart post by (part of) its title.
 *
 * @param {Object} requestUtils Request utils.
 * @param {string} search       Title search.
 * @return {Promise<number>} Chart post ID.
 */
async function findChart( requestUtils, search ) {
	const charts = await requestUtils.rest( {
		path: '/easy-charts/v1/charts',
		params: { search },
	} );
	if ( ! charts.length ) {
		throw new Error( `No chart matches "${ search }".` );
	}
	return charts[ 0 ].id;
}

module.exports = { axe, wp, findChart, EDITOR_CHART };
