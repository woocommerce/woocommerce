/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { CardElement, CardNumberElement } from '@stripe/react-stripe-js';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';
import {
	getStripeServerData,
	getErrorMessageForTypeAndCode,
} from '../../stripe-utils';

/**
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').EventRegistrationProps} EventRegistrationProps
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').PaymentStatusProps} PaymentStatusProps
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').BillingDataProps} BillingDataProps
 * @typedef {import('../../stripe-utils/type-defs').Stripe} Stripe
 */

/**
 * A custom hook for the Stripe processing and event observer logic.
 *
 * @param {EventRegistrationProps}     eventRegistration Event registration
 *                                                       functions.
 * @param {PaymentStatusProps}         paymentStatus     Various payment status
 *                                                       helpers.
 * @param {BillingDataProps}           billing           Various billing data
 *                                                       items.
 * @param {number}                     sourceId          Current set stripe
 *                                                       source id.
 * @param {function(number):undefined} setSourceId       Setter for stripe
 *                                                       source id.
 * @param {boolean}                    shouldSavePayment Whether to save the
 *                                                       payment or not.
 * @param {Stripe}                     stripe            The stripe.js object.
 * @param {Object}                     elements          Stripe Elements object.
 *
 * @return {function(Object):Object} Returns a function for handling stripe error.
 */
export const useCheckoutSubscriptions = (
	eventRegistration,
	paymentStatus,
	billing,
	sourceId,
	setSourceId,
	shouldSavePayment,
	stripe,
	elements
) => {
	const onStripeError = useRef( ( event ) => {
		return event;
	} );
	// hook into and register callbacks for events.
	useEffect( () => {
		onStripeError.current = ( event ) => {
			const type = event.error.type;
			const code = event.error.code || '';
			let message = getErrorMessageForTypeAndCode( type, code );
			message = message || event.error.message;
			paymentStatus.setPaymentStatus().error( message );
			return {};
		};
		const createSource = async ( ownerInfo ) => {
			const elementToGet = getStripeServerData().inline_cc_form
				? CardElement
				: CardNumberElement;
			return await stripe.createSource(
				elements.getElement( elementToGet ),
				{
					type: 'card',
					owner: ownerInfo,
				}
			);
		};
		const onSubmit = async () => {
			try {
				paymentStatus.setPaymentStatus().processing();
				const { billingData } = billing;
				// use token if it's set.
				if ( sourceId !== 0 ) {
					paymentStatus.setPaymentStatus().success( billingData, {
						paymentMethod: PAYMENT_METHOD_NAME,
						paymentRequestType: 'cc',
						sourceId,
						shouldSavePayment,
					} );
					return true;
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
					return onStripeError.current( response );
				}
				paymentStatus.setPaymentStatus().success( billingData, {
					sourceId: response.source.id,
					paymentMethod: PAYMENT_METHOD_NAME,
					paymentRequestType: 'cc',
					shouldSavePayment,
				} );
				setSourceId( response.source.id );
				return true;
			} catch ( e ) {
				paymentStatus.setPaymentStatus().error( e );
				return e;
			}
		};
		const onComplete = () => {
			paymentStatus.setPaymentStatus().completed();
		};
		const onError = () => {
			paymentStatus.setPaymentStatus().started();
		};
		const unsubscribeProcessing = eventRegistration.onCheckoutProcessing(
			onSubmit
		);
		const unsubscribeCheckoutComplete = eventRegistration.onCheckoutCompleteSuccess(
			onComplete
		);
		const unsubscribeCheckoutCompleteError = eventRegistration.onCheckoutCompleteError(
			onError
		);
		return () => {
			unsubscribeProcessing();
			unsubscribeCheckoutComplete();
			unsubscribeCheckoutCompleteError();
		};
	}, [
		eventRegistration.onCheckoutProcessing,
		eventRegistration.onCheckoutCompleteSuccess,
		eventRegistration.onCheckoutCompleteError,
		paymentStatus.setPaymentStatus,
		stripe,
		sourceId,
		billing.billingData,
		setSourceId,
		shouldSavePayment,
	] );
	return onStripeError.current;
};
