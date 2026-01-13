/**
 * External dependencies
 */
const { omit } = require( 'lodash' );
const glob = require( 'glob' );
const { scriptModuleEntries } = require( './webpack-interactivity-entries' );

// List of blocks that should be used as webpack entry points. They are expected
// to be in `/assets/js/blocks/[BLOCK_NAME]`. If they are not, their relative
// path should be defined in the `customDir` property. The scripts below will
// take care of looking for `index.js`, `frontend.js` and `*.scss` files in each
// block directory.
const blocks = {
	'active-filters': {},
	'add-to-cart-form': {
		customDir: 'product-elements/add-to-cart-form',
	},
	'add-to-cart-with-options': {},
	'add-to-cart-with-options-quantity-selector': {
		customDir: 'add-to-cart-with-options/quantity-selector',
	},
	'add-to-cart-with-options-variation-description': {
		customDir: 'add-to-cart-with-options/variation-description',
	},
	'add-to-cart-with-options-variation-selector': {
		customDir: 'add-to-cart-with-options/variation-selector',
	},
	'add-to-cart-with-options-variation-selector-attribute': {
		customDir: 'add-to-cart-with-options/variation-selector/attribute',
	},
	'add-to-cart-with-options-variation-selector-attribute-name': {
		customDir: 'add-to-cart-with-options/variation-selector/attribute-name',
	},
	'add-to-cart-with-options-variation-selector-attribute-options': {
		customDir:
			'add-to-cart-with-options/variation-selector/attribute-options',
	},
	'add-to-cart-with-options-grouped-product-selector': {
		customDir: 'add-to-cart-with-options/grouped-product-selector',
	},
	'add-to-cart-with-options-grouped-product-item': {
		customDir:
			'add-to-cart-with-options/grouped-product-selector/product-item',
	},
	'add-to-cart-with-options-grouped-product-item-selector': {
		customDir:
			'add-to-cart-with-options/grouped-product-selector/product-item-selector',
	},
	'add-to-cart-with-options-grouped-product-item-label': {
		customDir:
			'add-to-cart-with-options/grouped-product-selector/product-item-label',
	},
	'all-products': {
		customDir: 'products/all-products',
	},
	'all-reviews': {
		customDir: 'reviews/all-reviews',
	},
	'attribute-filter': {},
	breadcrumbs: {},
	'catalog-sorting': {},
	'category-description': {},
	'category-title': {},
	'coming-soon': {},
	'coupon-code': {},
	'customer-account': {},
	'email-content': {},
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
	'payment-method-icons': {},
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
		customDir: 'next-previous-buttons',
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
	'product-filters': {},
	'product-filter-status': {
		customDir: 'product-filters/inner-blocks/status-filter',
	},
	'product-filter-price': {
		customDir: 'product-filters/inner-blocks/price-filter',
	},
	'product-filter-attribute': {
		customDir: 'product-filters/inner-blocks/attribute-filter',
	},
	'product-filter-rating': {
		customDir: 'product-filters/inner-blocks/rating-filter',
	},
	'product-filter-active': {
		customDir: 'product-filters/inner-blocks/active-filters',
	},
	'product-filter-taxonomy': {
		customDir: 'product-filters/inner-blocks/taxonomy-filter',
	},
	'product-filter-removable-chips': {
		customDir: 'product-filters/inner-blocks/removable-chips',
	},
	'product-filter-clear-button': {
		customDir: 'product-filters/inner-blocks/clear-button',
	},
	'product-filter-checkbox-list': {
		customDir: 'product-filters/inner-blocks/checkbox-list',
	},
	'product-filter-chips': {
		customDir: 'product-filters/inner-blocks/chips',
	},
	'product-filter-price-slider': {
		customDir: 'product-filters/inner-blocks/price-slider',
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
	},
	'product-details': {},
	'product-description': {},
	'product-specifications': {},
	'product-reviews': {},
	'product-review-rating': {
		customDir: 'product-reviews/inner-blocks/review-rating',
	},
	'product-reviews-title': {
		customDir: 'product-reviews/inner-blocks/reviews-title',
	},
	'product-review-form': {
		customDir: 'product-reviews/inner-blocks/review-form',
	},
	'product-review-date': {
		customDir: 'product-reviews/inner-blocks/review-date',
	},
	'product-review-content': {
		customDir: 'product-reviews/inner-blocks/review-content',
	},
	'product-review-author-name': {
		customDir: 'product-reviews/inner-blocks/review-author-name',
	},
	'product-reviews-pagination': {
		customDir: 'product-reviews/inner-blocks/reviews-pagination',
	},
	'product-reviews-pagination-next': {
		customDir: 'product-reviews/inner-blocks/reviews-pagination-next',
	},
	'product-reviews-pagination-previous': {
		customDir: 'product-reviews/inner-blocks/reviews-pagination-previous',
	},
	'product-reviews-pagination-numbers': {
		customDir: 'product-reviews/inner-blocks/reviews-pagination-numbers',
	},
	'product-review-template': {
		customDir: 'product-reviews/inner-blocks/review-template',
	},
};

/**
 * Blocks that are generic and will likely be pushed up to Gutenberg or a public block registry.
 * Keep in sync with the generic_blocks array in copy-blocks-json.sh
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
						relativePath,
					{ dotRelative: true }
				);
				if ( filePaths.length > 0 ) {
					return [ blockCode, filePaths ];
				}
				return null;
			} )
			.filter( Boolean )
	);
};

// Script module blocks scripts and styles are handled in
// webpack-config-interactivity-blocks-frontend.js.
const frontendScriptModuleBlocksToSkip = Object.keys( scriptModuleEntries );

const frontendEntries = getBlockEntries( 'frontend.{t,j}s{,x}', {
	...Object.fromEntries(
		Object.entries( { ...blocks, ...genericBlocks } ).filter(
			( [ blockName ] ) => {
				return ! frontendScriptModuleBlocksToSkip.includes(
					`woocommerce/${ blockName }`
				);
			}
		)
	),
} );

// Remove styles from style build,
// that are already included in interactivity
// script modules build.
const blockStylingEntries = getBlockEntries(
	'{index,block,frontend}.{t,j}s{,x}',
	{
		...Object.fromEntries(
			Object.entries( {
				...blocks,
				...genericBlocks,
				...cartAndCheckoutBlocks,
			} ).filter( ( [ blockName ] ) => {
				return ! frontendScriptModuleBlocksToSkip.includes(
					`woocommerce/${ blockName }`
				);
			} )
		),
	}
);

const entries = {
	styling: {
		// Packages styles
		'packages-style': glob.sync( './packages/**/index.{t,j}s', {
			dotRelative: true,
		} ),

		// Shared blocks code
		'wc-blocks': './assets/js/index.js',

		// Blocks
		'product-image-gallery':
			'./assets/js/atomic/blocks/product-elements/product-image-gallery/index.ts',

		...blockStylingEntries,
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
		wcEntities: './assets/js/entities/index.ts',
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
