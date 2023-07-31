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
	'active-filters': {},
	'add-to-cart-form': {},
	'all-products': {
		customDir: 'products/all-products',
	},
	'all-reviews': {
		customDir: 'reviews/all-reviews',
	},
	'attribute-filter': {},
	breadcrumbs: {},
	cart: {},
	'catalog-sorting': {},
	checkout: {},
	'customer-account': {},
	'featured-category': {
		customDir: 'featured-items/featured-category',
	},
	'featured-product': {
		customDir: 'featured-items/featured-product',
	},
	'filter-wrapper': {},
	'handpicked-products': {},
	// We need to keep the legacy-template id, so we need to add a custom config to point to the renamed classic-template folder
	'legacy-template': {
		customDir: 'classic-template',
	},
	'mini-cart': {},
	'mini-cart-contents': {
		customDir: 'mini-cart/mini-cart-contents',
	},
	'store-notices': {},
	'price-filter': {},
	'product-best-sellers': {},
	'product-category': {},
	'product-categories': {},
	'product-gallery': {
		isExperimental: true,
	},
	'product-gallery-large-image': {
		customDir: 'product-gallery/inner-blocks/product-gallery-large-image',
		isExperimental: true,
	},
	'product-new': {},
	'product-on-sale': {},
	'product-query': {
		isExperimental: true,
	},
	'product-results-count': {},
	'product-search': {},
	'product-tag': {},
	'product-top-rated': {},
	'products-by-attribute': {},
	'rating-filter': {},
	'product-average-rating': {},
	'product-rating-stars': {},
	'product-rating-counter': {},
	'reviews-by-category': {
		customDir: 'reviews/reviews-by-category',
	},
	'reviews-by-product': {
		customDir: 'reviews/reviews-by-product',
	},
	'single-product': {},
	'stock-filter': {},
	'product-collection': {
		isExperimental: true,
	},
	'product-template': {
		isExperimental: true,
	},
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
		// Packages styles
		'packages-style': glob.sync( './packages/**/index.js' ),

		// Shared blocks code
		'wc-blocks': './assets/js/index.js',

		// Blocks
		'product-image-gallery':
			'./assets/js/atomic/blocks/product-elements/product-image-gallery/index.ts',
		'product-reviews':
			'./assets/js/atomic/blocks/product-elements/product-reviews/index.tsx',
		'product-details':
			'./assets/js/atomic/blocks/product-elements/product-details/index.tsx',
		'add-to-cart-form':
			'./assets/js/atomic/blocks/product-elements/add-to-cart-form/index.tsx',
		...getBlockEntries( '{index,block,frontend}.{t,j}s{,x}' ),

		// Templates
		'wc-blocks-classic-template-revert-button-style':
			'./assets/js/templates/revert-button/index.tsx',
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
			'./assets/js/blocks/mini-cart/component-frontend.tsx',
	},
	payments: {
		'wc-payment-method-cheque':
			'./assets/js/extensions/payment-methods/cheque/index.js',
		'wc-payment-method-paypal':
			'./assets/js/extensions/payment-methods/paypal/index.js',
		'wc-payment-method-bacs':
			'./assets/js/extensions/payment-methods/bacs/index.js',
		'wc-payment-method-cod':
			'./assets/js/extensions/payment-methods/cod/index.js',
	},
	extensions: {
		'wc-blocks-google-analytics':
			'./assets/js/extensions/google-analytics/index.ts',
		'wc-shipping-method-pickup-location':
			'./assets/js/extensions/shipping-methods/pickup-location/index.js',
	},
	editor: {
		'wc-blocks-classic-template-revert-button':
			'./assets/js/templates/revert-button/index.tsx',
	},
};

const getEntryConfig = ( type = 'main', exclude = [] ) => {
	return omit( entries[ type ], exclude );
};

module.exports = {
	getEntryConfig,
};
