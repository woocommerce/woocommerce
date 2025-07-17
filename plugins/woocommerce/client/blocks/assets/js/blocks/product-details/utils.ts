/**
 * External dependencies
 */
import {
	PartialProduct,
	ProductDimensions,
	ProductProductAttribute,
} from '@woocommerce/data';
import { __ } from '@wordpress/i18n';

const isAdditionalProductDataEmpty = ( product: PartialProduct ): boolean => {
	const isWeightEmpty = ( value: string | undefined ) => {
		return ! value || value.length === 0;
	};

	const isDimensionsEmpty = ( value: ProductDimensions | undefined ) => {
		return (
			! value ||
			Object.values( value ).every(
				( val ) => ! val || val.trim() === ''
			)
		);
	};

	const isAttributesEmpty = (
		value: ProductProductAttribute[] | undefined
	) => {
		return ! value || value.length === 0;
	};

	return (
		isWeightEmpty( product.weight ) &&
		isDimensionsEmpty( product.dimensions ) &&
		isAttributesEmpty( product.attributes )
	);
};

export const getTemplate = ( product: PartialProduct ) => {
	const additionalProductDataEmpty = isAdditionalProductDataEmpty( product );

	return [
		[
			'woocommerce/accordion-group',
			{
				metadata: {
					isDescendantOfProductDetails: true,
				},
			},
			[
				[
					'woocommerce/accordion-item',
					{
						openByDefault: true,
					},
					[
						[
							'woocommerce/accordion-header',
							{ title: __( 'Description', 'woocommerce' ) },
							[],
						],
						[
							'woocommerce/accordion-panel',
							{},
							[ [ 'woocommerce/product-description', {}, [] ] ],
						],
					],
				],
				...( ! additionalProductDataEmpty
					? [
							[
								'woocommerce/accordion-item',
								{},
								[
									[
										'woocommerce/accordion-header',
										{
											title: __(
												'Additional Information',
												'woocommerce'
											),
										},
										[],
									],
									[
										'woocommerce/accordion-panel',
										{},
										[
											[
												'woocommerce/product-specifications',
												{},
											],
										],
									],
								],
							],
					  ]
					: [] ),
				[
					'woocommerce/accordion-item',
					{},
					[
						[
							'woocommerce/accordion-header',
							{ title: __( 'Reviews', 'woocommerce' ) },
							[],
						],
						[
							'woocommerce/accordion-panel',
							{},
							[ [ 'woocommerce/product-reviews', {} ] ],
						],
					],
				],
			],
		],
	];
};
