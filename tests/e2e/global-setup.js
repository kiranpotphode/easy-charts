/**
 * External dependencies
 */
const { execSync } = require( 'child_process' );
const wordpressSetup = require( '@wordpress/scripts/config/playwright/global-setup.js' );

/**
 * Activate the plugin, log in (as `@wordpress/scripts` does), then seed the example charts and
 * pages on the test site.
 *
 * Seeding is idempotent: it updates the example charts in place and resets the 1.x examples to
 * their 1.x meta, so tests that save a chart start from the same state on every run.
 *
 * @param {import('@playwright/test').FullConfig} config Playwright config.
 */
module.exports = async function globalSetup( config ) {
	// The test site is shared with PHPUnit, which doesn't need the plugin active.
	execSync( 'npm run -s test-env:wp -- plugin activate easy-charts', {
		stdio: 'inherit',
	} );
	await wordpressSetup( config );

	for ( const args of [ '', '--v1' ] ) {
		execSync(
			`npm run -s test-env:wp -- easy-charts seed-examples ${ args }`,
			{
				stdio: 'inherit',
			}
		);
	}
	// Start from the default global settings (fails harmlessly when the option is not set).
	execSync(
		'npm run -s test-env:wp -- option delete easy_charts_settings || true',
		{ stdio: 'ignore' }
	);
};
