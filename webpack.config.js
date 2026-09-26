/**
 * Build configuration: the @wordpress/scripts defaults with the blocks and named entry points.
 *
 * Each entry becomes build/<name>.js plus build/<name>.asset.php (dependencies and version),
 * which PHP reads when registering the script handle.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const { getWebpackEntryPoints } = require( '@wordpress/scripts/utils' );

const scriptConfig = Array.isArray( defaultConfig )
	? defaultConfig[ 0 ]
	: defaultConfig;

module.exports = {
	...scriptConfig,
	entry: () => ( {
		// Blocks found through their block.json (build/blocks/<name>/, block.json copied along).
		...getWebpackEntryPoints( 'script' )(),
		// Shared ECharts runtime (handle: easy-charts-echarts).
		echarts: './src/echarts/index.js',
		// Draws charts on the front end (handle: easy-charts-frontend).
		frontend: './src/frontend/index.js',
	} ),
};
