export const EXCLUDED_BLOCKS = [
	'woocommerce/product-filters',
	'woocommerce/product-filter-attribute',
	'woocommerce/product-filter-active',
	'woocommerce/product-filter-price',
	'woocommerce/product-filter-status',
	'woocommerce/product-collection',
	'woocommerce/add-to-cart-with-options',
	'woocommerce/add-to-cart-with-options-grouped-product-item',
	'woocommerce/add-to-cart-with-options-grouped-product-item-label',
	'woocommerce/add-to-cart-with-options-grouped-product-item-selector',
	'woocommerce/add-to-cart-with-options-quantity-selector',
	'woocommerce/add-to-cart-with-options-variation-description',
	'woocommerce/add-to-cart-with-options-variation-selector',
	'woocommerce/add-to-cart-with-options-variation-selector-attribute',
	'core/query',
];

/**
 * Block types that declare a product filter or variation-selector parent in `ancestor`
 * but are not interchangeable display styles for the style toggle (chips vs dropdown).
 */
export const DISPLAY_STYLE_SWITCHER_EXCLUDED_BLOCK_NAMES: string[] = [
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-name',
];
