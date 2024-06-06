/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export type WPErrorCode =
	| 'variable_product_no_variation_prices'
	| 'product_form_field_error'
	| 'product_invalid_sku'
	| 'product_create_error'
	| 'product_publish_error'
	| 'product_preview_error';

export type WPError = {
	code: WPErrorCode;
	message: string;
	data: {
		[ key: string ]: unknown;
	};
};

export function getProductErrorMessageAndProps(
	error: WPError,
	visibleTab: string | null
): {
	message: string;
	errorProps: { explicitDismiss: boolean };
} {
	const response = {
		message: '',
		errorProps: {
			explicitDismiss: false,
		},
	};
	switch ( error.code ) {
		case 'variable_product_no_variation_prices':
			response.message = error.message;
			response.errorProps.explicitDismiss = visibleTab !== 'variations';
			break;
		case 'product_form_field_error':
			response.message = error.message;
			response.errorProps.explicitDismiss = visibleTab !== 'general';
			break;
		case 'product_invalid_sku':
			response.message = __(
				'Invalid or duplicated SKU.',
				'woocommerce'
			);
			response.errorProps.explicitDismiss = visibleTab !== 'inventory';
			break;
		case 'product_create_error':
			response.message = __( 'Failed to create product.', 'woocommerce' );
			response.errorProps.explicitDismiss = false;
			break;
		case 'product_publish_error':
			response.message = __(
				'Failed to publish product.',
				'woocommerce'
			);
			response.errorProps.explicitDismiss = false;
			break;
		case 'product_preview_error':
			response.message = __(
				'Failed to preview product.',
				'woocommerce'
			);
			response.errorProps.explicitDismiss = false;
			break;
		default:
			response.message = __( 'Failed to save product.', 'woocommerce' );
			response.errorProps.explicitDismiss = false;
			break;
	}
	return response;
}
