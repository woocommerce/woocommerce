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
//
// If a block is experimental, it should be marked with the `isExperimental`
// property.
// Update plugins/woocommerce/client/blocks/docs/internal-developers/blocks/feature-flags-and-experimental-interfaces.md
// when you mark/unmark block experimental.
const blocks = {
	'active-filters': {},
	'add-to-cart-form': {
		customDir: 'product-elements/add-to-cart-form',
	},
	'add-to-cart-with-options': {
		isExperimental: true,
	},
	'add-to-cart-with-options-quantity-selector': {
		customDir: 'add-to-cart-with-options/quantity-selector',
		isExperimental: true,
	},
	'add-to-cart-with-options-variation-selector': {
		customDir: 'add-to-cart-with-options/variation-selector',
		isExperimental: true,
	},
	'add-to-cart-with-options-variation-selector-item': {
		customDir:
			'add-to-cart-with-options/variation-selector/attribute-item-template',
		isExperimental: true,
	},
	'add-to-cart-with-options-variation-selector-attribute-name': {
		customDir: 'add-to-cart-with-options/variation-selector/attribute-name',
		isExperimental: true,
	},
	'add-to-cart-with-options-variation-selector-attribute-options': {
		customDir:
			'add-to-cart-with-options/variation-selector/attribute-options',
		isExperimental: true,
	},
	'add-to-cart-with-options-grouped-product-selector': {
		customDir: 'add-to-cart-with-options/grouped-product-selector',
		isExperimental: true,
	},
	'add-to-cart-with-options-grouped-product-selector-item': {
		customDir:
			'add-to-cart-with-options/grouped-product-selector/product-item-template',
		isExperimental: true,
	},
	'add-to-cart-with-options-grouped-product-selector-item-cta': {
		customDir:
			'add-to-cart-with-options/grouped-product-selector/product-item-cta',
		isExperimental: true,
	},
	'all-products': {
		customDir: 'products/all-products',
	},
	'all-reviews': {
		customDir: 'reviews/all-reviews',
	},
	'attribute-filter': {},
	breadcrumbs: {},
	'blockified-product-details': {
		isExperimental: true,
		customDir: 'product-details',
	},
	'catalog-sorting': {},
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
	'store-notices': {},
	'product-filters': {
		isExperimental: true,
	},
	'product-filter-status': {
		isExperimental: true,
		customDir: 'product-filters/inner-blocks/status-filter',
	},
	'product-filter-price': {
		customDir: 'product-filters/inner-blocks/price-filter',
		isExperimental: true,
	},
	'product-filter-attribute': {
		customDir: 'product-filters/inner-blocks/attribute-filter',
		isExperimental: true,
	},
	'product-filter-rating': {
		customDir: 'product-filters/inner-blocks/rating-filter',
		isExperimental: true,
	},
	'product-filter-active': {
		customDir: 'product-filters/inner-blocks/active-filters',
		isExperimental: true,
	},
	'product-filter-removable-chips': {
		customDir: 'product-filters/inner-blocks/removable-chips',
		isExperimental: true,
	},
	'product-filter-clear-button': {
		customDir: 'product-filters/inner-blocks/clear-button',
		isExperimental: true,
	},
	'product-filter-checkbox-list': {
		customDir: 'product-filters/inner-blocks/checkbox-list',
		isExperimental: true,
	},
	'product-filter-chips': {
		customDir: 'product-filters/inner-blocks/chips',
		isExperimental: true,
	},
	'product-filter-price-slider': {
		customDir: 'product-filters/inner-blocks/price-slider',
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
	'order-confirmation-create-account': {
		customDir: 'order-confirmation/create-account',
		isExperimental: true,
	},
};

/**
 * Blocks that are generic and will likely be pushed up to Gutenberg or a public block registry.
 */
const genericBlocks = {
	'accordion-group': {
		customDir: 'accordion/accordion-group',
	},
	'accordion-header': {
		customDir: 'accordion/inner-blocks/accordion-header',
	},
	'accordion-item': {
		customDir: 'accordion/inner-blocks/accordion-item',
	},
	'accordion-panel': {
		customDir: 'accordion/inner-blocks/accordion-panel',
	},
};

// Intentional separation of cart and checkout entry points to allow for better code splitting.
const cartAndCheckoutBlocks = {
	cart: {},
	'cart-link': {},
	checkout: {},
	'mini-cart': {},
	'mini-cart-contents': {
		customDir: 'mini-cart/mini-cart-contents',
	},
};

// Returns the entries for each block given a relative path (ie: `index.js`,
// `**/*.scss`...).
// It also filters out elements with undefined props and experimental blocks.
const getBlockEntries = ( relativePath, blockEntries = blocks ) => {
	return Object.fromEntries(
		Object.entries( blockEntries )
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

// `blocks` entries are used to build styles **and** JS, but for
// frontend JS of these blocks we use a script modules build so
// we skip building their JS files in the old build.
// The script modules build handles them in
// webpack-config-interactivity-blocks-frontend.js.
const frontendScriptModuleBlocksToSkip = [
	'product-gallery',
	'product-gallery-large-image',
	'store-notices',
	'product-collection',
	'product-filters',
	'product-filter-status',
	'product-filter-price',
	'product-filter-attribute',
	'product-filter-rating',
	'product-filter-active',
	'product-filter-removable-chips',
	'add-to-cart-form',
	'add-to-cart-with-options',
	'add-to-cart-with-options-quantity-selector',
	'add-to-cart-with-options-variation-selector',
	'add-to-cart-with-options-grouped-product-selector',
	'add-to-cart-with-options-grouped-product-selector-item',
	'accordion-group',
];

const frontendEntries = getBlockEntries( 'frontend.{t,j}s{,x}', {
	...Object.fromEntries(
		Object.entries( { ...blocks, ...genericBlocks } ).filter(
			( [ blockName ] ) => {
				return ! frontendScriptModuleBlocksToSkip.includes( blockName );
			}
		)
	),
} );

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

		...getBlockEntries( '{index,block,frontend}.{t,j}s{,x}', {
			...blocks,
			...genericBlocks,
			...cartAndCheckoutBlocks,
		} ),

		// Templates
		'wc-blocks-classic-template-revert-button-style':
			'./assets/js/templates/revert-button/index.tsx',
	},
	core: {
		wcBlocksRegistry: './assets/js/blocks-registry/index.js',
		blocksCheckoutEvents: './assets/js/events/index.ts',
		wcSettings: './assets/js/settings/shared/index.ts',
		wcBlocksData: './assets/js/data/index.ts',
		wcBlocksMiddleware: './assets/js/middleware/index.js',
		wcBlocksSharedContext: './assets/js/shared/context/index.js',
		wcBlocksSharedHocs: './assets/js/shared/hocs/index.js',
		wcSchemaParser: './assets/js/utils/schema-parser/index.ts',
		priceFormat: './packages/prices/index.js',
		wcTypes: './assets/js/types/index.ts',
	},
	main: {
		// Shared blocks code
		'wc-blocks': './assets/js/index.js',

		// Blocks
		...getBlockEntries( 'index.{t,j}s{,x}', {
			...blocks,
			...genericBlocks,
			...cartAndCheckoutBlocks,
		} ),
	},
	frontend: {
		reviews: './assets/js/blocks/reviews/frontend.ts',
		...frontendEntries,
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
	cartAndCheckoutFrontend: {
		...getBlockEntries( 'frontend.{t,j}s{,x}', cartAndCheckoutBlocks ),
		blocksCheckout: './packages/checkout/index.js',
		blocksComponents: './packages/components/index.ts',
		'mini-cart-component':
			'./assets/js/blocks/mini-cart/component-frontend.tsx',
	},
};

const getEntryConfig = ( type = 'main', exclude = [] ) => {
	return omit( entries[ type ], exclude );
};

module.exports = {
	getEntryConfig,
	genericBlocks,
};
