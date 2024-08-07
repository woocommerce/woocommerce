/**
 * External dependencies
 */
import { ProductStatus } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { WPError } from '../hooks/use-error-handler';

export function formatProductError(
	error: WPError,
	productStatus: ProductStatus
) {
	if ( error.code ) {
		return error;
	}

	const errorObj = Object.values( error ).find(
		( value ) => value !== undefined
	) as WPError | undefined;

	if ( 'variations' in error && error.variations ) {
		return {
			...errorObj,
			code: 'variable_product_no_variation_prices',
		};
	}

	if ( errorObj !== undefined ) {
		return {
			...errorObj,
			code: 'product_form_field_error',
		};
	}

	return {
		code:
			productStatus === 'publish' || productStatus === 'future'
				? 'product_publish_error'
				: 'product_create_error',
	};
}
