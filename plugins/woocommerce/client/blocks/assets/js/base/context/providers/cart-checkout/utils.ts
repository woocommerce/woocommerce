/**
 * External dependencies
 */
import triggerFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { checkoutStore } from '@woocommerce/block-data';
import { responseTypes } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	noticeContexts,
	PAYMENT_METHOD_REQUIRED_NOTICE_ID,
} from '../../event-emit';

/**
 * Checks that a payment method is selected when the order needs one.
 *
 * Only runs when the store doesn't preselect a payment method and the shopper has
 * a list to pick from; otherwise the server validates the submission as it always has.
 * Returns true when the checkout can proceed, or an error response for the
 * payment section when the shopper still has to pick a method. The notice is
 * pinned to an id so the option lists can remove it once a method is picked.
 */
export const validatePaymentMethodSelection = ( {
	preselectPaymentMethod,
	hasPaymentMethods,
	cartNeedsPayment,
	activePaymentMethod,
	isExpressPaymentMethodActive,
}: {
	preselectPaymentMethod: boolean;
	hasPaymentMethods: boolean;
	cartNeedsPayment: boolean;
	activePaymentMethod: string;
	isExpressPaymentMethodActive: boolean;
} ) => {
	if (
		preselectPaymentMethod ||
		! hasPaymentMethods ||
		! cartNeedsPayment ||
		isExpressPaymentMethodActive ||
		activePaymentMethod
	) {
		return true;
	}
	return {
		type: responseTypes.ERROR,
		errorMessage: __( 'Please select a payment method.', 'woocommerce' ),
		context: noticeContexts.PAYMENTS,
		id: PAYMENT_METHOD_REQUIRED_NOTICE_ID,
	};
};

/**
 * Utility function for preparing payment data for the request.
 */
export const preparePaymentData = (
	//Arbitrary payment data provided by the payment method.
	paymentData: Record< string, unknown >,
	//Whether to save the payment method info to user account.
	shouldSave: boolean,
	//The current active payment method.
	activePaymentMethod: string
): { key: string; value: unknown }[] => {
	const apiData = Object.keys( paymentData ).map( ( property ) => {
		const value = paymentData[ property ];
		return { key: property, value };
	}, [] );
	const savePaymentMethodKey = `wc-${ activePaymentMethod }-new-payment-method`;
	apiData.push( {
		key: savePaymentMethodKey,
		value: shouldSave,
	} );
	return apiData;
};

/**
 * Process headers from an API response and dispatch updates.
 */
export const processCheckoutResponseHeaders = (
	headers: Headers | undefined
): void => {
	if ( ! headers ) {
		return;
	}
	const { __internalSetCustomerId } = dispatch( checkoutStore );

	if (
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-nonce.
		triggerFetch.setNonce &&
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-nonce.
		typeof triggerFetch.setNonce === 'function'
	) {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-nonce.
		triggerFetch.setNonce( headers );
	}

	if (
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-cart-hash.
		triggerFetch.setCartHash &&
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-cart-hash.
		typeof triggerFetch.setCartHash === 'function'
	) {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-cart-hash.
		triggerFetch.setCartHash( headers );
	}

	// Update user using headers.
	if ( headers?.get( 'User-ID' ) ) {
		void __internalSetCustomerId(
			parseInt( headers.get( 'User-ID' ) || '0', 10 )
		);
	}
};
