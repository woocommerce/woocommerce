/**
 * External dependencies
 */
import { PartialProduct, ProductDimensions } from '@woocommerce/data';
import { isWpVersion } from '@woocommerce/settings';
import { isEmpty } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';

/**
 * Get accordion block names based on WordPress version
 */
const getAccordionBlockNames = () => {
	if ( isWpVersion( '6.9', '>=' ) ) {
		return {
			group: 'core/accordion',
			item: 'core/accordion-item',
			header: 'core/accordion-heading',
			panel: 'core/accordion-panel',
		};
	}
	return {
		group: 'woocommerce/accordion-group',
		item: 'woocommerce/accordion-item',
		header: 'woocommerce/accordion-header',
		panel: 'woocommerce/accordion-panel',
	};
};

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

	const blockNames = getAccordionBlockNames();

	return [
		[
			blockNames.group,
			{
				metadata: {
					isDescendantOfProductDetails: true,
				},
			},
			[
				[
					blockNames.item,
					{
						openByDefault: true,
					},
					[
						[
							blockNames.header,
							{ title: __( 'Description', 'woocommerce' ) },
							[],
						],
						[
							blockNames.panel,
							{},
							[ [ 'woocommerce/product-description', {}, [] ] ],
						],
					],
				],
				...( ! additionalProductDataEmpty
					? [
							[
								blockNames.item,
								{},
								[
									[
										blockNames.header,
										{
											title: __(
												'Additional Information',
												'woocommerce'
											),
										},
										[],
									],
									[
										blockNames.panel,
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
					blockNames.item,
					{},
					[
						[
							blockNames.header,
							{ title: __( 'Reviews', 'woocommerce' ) },
							[],
						],
						[
							blockNames.panel,
							{},
							[ [ 'woocommerce/product-reviews', {} ] ],
						],
					],
				],
			],
		],
	];
};
