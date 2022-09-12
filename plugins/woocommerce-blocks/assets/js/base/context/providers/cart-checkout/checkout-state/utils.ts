/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import type { PaymentResultDataType, CheckoutResponse } from './types';

/**
 * Prepares the payment_result data from the server checkout endpoint response.
 */
export const getPaymentResultFromCheckoutResponse = (
	response: CheckoutResponse
): PaymentResultDataType => {
	const paymentResult = {
		message: '',
		paymentStatus: '',
		redirectUrl: '',
		paymentDetails: {},
	} as PaymentResultDataType;

	// payment_result is present in successful responses.
	if ( 'payment_result' in response ) {
		paymentResult.paymentStatus = response.payment_result.payment_status;
		paymentResult.redirectUrl = response.payment_result.redirect_url;

		if (
			response.payment_result.hasOwnProperty( 'payment_details' ) &&
			Array.isArray( response.payment_result.payment_details )
		) {
			response.payment_result.payment_details.forEach(
				( { key, value }: { key: string; value: string } ) => {
					paymentResult.paymentDetails[ key ] =
						decodeEntities( value );
				}
			);
		}
	}

	// message is present in error responses.
	if ( 'message' in response ) {
		paymentResult.message = decodeEntities( response.message );
	}

	// If there was an error code but no message, set a default message.
	if (
		! paymentResult.message &&
		'data' in response &&
		'status' in response.data &&
		response.data.status > 299
	) {
		paymentResult.message = __(
			'Something went wrong. Please contact us for assistance.',
			'woo-gutenberg-products-block'
		);
	}

	return paymentResult;
};
