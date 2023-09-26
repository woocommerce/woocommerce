/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export type WPErrorCode =
	| 'variable_product_no_variation_prices'
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

export function getProductErrorMessage( error: WPError ) {
	switch ( error.code ) {
		case 'variable_product_no_variation_prices':
			return error.message;
		case 'product_invalid_sku':
			return __( 'Invalid or duplicated SKU.', 'woocommerce' );
		case 'product_create_error':
			return __( 'Failed to create product.', 'woocommerce' );
		case 'product_publish_error':
			return __( 'Failed to publish product.', 'woocommerce' );
		case 'product_preview_error':
			return __( 'Failed to preview product.', 'woocommerce' );
		default:
			return __( 'Failed to save product.', 'woocommerce' );
	}
}
