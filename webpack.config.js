/**
 * Webpack config file for the project.
 *
 * @package easy-charts
 */

const path                 = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const JSDir                = path.resolve( __dirname, 'src/js' );
const ESLintPlugin         = require( 'eslint-webpack-plugin' );

const devMode = process.env.NODE_ENV !== "production";

module.exports = {
	entry: {
		frontend: JSDir + '/easy-charts-public.js', // Entry for main js file.
		admin: JSDir + '/easy-charts-admin.js',
		insertChartButton: JSDir + '/insert-chart-button.js',
	},
	output: {
		path: path.resolve( __dirname, 'build' ),
		filename: 'js/[name].js', // JS files (required by Webpack for entry points).
	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: [
					devMode ? "style-loader" : MiniCssExtractPlugin.loader, 'css-loader' ]
			},
			{
				test: /\.scss$/,
				use: [
					MiniCssExtractPlugin.loader, // Extracts CSS into files.
					'css-loader', // Translates CSS into CommonJS.
					'sass-loader', // Compiles SCSS to CSS.
				],
			},
			{
				test: /\.(woff|woff2|eot|ttf|otf)$/i,
				type: 'asset/inline',
			}
		],
	},
	plugins: [
		new ESLintPlugin( {"configType": "flat",
			exclude: ['src', 'dependencies'],} ),
		new MiniCssExtractPlugin(
			{
				filename: 'css/[name].css', // Outputs CSS files for each entry.
			}
		),
	],
	//mode: 'production', // Change to 'development' for debugging.
	mode: devMode ? "development" : "production", // Change to 'development' for debugging.
	devtool: 'source-map'
};
