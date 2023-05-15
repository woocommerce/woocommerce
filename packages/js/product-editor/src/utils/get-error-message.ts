/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export type WPError = {
	code: string;
	message: string;
	data: {
		[ key: string ]: unknown;
	};
};

export function getErrorMessage( error: WPError ) {
	if ( error.code === 'product_invalid_sku' ) {
		return __( 'Invalid or duplicated SKU.', 'woocommerce' );
	}

	return null;
}
