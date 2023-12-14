const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

module.exports = {
	...defaultConfig,
	entry: {
		index: './src/index.ts',
		'woo-ai-assistant': './src/woo-ai-assistant/index.ts',
	},
	cache: false,
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.tsx?$/,
				use: 'ts-loader',
				exclude: /node_modules/,
			},
			{
				test: /\.(png|jp(e*)g|svg|gif)$/,
				type: 'asset/resource',
			},
		],
	},
	resolve: {
		alias: {
			// https://github.com/facebook/react/issues/20235
			'react/jsx-dev-runtime': 'react/jsx-dev-runtime.js',
			'react/jsx-runtime': 'react/jsx-runtime.js',
		},
		extensions: [ '.js', '.jsx', '.tsx', '.ts' ],
		fallback: {
			stream: false,
			path: false,
			fs: false,
		},
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
