/**
 * External dependencies
 */
import { PartialProduct, ProductDimensions } from '@woocommerce/data';
import { isEmpty } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';

export const isAdditionalProductDataEmpty = (
	product: PartialProduct
): boolean => {
	const isDimensionsEmpty = ( value: ProductDimensions | undefined ) => {
		return (
			! value ||
			Object.values( value ).every(
				( val ) => ! val || val.trim() === ''
			)
		);
	};

	return (
		isEmpty( product.weight ) &&
		isDimensionsEmpty( product.dimensions ) &&
		isEmpty( product.attributes )
	);
};

export const getTemplate = (
	product: PartialProduct | null,
	{
		isInnerBlockOfSingleProductBlock,
	}: { isInnerBlockOfSingleProductBlock: boolean }
) => {
	const additionalProductDataEmpty =
		product !== null &&
		product !== undefined &&
		isAdditionalProductDataEmpty( product ) &&
		isInnerBlockOfSingleProductBlock;

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
