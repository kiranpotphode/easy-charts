/**
 * End-to-end tests (Playwright) against the wp-env test site on port 8889.
 *
 * Extends the `@wordpress/scripts` config: logged in as admin, reduced motion, one worker.
 */
const path = require( 'path' );
const { defineConfig } = require( '@playwright/test' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config.js' );

module.exports = defineConfig( {
	...baseConfig,
	testDir: './tests/e2e/specs',
	globalSetup: path.resolve( __dirname, 'tests/e2e/global-setup.js' ),
	webServer: {
		...baseConfig.webServer,
		command: 'npm run test-env:start',
	},
} );
