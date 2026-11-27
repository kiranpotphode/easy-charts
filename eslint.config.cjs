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
	'@wordpress/editor',
	'@wordpress/element',
	'@wordpress/i18n',
	'@wordpress/plugins',
	'@wordpress/url',
];

/**
 * Security (AGENTS.md, "Security first"): chart data and settings reach this code and are untrusted,
 * so no HTML sinks, no code built from strings, and styles only through `element.style` (CSSOM,
 * which a strict Content Security Policy allows).
 */
const NO_HTML = 'Chart data is untrusted: build DOM nodes and set textContent instead of parsing HTML.';
const SECURITY_RULES = {
	'no-restricted-properties': [
		'error',
		{ property: 'innerHTML', message: NO_HTML },
		{ property: 'outerHTML', message: NO_HTML },
		{ property: 'insertAdjacentHTML', message: NO_HTML },
		{ property: 'createContextualFragment', message: NO_HTML },
		{ object: 'document', property: 'write', message: NO_HTML },
		{ object: 'document', property: 'writeln', message: NO_HTML },
	],
	'no-restricted-syntax': [
		'error',
		{
			selector: "JSXAttribute[name.name='dangerouslySetInnerHTML']",
			message: NO_HTML,
		},
		{
			selector:
				"CallExpression[callee.property.name='setAttribute'][arguments.0.value=/^(style$|on)/i]",
			message:
				'Set styles through element.style and listeners with addEventListener.',
		},
	],
	'no-implied-eval': 'error',
	'no-new-func': 'error',
	'react/no-danger': 'error',
};

module.exports = [
	...defaultConfig,
	{
		settings: {
			'import/core-modules': WORDPRESS_MODULES,
		},
	},
	{
		files: [ 'src/**/*.js' ],
		rules: SECURITY_RULES,
	},
];
