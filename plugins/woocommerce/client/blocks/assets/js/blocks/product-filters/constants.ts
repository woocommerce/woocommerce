export const PRODUCT_FILTERS_STORE_NAME = 'woocommerce/product-filters';
export const PRODUCT_FILTERS_STORE_LOCK =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

export const EXCLUDED_BLOCKS = [
	PRODUCT_FILTERS_STORE_NAME,
	'woocommerce/product-filter-attribute',
	'woocommerce/product-filter-active',
	'woocommerce/product-filter-price',
	'woocommerce/product-filter-status',
	'woocommerce/product-collection',
	'woocommerce/add-to-cart-form',
	'woocommerce/add-to-cart-with-options',
	'core/query',
];
