/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';
import {
	activeFiltersIcon,
	attributeFilterIcon,
	priceFilterIcon,
	ratingFilterIcon,
	stockStatusFilterIcon,
} from './icon';

const variations = [
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
			providerNameSlug: 'product-filter-active',
		},
		icon: {
			src: activeFiltersIcon,
		},
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
			heading: __( 'Filter by Price', 'woocommerce' ),
			providerNameSlug: 'product-filter-price',
		},
		icon: {
			src: priceFilterIcon,
		},
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
			heading: __( 'Filter by Stock Status', 'woocommerce' ),
			providerNameSlug: 'product-filter-stock-status',
		},
		icon: {
			src: stockStatusFilterIcon,
		},
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
			heading: __( 'Filter by Attribute', 'woocommerce' ),
			attributeId: 0,
			providerNameSlug: 'product-filter-attribute',
		},
		icon: {
			src: attributeFilterIcon,
		},
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
			heading: __( 'Filter by Rating', 'woocommerce' ),
			providerNameSlug: 'product-filter-rating',
		},
		icon: {
			src: ratingFilterIcon,
		},
	},
];

type Variation = ( typeof variations )[ 0 ] & {
	isActive: (
		blockAttributes: BlockAttributes,
		variationAttributes: BlockAttributes
	) => boolean;
};

/**
 * Add `isActive` function to all `embed` variations, if not defined.
 * `isActive` function is used to find a variation match from a created
 *  Block by providing its attributes.
 */
( variations as Variation[] ).forEach( ( variation ) => {
	variation.isActive = (
		blockAttributes: BlockAttributes,
		variationAttributes: BlockAttributes
	) =>
		blockAttributes.providerNameSlug ===
		variationAttributes.providerNameSlug;
} );

export const blockVariations = variations;
