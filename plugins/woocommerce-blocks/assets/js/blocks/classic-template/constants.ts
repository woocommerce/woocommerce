/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TemplateDetails } from './types';

export const BLOCK_SLUG = 'woocommerce/legacy-template';
export const TYPES = {
	singleProduct: 'single-product',
	productCatalog: 'product-catalog',
	productTaxonomy: 'product-taxonomy',
	productSearchResults: 'product-search-results',
	orderConfirmation: 'order-confirmation',
	checkoutHeader: 'checkout-header',
};
export const PLACEHOLDERS = {
	singleProduct: 'single-product',
	archiveProduct: 'archive-product',
	orderConfirmation: 'fallback',
	checkoutHeader: 'checkout-header',
};

export const TEMPLATES: TemplateDetails = {
	'single-product': {
		type: TYPES.singleProduct,
		title: __( 'Single Product Classic Template', 'woocommerce' ),
		placeholder: PLACEHOLDERS.singleProduct,
	},
	'archive-product': {
		type: TYPES.productCatalog,
		title: __( 'Product Grid Classic Template', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'taxonomy-product_cat': {
		type: TYPES.productTaxonomy,
		title: __( 'Product Taxonomy Classic Template', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'taxonomy-product_tag': {
		type: TYPES.productTaxonomy,
		title: __( 'Product Tag Classic Template', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'taxonomy-product_attribute': {
		type: TYPES.productTaxonomy,
		title: __( 'Product Attribute Classic Template', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	// Since that it is a fallback value, it has to be the last one.
	'taxonomy-product': {
		type: TYPES.productTaxonomy,
		title: __(
			"Product's Custom Taxonomy Classic Template",
			'woocommerce'
		),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'product-search-results': {
		type: TYPES.productSearchResults,
		title: __( 'Product Search Results Classic Template', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'checkout-header': {
		type: TYPES.checkoutHeader,
		title: __( 'Checkout Header', 'woocommerce' ),
		placeholder: 'checkout-header',
	},
	'order-confirmation': {
		type: TYPES.orderConfirmation,
		title: __( 'Order Confirmation Block', 'woocommerce' ),
		placeholder: PLACEHOLDERS.orderConfirmation,
	},
};
