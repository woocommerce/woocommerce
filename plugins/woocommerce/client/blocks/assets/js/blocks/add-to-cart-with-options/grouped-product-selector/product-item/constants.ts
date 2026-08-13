/**
 * External dependencies
 */
import type { TemplateArray } from '@wordpress/blocks';

/**
 * Template definition for Grouped Product Item Template in the Add to Cart form.
 */
export const GROUPED_PRODUCT_ITEM_TEMPLATE: TemplateArray = [
	[
		'woocommerce/add-to-cart-with-options-grouped-product-item',
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
						'woocommerce/add-to-cart-with-options-grouped-product-item-selector',
					],
					[
						'woocommerce/add-to-cart-with-options-grouped-product-item-label',
						{
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
						},
					],
					[
						'core/group',
						{
							layout: {
								type: 'flex',
								orientation: 'vertical',
								justifyContent: 'right',
							},
							style: {
								spacing: {
									blockGap: '0',
								},
							},
						},
						[
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
							[ 'woocommerce/product-stock-indicator' ],
						],
					],
				],
			],
		],
	],
];
