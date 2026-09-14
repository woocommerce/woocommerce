/**
 * External dependencies
 */
import { WP_REST_API_Category } from 'wp-types';
import { ProductResponseItem } from '@woocommerce/types';
import { InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { VARIATION_NAME as PRODUCT_TITLE_VARIATION_NAME } from '../product-collection/variations/elements/product-title';

export const DEFAULT_EDITOR_SIZE = {
	height: 500,
	width: 500,
} as const;

export const BLOCK_NAMES = {
	featuredCategory: 'woocommerce/featured-category',
	featuredProduct: 'woocommerce/featured-product',
} as const;

export const FEATURED_CATEGORY_DEFAULT_TEMPLATE = (
	category: WP_REST_API_Category
): InnerBlockTemplate[] => [
	[ 'woocommerce/category-title', { level: 2, textAlign: 'center' } ],
	[ 'woocommerce/category-description', { textAlign: 'center' } ],
	[
		'core/buttons',
		{
			layout: {
				type: 'flex',
				justifyContent: 'center',
			},
		},
		[
			[
				'core/button',
				{
					text: __( 'Shop now', 'woocommerce' ),
					url: category.permalink,
				},
			],
		],
	],
];

export const FEATURED_PRODUCT_DEFAULT_TEMPLATE = (
	product: ProductResponseItem
): InnerBlockTemplate[] => [
	[
		'core/post-title',
		{
			isLink: true,
			level: 2,
			textAlign: 'center',
			__woocommerceNamespace: PRODUCT_TITLE_VARIATION_NAME,
		},
	],
	[
		'woocommerce/product-summary',
		{
			showDescriptionIfEmpty: true,
			style: {
				typography: {
					textAlign: 'center',
				},
			},
			summaryLength: 80,
		},
	],
	[
		'woocommerce/product-price',
		{
			style: {
				spacing: {
					padding: {
						bottom: '16px',
					},
				},
			},
			textAlign: 'center',
		},
	],
	[
		'core/buttons',
		{
			layout: {
				type: 'flex',
				justifyContent: 'center',
			},
		},
		[
			[
				'core/button',
				{
					text: __( 'Shop now', 'woocommerce' ),
					url: product.permalink,
				},
			],
		],
	],
];
