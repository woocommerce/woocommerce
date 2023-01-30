/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ProductStatus } from '@woocommerce/data';

export type ProductError = {
	code: string;
	message: string;
	data: Record< string, unknown >;
};

export const getProductError = (
	error: ProductError,
	status: ProductStatus
) => {
	if ( error.code === 'product_invalid_sku' ) {
		return {
			content: __(
				'Product with this link already exists.',
				'woocommerce'
			),
			options: {
				explicitDismiss: true,
				actions: [
					{
						label: __( 'Edit link', 'woocommerce' ),
						onClick: () => {
							(
								document.querySelector(
									'#tab-panel-0-general'
								) as HTMLElement
							 )?.click();
							setTimeout( () => {
								(
									document.querySelector(
										'#wooocommerce-product-form__edit-product-link'
									) as HTMLElement
								 )?.click();
							}, 0 );
						},
					},
				],
			},
		};
	}

	if ( status === 'publish' ) {
		return {
			content: __( 'Failed to publish product.', 'woocommerce' ),
		};
	}

	return {
		content: __( 'Failed to create product.', 'woocommerce' ),
	};
};
