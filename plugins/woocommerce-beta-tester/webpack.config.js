const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );


console.log(defaultConfig);

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		// Separate entry point for the live-branches page.
		'live-branches': './src/live-branches/app.ts'
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
