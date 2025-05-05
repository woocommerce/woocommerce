const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: './src/index.ts',
	},
	module: {
		...defaultConfig.module,
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
		// Copy the rich-text.js file to the build directory.
		// This is required for the Personalization tags to work. Can be removed after default version is set to WP 6.8.
		new CopyWebpackPlugin( {
			patterns: [
				{
					from: path.join( __dirname, 'assets' ),
					to: './assets',
				},
			],
		} ),
	],
};
