/**
 * External dependencies
 */
const { omit } = require( 'lodash' );

const stable = {
	core: {
		wcBlocksRegistry: './assets/js/blocks-registry/index.js',
		wcSettings: './assets/js/settings/shared/index.js',
		wcBlocksData: './assets/js/data/index.js',
		wcBlocksMiddleware: './assets/js/middleware/index.js',
		wcSharedContext: './assets/js/shared/context/index.js',
	},
	main: {
		// Shared blocks code
		blocks: './assets/js/index.js',

		// @wordpress/components styles
		'custom-select-control-style':
			'./node_modules/wordpress-components/src/custom-select-control/style.scss',
		'spinner-style':
			'./node_modules/wordpress-components/src/spinner/style.scss',
		'snackbar-notice-style':
			'./node_modules/wordpress-components/src/snackbar/style.scss',

		// Styles for grid blocks. WP <=5.2 doesn't have the All Products block,
		// so this file would not be included if not explicitly declared here.
		// This file is excluded from the default build so CSS styles are included
		// in the other the components are imported.
		'product-list-style':
			'./assets/js/base/components/product-list/style.scss',

		// Blocks
		'handpicked-products':
			'./assets/js/blocks/handpicked-products/index.js',
		'product-best-sellers':
			'./assets/js/blocks/product-best-sellers/index.js',
		'product-category': './assets/js/blocks/product-category/index.js',
		'product-categories': './assets/js/blocks/product-categories/index.js',
		'product-new': './assets/js/blocks/product-new/index.js',
		'product-on-sale': './assets/js/blocks/product-on-sale/index.js',
		'product-top-rated': './assets/js/blocks/product-top-rated/index.js',
		'products-by-attribute':
			'./assets/js/blocks/products-by-attribute/index.js',
		'featured-product': './assets/js/blocks/featured-product/index.js',
		'all-reviews': './assets/js/blocks/reviews/all-reviews/index.js',
		'reviews-by-product':
			'./assets/js/blocks/reviews/reviews-by-product/index.js',
		'reviews-by-category':
			'./assets/js/blocks/reviews/reviews-by-category/index.js',
		'product-search': './assets/js/blocks/product-search/index.js',
		'product-tag': './assets/js/blocks/product-tag/index.js',
		'featured-category': './assets/js/blocks/featured-category/index.js',
		'all-products': './assets/js/blocks/products/all-products/index.js',
		'price-filter': './assets/js/blocks/price-filter/index.js',
		'attribute-filter': './assets/js/blocks/attribute-filter/index.js',
		'active-filters': './assets/js/blocks/active-filters/index.js',
		'block-error-boundary':
			'./assets/js/base/components/block-error-boundary/style.scss',
		cart: './assets/js/blocks/cart-checkout/cart/index.js',
		checkout: './assets/js/blocks/cart-checkout/checkout/index.js',
	},
	frontend: {
		reviews: './assets/js/blocks/reviews/frontend.js',
		'all-products': './assets/js/blocks/products/all-products/frontend.js',
		'price-filter': './assets/js/blocks/price-filter/frontend.js',
		'attribute-filter': './assets/js/blocks/attribute-filter/frontend.js',
		'active-filters': './assets/js/blocks/active-filters/frontend.js',
		cart: './assets/js/blocks/cart-checkout/cart/frontend.js',
		checkout: './assets/js/blocks/cart-checkout/checkout/frontend.js',
	},
	payments: {
		'wc-payment-method-stripe':
			'./assets/js/payment-method-extensions/payment-methods/stripe/index.js',
		'wc-payment-method-cheque':
			'./assets/js/payment-method-extensions/payment-methods/cheque/index.js',
		'wc-payment-method-paypal':
			'./assets/js/payment-method-extensions/payment-methods/paypal/index.js',
	},
};

const experimental = {
	core: {},
	main: {
		'single-product': './assets/js/blocks/single-product/index.js',
	},
	frontend: {
		'single-product': './assets/js/blocks/single-product/frontend.js',
	},
	payments: {},
};

const getEntryConfig = ( type = 'main', exclude = [] ) => {
	return omit(
		parseInt( process.env.WOOCOMMERCE_BLOCKS_PHASE, 10 ) < 3
			? stable[ type ]
			: { ...stable[ type ], ...experimental[ type ] },
		exclude
	);
};

module.exports = {
	getEntryConfig,
};
