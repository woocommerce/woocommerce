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
	'coming-soon': {},
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
	'classic-shortcode': {},
	'mini-cart': {},
	'mini-cart-contents': {
		customDir: 'mini-cart/mini-cart-contents',
	},
	'store-notices': {},
	'page-content-wrapper': {},
	'price-filter': {},
	'product-best-sellers': {},
	'product-category': {},
	'product-categories': {},
	'product-collection': {},
	'product-collection-no-results': {
		customDir: 'product-collection/inner-blocks/no-results',
	},
	'product-gallery': {},
	'product-gallery-large-image': {
		customDir: 'product-gallery/inner-blocks/product-gallery-large-image',
	},
	'product-gallery-large-image-next-previous': {
		customDir:
			'product-gallery/inner-blocks/product-gallery-large-image-next-previous',
	},
	'product-gallery-pager': {
		customDir: 'product-gallery/inner-blocks/product-gallery-pager',
	},
	'product-gallery-thumbnails': {
		customDir: 'product-gallery/inner-blocks/product-gallery-thumbnails',
	},
	'product-new': {},
	'product-on-sale': {},
	'product-query': {},
	'product-results-count': {},
	'product-search': {},
	'product-tag': {},
	'product-template': {},
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
	'product-filter': {
		isExperimental: true,
	},
	'product-filter-stock-status': {
		isExperimental: true,
		customDir: 'product-filter/inner-blocks/stock-filter',
	},
	'product-filter-price': {
		customDir: 'product-filter/inner-blocks/price-filter',
		isExperimental: true,
	},
	'product-filter-attribute': {
		customDir: 'product-filter/inner-blocks/attribute-filter',
		isExperimental: true,
	},
	'product-filter-rating': {
		customDir: 'product-filter/inner-blocks/rating-filter',
		isExperimental: true,
	},
	'product-filter-active': {
		customDir: 'product-filter/inner-blocks/active-filters',
		isExperimental: true,
	},
	'order-confirmation-summary': {
		customDir: 'order-confirmation/summary',
	},
	'order-confirmation-totals-wrapper': {
		customDir: 'order-confirmation/totals-wrapper',
	},
	'order-confirmation-totals': {
		customDir: 'order-confirmation/totals',
	},
	'order-confirmation-downloads-wrapper': {
		customDir: 'order-confirmation/downloads-wrapper',
	},
	'order-confirmation-downloads': {
		customDir: 'order-confirmation/downloads',
	},
	'order-confirmation-billing-address': {
		customDir: 'order-confirmation/billing-address',
	},
	'order-confirmation-shipping-address': {
		customDir: 'order-confirmation/shipping-address',
	},
	'order-confirmation-billing-wrapper': {
		customDir: 'order-confirmation/billing-wrapper',
	},
	'order-confirmation-shipping-wrapper': {
		customDir: 'order-confirmation/shipping-wrapper',
	},
	'order-confirmation-status': {
		customDir: 'order-confirmation/status',
	},
	'order-confirmation-additional-information': {
		customDir: 'order-confirmation/additional-information',
	},
	'order-confirmation-additional-fields-wrapper': {
		customDir: 'order-confirmation/additional-fields-wrapper',
	},
	'order-confirmation-additional-fields': {
		customDir: 'order-confirmation/additional-fields',
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
		'packages-style': glob.sync( './packages/**/index.{t,j}s' ),

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

		// Interactivity component styling
		'wc-interactivity-checkbox-list':
			'./packages/interactivity-components/checkbox-list/index.ts',
		'wc-interactivity-dropdown':
			'./packages/interactivity-components/dropdown/index.ts',

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
		wcTypes: './assets/js/types/index.ts',

		// interactivity components, exported as separate entries for now
		'wc-interactivity-dropdown':
			'./packages/interactivity-components/dropdown/index.ts',
		'wc-interactivity-checkbox-list':
			'./packages/interactivity-components/checkbox-list/index.ts',
	},
	main: {
		// Shared blocks code
		'wc-blocks': './assets/js/index.js',

		// Blocks
		...getBlockEntries( 'index.{t,j}s{,x}' ),
	},
	frontend: {
		reviews: './assets/js/blocks/reviews/frontend.ts',
		...getBlockEntries( 'frontend.{t,j}s{,x}' ),

		blocksCheckout: './packages/checkout/index.js',
		blocksComponents: './packages/components/index.ts',

		'mini-cart-component':
			'./assets/js/blocks/mini-cart/component-frontend.tsx',
		'product-button-interactivity':
			'./assets/js/atomic/blocks/product-elements/button/frontend.tsx',
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
