/**
 * External dependencies
 */
import { useEffect, useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getErrorMessageForTypeAndCode } from '../stripe-utils';
import { usePaymentIntents } from './use-payment-intents';
import { usePaymentProcessing } from './use-payment-processing';

/**
 * @typedef {import('@woocommerce/type-defs/payment-method-interface').EventRegistrationProps} EventRegistrationProps
 * @typedef {import('@woocommerce/type-defs/payment-method-interface').BillingDataProps} BillingDataProps
 * @typedef {import('@woocommerce/type-defs/payment-method-interface').EmitResponseProps} EmitResponseProps
 * @typedef {import('../stripe-utils/type-defs').Stripe} Stripe
 * @typedef {import('react').Dispatch<string>} SourceIdDispatch
 */

/**
 * A custom hook for the Stripe processing and event observer logic.
 *
 * @param {EventRegistrationProps} eventRegistration Event registration functions.
 * @param {BillingDataProps}       billing           Various billing data items.
 * @param {string}                 sourceId          Current set stripe source id.
 * @param {SourceIdDispatch}       setSourceId       Setter for stripe source id.
 * @param {EmitResponseProps}      emitResponse      Various helpers for usage with observer
 *                                                   response objects.
 * @param {Stripe}                 stripe            The stripe.js object.
 *
 * @return {function(Object):Object} Returns a function for handling stripe error.
 */
export const useCheckoutSubscriptions = (
	eventRegistration,
	billing,
	sourceId,
	setSourceId,
	emitResponse,
	stripe
) => {
	const [ error, setError ] = useState( '' );
	const onStripeError = useCallback( ( event ) => {
		const type = event.error.type;
		const code = event.error.code || '';
		const message =
			getErrorMessageForTypeAndCode( type, code ) ?? event.error.message;
		setError( message );
		return message;
	}, [] );
	const {
		onCheckoutAfterProcessingWithSuccess,
		onPaymentProcessing,
		onCheckoutAfterProcessingWithError,
	} = eventRegistration;
	usePaymentIntents(
		stripe,
		onCheckoutAfterProcessingWithSuccess,
		setSourceId,
		emitResponse
	);
	usePaymentProcessing(
		onStripeError,
		error,
		stripe,
		billing,
		emitResponse,
		sourceId,
		setSourceId,
		onPaymentProcessing
	);
	// hook into and register callbacks for events.
	useEffect( () => {
		const onError = ( { processingResponse } ) => {
			if ( processingResponse?.paymentDetails?.errorMessage ) {
				return {
					type: emitResponse.responseTypes.ERROR,
					message: processingResponse.paymentDetails.errorMessage,
					messageContext: emitResponse.noticeContexts.PAYMENTS,
				};
			}
			// so we don't break the observers.
			return true;
		};
		const unsubscribeAfterProcessing = onCheckoutAfterProcessingWithError(
			onError
		);
		return () => {
			unsubscribeAfterProcessing();
		};
	}, [
		onCheckoutAfterProcessingWithError,
		emitResponse.noticeContexts.PAYMENTS,
		emitResponse.responseTypes.ERROR,
	] );
	return onStripeError;
};
