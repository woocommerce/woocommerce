/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockVariation } from '@wordpress/blocks';
import {
	productFilterActive,
	productFilterAttribute,
	productFilterPrice,
	productFilterRating,
	productFilterStockStatus,
} from '@woocommerce/icons';

const variations: BlockVariation[] = [
	{
		name: 'product-filter-active',
		title: __( 'Active (Experimental)', 'woocommerce' ),
		description: __(
			'Display the currently active filters.',
			'woocommerce'
		),
		attributes: {
			heading: __( 'Active filters', 'woocommerce' ),
			filterType: 'active-filters',
		},
		icon: productFilterActive,
		isDefault: true,
	},
	{
		name: 'product-filter-price',
		title: __( 'Price (Experimental)', 'woocommerce' ),
		description: __(
			'Enable customers to filter the product collection by choosing a price range.',
			'woocommerce'
		),
		attributes: {
			filterType: 'price-filter',
			heading: __( 'Price', 'woocommerce' ),
		},
		icon: productFilterPrice,
	},
	{
		name: 'product-filter-stock-status',
		title: __( 'Status (Experimental)', 'woocommerce' ),
		description: __(
			'Enable customers to filter the product collection by stock status.',
			'woocommerce'
		),
		attributes: {
			filterType: 'stock-filter',
			heading: __( 'Status', 'woocommerce' ),
		},
		icon: productFilterStockStatus,
	},
	{
		name: 'product-filter-attribute',
		title: __( 'Attribute (Experimental)', 'woocommerce' ),
		description: __(
			'Enable customers to filter the product collection by selecting one or more attributes, such as color.',
			'woocommerce'
		),
		attributes: {
			filterType: 'attribute-filter',
			heading: __( 'Attribute', 'woocommerce' ),
			attributeId: 0,
		},
		icon: productFilterAttribute,
	},
	{
		name: 'product-filter-rating',
		title: __( 'Rating (Experimental)', 'woocommerce' ),
		description: __(
			'Enable customers to filter the product collection by rating.',
			'woocommerce'
		),
		attributes: {
			filterType: 'rating-filter',
			heading: __( 'Rating', 'woocommerce' ),
		},
		icon: productFilterRating,
	},
];

/**
 * Add `isActive` function to all Product Filter block variations.
 * `isActive` function is used to find a variation match from a created
 *  Block by providing its attributes.
 */
variations.forEach( ( variation ) => {
	// @ts-expect-error: `isActive` is currently typed wrong in `@wordpress/blocks`.
	variation.isActive = [ 'filterType' ];
} );

export const blockVariations = variations;
