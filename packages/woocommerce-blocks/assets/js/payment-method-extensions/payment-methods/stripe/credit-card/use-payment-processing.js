/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import {
	CardElement,
	CardNumberElement,
	useElements,
} from '@stripe/react-stripe-js';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';
import {
	getStripeServerData,
	getErrorMessageForTypeAndCode,
} from '../stripe-utils';
import { errorTypes } from '../stripe-utils/constants';

/**
 * @typedef {import('@stripe/stripe-js').Stripe} Stripe
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').EventRegistrationProps} EventRegistrationProps
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').BillingDataProps} BillingDataProps
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').EmitResponseProps} EmitResponseProps
 * @typedef {import('react').Dispatch<string>} SourceIdDispatch
 */

/**
 * @typedef {function(function():any):function():void} EventRegistration
 */

/**
 * A custom hook that registers stripe payment processing with the
 * onPaymentProcessing event from checkout.
 *
 * @param {function(any):string} onStripeError       Sets an error for stripe.
 * @param {string}               error               Any set error message (an empty string if no
 *                                                   error).
 * @param {Stripe}               stripe              The stripe utility
 * @param {BillingDataProps}     billing             Various billing data items.
 * @param {EmitResponseProps}    emitResponse        Various helpers for usage with observer
 *                                                   response objects.
 * @param {string}               sourceId            Current set stripe source id.
 * @param {SourceIdDispatch}     setSourceId         Setter for stripe source id.
 * @param {EventRegistration}    onPaymentProcessing The event emitter for processing payment.
 */
export const usePaymentProcessing = (
	onStripeError,
	error,
	stripe,
	billing,
	emitResponse,
	sourceId,
	setSourceId,
	onPaymentProcessing
) => {
	const elements = useElements();
	// hook into and register callbacks for events
	useEffect( () => {
		const createSource = async ( ownerInfo ) => {
			const elementToGet = getStripeServerData().inline_cc_form
				? CardElement
				: CardNumberElement;
			return await stripe.createSource(
				// @ts-ignore
				elements?.getElement( elementToGet ),
				{
					type: 'card',
					owner: ownerInfo,
				}
			);
		};
		const onSubmit = async () => {
			try {
				const billingData = billing.billingData;
				// if there's an error return that.
				if ( error ) {
					return {
						type: emitResponse.responseTypes.ERROR,
						message: error,
					};
				}
				// use token if it's set.
				if ( sourceId !== '' && sourceId !== '0' ) {
					return {
						type: emitResponse.responseTypes.SUCCESS,
						meta: {
							paymentMethodData: {
								paymentMethod: PAYMENT_METHOD_NAME,
								paymentRequestType: 'cc',
								stripe_source: sourceId,
							},
							billingData,
						},
					};
				}
				const ownerInfo = {
					address: {
						line1: billingData.address_1,
						line2: billingData.address_2,
						city: billingData.city,
						state: billingData.state,
						postal_code: billingData.postcode,
						country: billingData.country,
					},
				};
				if ( billingData.phone ) {
					ownerInfo.phone = billingData.phone;
				}
				if ( billingData.email ) {
					ownerInfo.email = billingData.email;
				}
				if ( billingData.first_name || billingData.last_name ) {
					ownerInfo.name = `${ billingData.first_name } ${ billingData.last_name }`;
				}

				const response = await createSource( ownerInfo );
				if ( response.error ) {
					return {
						type: emitResponse.responseTypes.ERROR,
						message: onStripeError( response ),
					};
				}
				if ( ! response.source || ! response.source.id ) {
					throw new Error(
						getErrorMessageForTypeAndCode( errorTypes.API_ERROR )
					);
				}
				setSourceId( response.source.id );
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							stripe_source: response.source.id,
							paymentMethod: PAYMENT_METHOD_NAME,
							paymentRequestType: 'cc',
						},
						billingData,
					},
				};
			} catch ( e ) {
				return {
					type: emitResponse.responseTypes.ERROR,
					message: e,
				};
			}
		};
		const unsubscribeProcessing = onPaymentProcessing( onSubmit );
		return () => {
			unsubscribeProcessing();
		};
	}, [
		onPaymentProcessing,
		billing.billingData,
		stripe,
		sourceId,
		setSourceId,
		onStripeError,
		error,
		emitResponse.noticeContexts.PAYMENTS,
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
		elements,
	] );
};
