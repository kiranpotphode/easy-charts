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

// WP-CLI on the site under test. WP_CLI_COMMAND points it at another site, e.g. one running the
// minimum supported WordPress version.
const WP_CLI = process.env.WP_CLI_COMMAND || 'npm run -s test-env:wp --';

/**
 * Run WP-CLI on the site under test. Retried once: `wp-env run` sometimes fails to connect on
 * slow networks.
 *
 * @param {string}  args          WP-CLI arguments.
 * @param {Object}  [options]     Options.
 * @param {boolean} [options.log] Show the output instead of returning it.
 * @return {string} Output.
 */
function wp( args, { log = false } = {} ) {
	const run = () =>
		execSync( `${ WP_CLI } ${ args }`, {
			encoding: 'utf8',
			stdio: [ 'ignore', log ? 'inherit' : 'pipe', 'pipe' ],
		} );
	let output;
	try {
		output = run();
	} catch {
		output = run();
	}
	return ( output ?? '' ).trim();
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
