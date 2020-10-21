/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').EmitResponseProps} EmitResponseProps
 * @typedef {import('../stripe-utils/type-defs').Stripe} Stripe
 */

/**
 * Opens the modal for PaymentIntent authorizations.
 *
 * @param {Stripe}           stripe         The stripe object.
 * @param {Object}           paymentDetails The payment details from the server after checkout
 *                                          processing.
 * @param {EmitResponseProps} emitResponse  Various helpers for usage with observer response
 *                                          objects.
 */
const openIntentModal = ( stripe, paymentDetails, emitResponse ) => {
	const checkoutResponse = { type: emitResponse.responseTypes.SUCCESS };
	if (
		! paymentDetails.setup_intent &&
		! paymentDetails.payment_intent_secret
	) {
		return checkoutResponse;
	}
	const isSetupIntent = !! paymentDetails.setupIntent;
	const verificationUrl = paymentDetails.verification_endpoint;
	const intentSecret = isSetupIntent
		? paymentDetails.setup_intent
		: paymentDetails.payment_intent_secret;
	return stripe[ isSetupIntent ? 'confirmCardSetup' : 'confirmCardPayment' ](
		intentSecret
	)
		.then( function ( response ) {
			if ( response.error ) {
				throw response.error;
			}
			const intent =
				response[ isSetupIntent ? 'setupIntent' : 'paymentIntent' ];
			if (
				intent.status !== 'requires_capture' &&
				intent.status !== 'succeeded'
			) {
				return checkoutResponse;
			}
			checkoutResponse.redirectUrl = verificationUrl;
			return checkoutResponse;
		} )
		.catch( function ( error ) {
			checkoutResponse.type = emitResponse.responseTypes.ERROR;
			checkoutResponse.message = error.message;
			checkoutResponse.retry = true;
			checkoutResponse.messageContext =
				emitResponse.noticeContexts.PAYMENTS;
			// Reports back to the server.
			window.fetch( verificationUrl + '&is_ajax' );
			return checkoutResponse;
		} );
};

export const usePaymentIntents = ( stripe, subscriber, emitResponse ) => {
	useEffect( () => {
		const unsubscribe = subscriber( ( { processingResponse } ) => {
			const paymentDetails = processingResponse.paymentDetails || {};
			return openIntentModal( stripe, paymentDetails, emitResponse );
		} );
		return () => unsubscribe();
	}, [ subscriber, stripe ] );
};
