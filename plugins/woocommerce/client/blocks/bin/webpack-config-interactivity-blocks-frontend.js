/**
 * External dependencies
 */
const path = require( 'path' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

/**
 * Internal dependencies
 */
const { sharedOptimizationConfig } = require( './webpack-shared-config' );

const entries = {
	// Blocks
	'woocommerce/product-button':
		'./assets/js/atomic/blocks/product-elements/button/frontend.tsx',
	'woocommerce/product-gallery':
		'./assets/js/blocks/product-gallery/frontend.tsx',
	'woocommerce/product-gallery-large-image':
		'./assets/js/blocks/product-gallery/inner-blocks/product-gallery-large-image/frontend.tsx',
	'woocommerce/product-collection':
		'./assets/js/blocks/product-collection/frontend.tsx',
	'woocommerce/product-filters':
		'./assets/js/blocks/product-filters/frontend.ts',
	'woocommerce/product-filter-active':
		'./assets/js/blocks/product-filters/inner-blocks/active-filters/frontend.ts',
	'woocommerce/product-filter-attribute':
		'./assets/js/blocks/product-filters/inner-blocks/attribute-filter/frontend.ts',
	'woocommerce/product-filter-checkbox-list':
		'./assets/js/blocks/product-filters/inner-blocks/checkbox-list/frontend.ts',
	'woocommerce/product-filter-chips':
		'./assets/js/blocks/product-filters/inner-blocks/chips/frontend.ts',
	'woocommerce/product-filter-price':
		'./assets/js/blocks/product-filters/inner-blocks/price-filter/frontend.ts',
	'woocommerce/product-filter-price-slider':
		'./assets/js/blocks/product-filters/inner-blocks/price-slider/frontend.ts',
	'woocommerce/product-filter-rating':
		'./assets/js/blocks/product-filters/inner-blocks/rating-filter/frontend.ts',
	'woocommerce/product-filter-removable-chips':
		'./assets/js/blocks/product-filters/inner-blocks/removable-chips/frontend.ts',
	'woocommerce/product-filter-status':
		'./assets/js/blocks/product-filters/inner-blocks/status-filter/frontend.ts',
	'woocommerce/accordion-group':
		'./assets/js/blocks/accordion/accordion-group/frontend.js',
	'woocommerce/add-to-cart-form':
		'./assets/js/blocks/product-elements/add-to-cart-form/frontend.ts',
	'woocommerce/add-to-cart-with-options':
		'./assets/js/blocks/add-to-cart-with-options/frontend.ts',
	'woocommerce/add-to-cart-with-options-grouped-product-selector':
		'./assets/js/blocks/add-to-cart-with-options/grouped-product-selector/frontend.ts',
	'woocommerce/add-to-cart-with-options-quantity-selector':
		'./assets/js/blocks/add-to-cart-with-options/quantity-selector/frontend.ts',
	'woocommerce/add-to-cart-with-options-variation-selector':
		'./assets/js/blocks/add-to-cart-with-options/variation-selector/frontend.ts',

	// Other
	'woocommerce/product-collection-notices':
		'./assets/js/blocks/product-collection/notices-frontend.ts',
};

module.exports = {
	entry: entries,
	optimization: sharedOptimizationConfig,
	name: 'interactivity-blocks-modules',
	experiments: {
		outputModule: true,
	},
	output: {
		devtoolNamespace: 'wc',
		filename: '[name].js',
		library: {
			type: 'module',
		},
		path: path.resolve( __dirname, '../build/' ),
		asyncChunks: false,
		chunkFormat: 'module',
		module: true,
	},
	resolve: {
		extensions: [ '.js', '.ts', '.tsx' ],
	},
	plugins: [
		new DependencyExtractionWebpackPlugin( {
			combineAssets: true,
			combinedOutputFile: './interactivity-blocks-frontend-assets.php',
		} ),
	],
	module: {
		rules: [
			{
				test: /\.(j|t)sx?$/,
				exclude: [ /[\/\\](node_modules|build|docs|vendor)[\/\\]/ ],
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							[
								'@wordpress/babel-preset-default',
								{
									modules: false,
									targets: {
										browsers: [
											'extends @wordpress/browserslist-config',
										],
									},
								},
							],
						],
						cacheDirectory: path.resolve(
							__dirname,
							'../../../node_modules/.cache/babel-loader'
						),
						cacheCompression: false,
					},
				},
			},
			{
				test: /\.s[c|a]ss$/,
				use: {
					loader: 'ignore-loader',
				},
			},
		],
	},
};
