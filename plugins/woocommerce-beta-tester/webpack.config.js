const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const path = require("path");

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		// Separate entry point for the live-branches page.
		'live-branches': './src/live-branches/index.tsx',
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.tsx?$/,
				use: 'ts-loader',
				exclude: [
					path.resolve( __dirname, './build/' ),
					path.resolve( __dirname, './node_modules/' ),
					path.resolve( __dirname, './vendor/' ),
				],
			},
		],
	},
	resolve: {
		extensions: [ '.js', '.jsx', '.tsx', '.ts' ],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
