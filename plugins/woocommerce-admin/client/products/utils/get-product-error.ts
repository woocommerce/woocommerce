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
							// Navigate to general tab.
							// Use context to open modal.
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
