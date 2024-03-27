/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const BLOCK_NAME_MAP = {
	'active-filters': 'woocommerce/product-filter-active',
	'price-filter': 'woocommerce/product-filter-price',
	'stock-filter': 'woocommerce/product-filter-stock-status',
	'rating-filter': 'woocommerce/product-filter-rating',
	'attribute-filter': 'woocommerce/product-filter-attribute',
};

export const BLOCK_TITLE_MAP = {
	'active-filters': __(
		'Product Filter: Active Filters (Beta)',
		'woocommerce'
	),
	'price-filter': __( 'Product Filter: Price (Beta)', 'woocommerce' ),
	'stock-filter': __( 'Product Filter: Stock Status (Beta)', 'woocommerce' ),
	'rating-filter': __( 'Product Filter: Rating (Beta)', 'woocommerce' ),
	'attribute-filter': __( 'Product Filter: Attribute (Beta)', 'woocommerce' ),
};

export const BLOCK_ICON_MAP = {
	'active-filters': 'filter',
	'price-filter': 'currency',
	'stock-filter': 'box',
	'rating-filter': 'star-filled',
	'attribute-filter': 'tag',
};
