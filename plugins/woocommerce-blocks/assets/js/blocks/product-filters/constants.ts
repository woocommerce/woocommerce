export const BlockOverlayAttribute = {
	NEVER: 'never',
	MOBILE: 'mobile',
	ALWAYS: 'always',
} as const;

export const EXCLUDED_BLOCKS = [
	'woocommerce/product-filters',
	'woocommerce/product-filter-attribute',
	'woocommerce/product-filter-price',
	'woocommerce/product-collection',
	'core/query',
];
