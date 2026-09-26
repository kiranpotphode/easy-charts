/**
 * External dependencies
 */
const wordpressSetup = require( '@wordpress/scripts/config/playwright/global-setup.js' );

/**
 * Internal dependencies
 */
const { wp } = require( './utils' );

/**
 * Activate the plugin, log in (as `@wordpress/scripts` does), then seed the example charts and
 * pages on the site under test.
 *
 * Seeding is idempotent: it updates the example charts in place and resets the 1.x examples to
 * their 1.x meta, so tests that save a chart start from the same state on every run.
 *
 * @param {import('@playwright/test').FullConfig} config Playwright config.
 */
module.exports = async function globalSetup( config ) {
	wp( 'plugin activate easy-charts', { log: true } );
	await wordpressSetup( config );

	wp( 'easy-charts seed-examples', { log: true } );
	wp( 'easy-charts seed-examples --v1', { log: true } );
	// Start from the default global settings (fails harmlessly when the option is not set).
	wp( 'option delete easy_charts_settings || true' );
};
