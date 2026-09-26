/**
 * ESLint: the @wordpress/scripts defaults, minus the 1.x code and its vendored libraries.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	{
		ignores: [ 'admin/**', 'includes/**', 'public/**' ],
	},
	...defaultConfig,
];
