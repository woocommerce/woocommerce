/**
 * External dependencies
 */
const { omit } = require( 'lodash' );
const glob = require( 'glob' );

// List of blocks that should be used as webpack entry points. They are expected
// to be in `/assets/js/blocks/[BLOCK_NAME]`. If they are not, their relative
// path should be defined in the `customDir` property. The scripts below will
// take care of looking for `index.js`, `frontend.js` and `*.scss` files in each
// block directory.
// If a block is experimental, it should be marked with the `isExperimental`
// property.
const blocks = {
	'handpicked-products': {},
	'product-best-sellers': {},
	'product-category': {},
	'product-categories': {},
	'product-new': {},
	'product-on-sale': {},
	'product-top-rated': {},
	'products-by-attribute': {},
	'featured-product': {},
	'all-reviews': {
		customDir: 'reviews/all-reviews',
	},
	'reviews-by-product': {
		customDir: 'reviews/reviews-by-product',
	},
	'reviews-by-category': {
		customDir: 'reviews/reviews-by-category',
	},
	'product-search': {},
	'product-tag': {},
	'featured-category': {},
	'all-products': {
		customDir: 'products/all-products',
	},
	'price-filter': {},
	'attribute-filter': {},
	'stock-filter': {},
	'active-filters': {},
	cart: {
		customDir: 'cart-checkout/cart',
	},
	checkout: {},
	'mini-cart': {
		customDir: 'cart-checkout/mini-cart',
		isExperimental: true,
	},
	'mini-cart-contents': {
		customDir: 'cart-checkout/mini-cart-contents',
		isExperimental: true,
	},
	'single-product': {
		isExperimental: true,
	},
	'legacy-template': {},
};

// Returns the entries for each block given a relative path (ie: `index.js`,
// `**/*.scss`...).
// It also filters out elements with undefined props and experimental blocks.
const getBlockEntries = ( relativePath ) => {
	const experimental =
		! parseInt( process.env.WOOCOMMERCE_BLOCKS_PHASE, 10 ) < 3;

	return Object.fromEntries(
		Object.entries( blocks )
			.filter(
				( [ , config ] ) =>
					! config.isExperimental ||
					config.isExperimental === experimental
			)
			.map( ( [ blockCode, config ] ) => {
				const filePaths = glob.sync(
					`./assets/js/blocks/${ config.customDir || blockCode }/` +
						relativePath
				);
				if ( filePaths.length > 0 ) {
					return [ blockCode, filePaths ];
				}
				return null;
			} )
			.filter( Boolean )
	);
};

const entries = {
	styling: {
		// @wordpress/components styles
		'custom-select-control-style':
			'./node_modules/wordpress-components/src/custom-select-control/style.scss',
		'snackbar-notice-style':
			'./node_modules/wordpress-components/src/snackbar/style.scss',
		'combobox-control-style':
			'./node_modules/wordpress-components/src/combobox-control/style.scss',

		'general-style': glob.sync( './assets/**/*.scss', {
			ignore: [
				// Block styles are added below.
				'./assets/js/blocks/*/*.scss',
			],
		} ),

		'packages-style': glob.sync( './packages/**/*.scss' ),

		'reviews-style': './assets/js/blocks/reviews/editor.scss',
		...getBlockEntries( '**/*.scss' ),
	},
	core: {
		wcBlocksRegistry: './assets/js/blocks-registry/index.js',
		wcSettings: './assets/js/settings/shared/index.ts',
		wcBlocksData: './assets/js/data/index.ts',
		wcBlocksMiddleware: './assets/js/middleware/index.js',
		wcBlocksSharedContext: './assets/js/shared/context/index.js',
		wcBlocksSharedHocs: './assets/js/shared/hocs/index.js',
		priceFormat: './packages/prices/index.js',
		blocksCheckout: './packages/checkout/index.js',
	},
	main: {
		// Shared blocks code
		'wc-blocks': './assets/js/index.js',

		// Blocks
		...getBlockEntries( 'index.{t,j}s{,x}' ),
	},
	frontend: {
		reviews: './assets/js/blocks/reviews/frontend.js',
		...getBlockEntries( 'frontend.{t,j}s{,x}' ),
		'mini-cart-component':
			'./assets/js/blocks/cart-checkout/mini-cart/component-frontend.tsx',
	},
	payments: {
		'wc-payment-method-cheque':
			'./assets/js/payment-method-extensions/payment-methods/cheque/index.js',
		'wc-payment-method-paypal':
			'./assets/js/payment-method-extensions/payment-methods/paypal/index.js',
		'wc-payment-method-bacs':
			'./assets/js/payment-method-extensions/payment-methods/bacs/index.js',
		'wc-payment-method-cod':
			'./assets/js/payment-method-extensions/payment-methods/cod/index.js',
	},
	extensions: {
		'wc-blocks-google-analytics':
			'./assets/js/extensions/google-analytics/index.ts',
	},
};

const getEntryConfig = ( type = 'main', exclude = [] ) => {
	return omit( entries[ type ], exclude );
};

module.exports = {
	getEntryConfig,
};
