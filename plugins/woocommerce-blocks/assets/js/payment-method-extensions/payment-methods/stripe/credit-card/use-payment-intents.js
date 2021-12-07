/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * @typedef {import('@woocommerce/type-defs/payment-method-interface').EmitResponseProps} EmitResponseProps
 * @typedef {import('../stripe-utils/type-defs').Stripe} Stripe
 */

/**
 * Opens the modal for PaymentIntent authorizations.
 *
 * @param {Object}           params                Params object.
 * @param {Stripe}           params.stripe         The stripe object.
 * @param {Object}           params.paymentDetails The payment details from the
 *                                                 server after checkout processing.
 * @param {string}           params.errorContext   Context where errors will be added.
 * @param {string}           params.errorType      Type of error responses.
 * @param {string}           params.successType    Type of success responses.
 */
const openIntentModal = ( {
	stripe,
	paymentDetails,
	errorContext,
	errorType,
	successType,
} ) => {
	const checkoutResponse = { type: successType };
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
			checkoutResponse.type = errorType;
			checkoutResponse.message = error.message;
			checkoutResponse.retry = true;
			checkoutResponse.messageContext = errorContext;
			// Reports back to the server.
			window.fetch( verificationUrl + '&is_ajax' );
			return checkoutResponse;
		} );
};

export const usePaymentIntents = (
	stripe,
	subscriber,
	setSourceId,
	emitResponse
) => {
	useEffect( () => {
		const unsubscribe = subscriber( async ( { processingResponse } ) => {
			const paymentDetails = processingResponse.paymentDetails || {};
			const response = await openIntentModal( {
				stripe,
				paymentDetails,
				errorContext: emitResponse.noticeContexts.PAYMENTS,
				errorType: emitResponse.responseTypes.ERROR,
				successType: emitResponse.responseTypes.SUCCESS,
			} );
			if (
				response.type === emitResponse.responseTypes.ERROR &&
				response.retry
			) {
				setSourceId( '0' );
			}

			return response;
		} );
		return () => unsubscribe();
	}, [
		subscriber,
		emitResponse.noticeContexts.PAYMENTS,
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
		setSourceId,
		stripe,
	] );
};
