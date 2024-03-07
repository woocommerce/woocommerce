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
		title: __( 'WooCommerce Single Product Block', 'woocommerce' ),
		placeholder: PLACEHOLDERS.singleProduct,
	},
	'archive-product': {
		type: TYPES.productCatalog,
		title: __( 'WooCommerce Product Grid Block', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'taxonomy-product_cat': {
		type: TYPES.productTaxonomy,
		title: __( 'WooCommerce Product Taxonomy Block', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'taxonomy-product_tag': {
		type: TYPES.productTaxonomy,
		title: __( 'WooCommerce Product Tag Block', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'taxonomy-product_attribute': {
		type: TYPES.productTaxonomy,
		title: __( 'WooCommerce Product Attribute Block', 'woocommerce' ),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	// Since that it is a fallback value, it has to be the last one.
	'taxonomy-product': {
		type: TYPES.productTaxonomy,
		title: __(
			"WooCommerce Product's Custom Taxonomy Block",
			'woocommerce'
		),
		placeholder: PLACEHOLDERS.archiveProduct,
	},
	'product-search-results': {
		type: TYPES.productSearchResults,
		title: __( 'WooCommerce Product Search Results Block', 'woocommerce' ),
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
