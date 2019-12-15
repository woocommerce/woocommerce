const defaultConfig = require( "@wordpress/scripts/config/webpack.config" );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

const requestToExternal = request => {
	const wcDepMap = {
		'@woocommerce/components': [ 'window', 'wc', 'components' ],
		'@woocommerce/csv-export': [ 'window', 'wc', 'csvExport' ],
		'@woocommerce/currency': [ 'window', 'wc', 'currency' ],
		'@woocommerce/date': [ 'window', 'wc', 'date' ],
		'@woocommerce/navigation': [ 'window', 'wc', 'navigation' ],
		'@woocommerce/number': [ 'window', 'wc', 'number' ],
		'@woocommerce/settings': [ 'window', 'wc', 'wcSettings' ],
	};

	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToHandle = request => {
	const wcHandleMap = {
		'@woocommerce/components': 'wc-components',
		'@woocommerce/csv-export': 'wc-csv',
		'@woocommerce/currency': 'wc-currency',
		'@woocommerce/date': 'wc-date',
		'@woocommerce/navigation': 'wc-navigation',
		'@woocommerce/number': 'wc-number',
		'@woocommerce/settings': 'wc-settings',
	};

	if ( wcHandleMap[ request ] ) {
		return wcHandleMap[ request ];
	}
};

module.exports = {
	...defaultConfig,
	plugins: [
		...defaultConfig.plugins.filter(
			plugin => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin',
		),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			requestToExternal,
			requestToHandle,
		} ),
		new MiniCssExtractPlugin( {
			filename: 'style.css',
		} ),
	],
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.(sa|sc|c)ss$/,
				use: [
					{
						loader: MiniCssExtractPlugin.loader,
					},
					'css-loader',
					'sass-loader',
				],
			},
		],
	},
};
