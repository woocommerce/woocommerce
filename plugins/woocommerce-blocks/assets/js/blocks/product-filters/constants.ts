export const BlockOverlayAttribute = {
	NEVER: 'never',
	MOBILE: 'mobile',
	ALWAYS: 'always',
} as const;

export const DISALLOWED_BLOCKS = [
	'woocommerce/product-filter-attribute',
	'woocommerce/product-collection',
	'core/query',
];
