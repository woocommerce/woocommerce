/**
 * Internal dependencies
 */
const { webpackConfig } = require( '@woocommerce/internal-style-build' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const path = require( 'path' );

module.exports = {
	mode: process.env.NODE_ENV || 'development',
	entry: {
		'build-style': __dirname + '/src/style.scss',
	},
	output: {
		path: __dirname,
	},
	module: {
		parser: webpackConfig.parser,
		rules: webpackConfig.rules,
	},
	plugins: [
		...webpackConfig.plugins,
		// Copy the rich-text.js file to the build directory.
		// This is required for the Personalization tags to work. Can be removed after minimal required WordPress version is set to 6.8.
		new CopyWebpackPlugin( {
			patterns: [
				{
					from: path.join( __dirname, 'assets' ),
					to: './build/assets',
				},
				{
					from: path.join( __dirname, 'assets' ),
					to: './build-module/assets',
				},
			],
		} ),
	],
};
