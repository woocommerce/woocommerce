/**
 * External dependencies
 */
import type { TemplateArray } from '@wordpress/blocks';

export const GROUPED_PRODUCT_ITEM_TEMPLATE: TemplateArray = [
	[
		'woocommerce/add-to-cart-with-options-grouped-product-selector-item',
		{},
		[
			[
				'core/group',
				{
					layout: {
						type: 'flex',
						orientation: 'horizontal',
						flexWrap: 'nowrap',
					},
					style: {
						spacing: {
							margin: {
								top: '1rem',
								bottom: '1rem',
							},
						},
					},
				},
				[
					[
						'woocommerce/add-to-cart-with-options-grouped-product-selector-item-cta',
					],
					[
						'core/post-title',
						{
							level: 2,
							fontSize: 'medium',
							style: {
								layout: {
									selfStretch: 'fill',
								},
								spacing: {
									margin: {
										top: '0',
										bottom: '0',
									},
								},
								typography: {
									fontWeight: 400,
								},
							},
							isLink: true,
						},
					],
					[
						'woocommerce/product-price',
						{
							isDescendentOfSingleProductBlock: true,
							fontSize: 'medium',
							textAlign: 'right',
							style: {
								typography: {
									fontWeight: 400,
								},
							},
						},
					],
				],
			],
		],
	],
];
