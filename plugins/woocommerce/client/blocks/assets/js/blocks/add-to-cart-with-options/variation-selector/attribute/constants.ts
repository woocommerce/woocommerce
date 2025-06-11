/**
 * External dependencies
 */
import type { TemplateArray } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

export const ATTRIBUTE_ITEM_TEMPLATE: TemplateArray = [
	[
		'woocommerce/add-to-cart-with-options-variation-selector-attribute',
		{},
		[
			[
				'core/group',
				{
					layout: {
						type: 'flex',
						orientation: 'vertical',
						flexWrap: 'nowrap',
					},
					style: {
						spacing: {
							blockGap: '0.5rem',
							margin: {
								top: '1rem',
								bottom: '1rem',
							},
						},
					},
				},
				[
					[
						'woocommerce/add-to-cart-with-options-variation-selector-attribute-name',
						{
							fontSize: 'medium',
						},
					],
					[
						'woocommerce/add-to-cart-with-options-variation-selector-attribute-options',
					],
				],
			],
		],
	],
] as const;

export const DEFAULT_ATTRIBUTES = [
	{
		id: 1,
		taxonomy: 'pa_color',
		name: __( 'Color', 'woocommerce' ),
		has_variations: true,
		terms: [
			{ id: 1, slug: 'blue', name: __( 'Blue', 'woocommerce' ) },
			{ id: 2, slug: 'red', name: __( 'Red', 'woocommerce' ) },
			{ id: 3, slug: 'green', name: __( 'Green', 'woocommerce' ) },
		],
	},
	{
		id: 2,
		taxonomy: 'pa_size',
		name: __( 'Size', 'woocommerce' ),
		has_variations: true,
		terms: [
			{ id: 1, slug: 'sm', name: __( 'Small', 'woocommerce' ) },
			{ id: 2, slug: 'md', name: __( 'Medium', 'woocommerce' ) },
			{ id: 3, slug: 'lg', name: __( 'Large', 'woocommerce' ) },
		],
	},
] as const;
