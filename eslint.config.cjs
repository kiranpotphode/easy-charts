/**
 * ESLint: the @wordpress/scripts defaults, minus the 1.x code and its vendored libraries.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

/**
 * Packages WordPress provides at runtime (the build turns them into `wp.*` externals), so they are
 * not installed from npm. Add a package here when the block starts importing it.
 */
const WORDPRESS_MODULES = [
	'@wordpress/api-fetch',
	'@wordpress/block-editor',
	'@wordpress/blocks',
	'@wordpress/components',
	'@wordpress/compose',
	'@wordpress/core-data',
	'@wordpress/data',
	'@wordpress/element',
	'@wordpress/i18n',
	'@wordpress/icons',
	'@wordpress/url',
];

module.exports = [
	{
		ignores: [ 'admin/**', 'includes/**', 'public/**' ],
	},
	...defaultConfig,
	{
		settings: {
			'import/core-modules': WORDPRESS_MODULES,
		},
	},
];
