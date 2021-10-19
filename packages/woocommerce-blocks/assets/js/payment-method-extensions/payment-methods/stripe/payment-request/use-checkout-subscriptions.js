/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	normalizeShippingOptions,
	getTotalPaymentItem,
	normalizeLineItems,
	getBillingData,
	getPaymentMethodData,
	getShippingData,
} from '../stripe-utils';

/**
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').EventRegistrationProps} EventRegistrationProps
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').BillingDataProps} BillingDataProps
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').ShippingDataProps} ShippingDataProps
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').EmitResponseProps} EmitResponseProps
 */

/**
 * @param {Object} props
 *
 * @param {boolean}                props.canMakePayment                  Whether the payment request
 *                                                                       can make payment or not.
 * @param {boolean}                props.isProcessing                    Whether the express payment
 *                                                                       method is processing or not.
 * @param {EventRegistrationProps} props.eventRegistration               Various functions for
 *                                                                       registering observers to
 *                                                                       events.
 * @param {Object}                 props.paymentRequestEventHandlers     Cached handlers registered
 *                                                                       for paymentRequest events.
 * @param {function(string):void}  props.clearPaymentRequestEventHandler Clears the cached payment
 *                                                                       request event handler.
 * @param {BillingDataProps}       props.billing
 * @param {ShippingDataProps}      props.shippingData
 * @param {EmitResponseProps}      props.emitResponse
 * @param {string}                 props.paymentRequestType              The derived payment request
 *                                                                       type for the express
 *                                                                       payment being processed.
 * @param {function(any):void}     props.completePayment                 This is a callback
 *                                                                       receiving the source event
 *                                                                       and setting it to
 *                                                                       successful payment.
 * @param {function(any,string):any}     props.abortPayment                    This is a callback
 *                                                                       receiving the source
 *                                                                       event and setting it to
 *                                                                       failed payment.
 */
export const useCheckoutSubscriptions = ( {
	canMakePayment,
	isProcessing,
	eventRegistration,
	paymentRequestEventHandlers,
	clearPaymentRequestEventHandler,
	billing,
	shippingData,
	emitResponse,
	paymentRequestType,
	completePayment,
	abortPayment,
} ) => {
	const {
		onShippingRateSuccess,
		onShippingRateFail,
		onShippingRateSelectSuccess,
		onShippingRateSelectFail,
		onPaymentProcessing,
		onCheckoutAfterProcessingWithSuccess,
		onCheckoutAfterProcessingWithError,
	} = eventRegistration;
	const { noticeContexts, responseTypes } = emitResponse;
	const eventHandlers = useRef( paymentRequestEventHandlers );
	const currentBilling = useRef( billing );
	const currentShipping = useRef( shippingData );
	const currentPaymentRequestType = useRef( paymentRequestType );

	useEffect( () => {
		eventHandlers.current = paymentRequestEventHandlers;
		currentBilling.current = billing;
		currentShipping.current = shippingData;
		currentPaymentRequestType.current = paymentRequestType;
	}, [
		paymentRequestEventHandlers,
		billing,
		shippingData,
		paymentRequestType,
	] );

	// subscribe to events.
	useEffect( () => {
		const onShippingRatesEvent = ( shippingRates ) => {
			const handlers = eventHandlers.current;
			const billingData = currentBilling.current;
			if ( handlers.shippingAddressChange && isProcessing ) {
				handlers.shippingAddressChange.updateWith( {
					status: 'success',
					shippingOptions: normalizeShippingOptions( shippingRates ),
					total: getTotalPaymentItem( billingData.cartTotal ),
					displayItems: normalizeLineItems(
						billingData.cartTotalItems
					),
				} );
				clearPaymentRequestEventHandler( 'shippingAddressChange' );
			}
		};
		const onShippingRatesEventFail = ( currentErrorStatus ) => {
			const handlers = eventHandlers.current;
			if ( handlers.shippingAddressChange && isProcessing ) {
				handlers.shippingAddressChange.updateWith( {
					status: currentErrorStatus.hasInvalidAddress
						? 'invalid_shipping_address'
						: 'fail',
					shippingOptions: [],
				} );
			}
			clearPaymentRequestEventHandler( 'shippingAddressChange' );
		};
		const onShippingSelectedRate = ( forSuccess = true ) => () => {
			const handlers = eventHandlers.current;
			const shipping = currentShipping.current;
			const billingData = currentBilling.current;
			if (
				handlers.shippingOptionChange &&
				! shipping.isSelectingRate &&
				isProcessing
			) {
				const updateObject = forSuccess
					? {
							status: 'success',
							total: getTotalPaymentItem( billingData.cartTotal ),
							displayItems: normalizeLineItems(
								billingData.cartTotalItems
							),
					  }
					: {
							status: 'fail',
					  };
				handlers.shippingOptionChange.updateWith( updateObject );
				clearPaymentRequestEventHandler( 'shippingOptionChange' );
			}
		};
		const onProcessingPayment = () => {
			const handlers = eventHandlers.current;
			if ( handlers.sourceEvent && isProcessing ) {
				const response = {
					type: responseTypes.SUCCESS,
					meta: {
						billingData: getBillingData( handlers.sourceEvent ),
						paymentMethodData: getPaymentMethodData(
							handlers.sourceEvent,
							currentPaymentRequestType.current
						),
						shippingData: getShippingData( handlers.sourceEvent ),
					},
				};
				return response;
			}
			return { type: responseTypes.SUCCESS };
		};
		const onCheckoutComplete = ( checkoutResponse ) => {
			const handlers = eventHandlers.current;
			let response = { type: responseTypes.SUCCESS };
			if ( handlers.sourceEvent && isProcessing ) {
				const {
					paymentStatus,
					paymentDetails,
				} = checkoutResponse.processingResponse;
				if ( paymentStatus === responseTypes.SUCCESS ) {
					completePayment( handlers.sourceEvent );
				}
				if (
					paymentStatus === responseTypes.ERROR ||
					paymentStatus === responseTypes.FAIL
				) {
					abortPayment( handlers.sourceEvent );
					response = {
						type: responseTypes.ERROR,
						message: paymentDetails?.errorMessage,
						messageContext: noticeContexts.EXPRESS_PAYMENTS,
						retry: true,
					};
				}
				clearPaymentRequestEventHandler( 'sourceEvent' );
			}
			return response;
		};
		if ( canMakePayment && isProcessing ) {
			const unsubscribeShippingRateSuccess = onShippingRateSuccess(
				onShippingRatesEvent
			);
			const unsubscribeShippingRateFail = onShippingRateFail(
				onShippingRatesEventFail
			);
			const unsubscribeShippingRateSelectSuccess = onShippingRateSelectSuccess(
				onShippingSelectedRate()
			);
			const unsubscribeShippingRateSelectFail = onShippingRateSelectFail(
				onShippingRatesEventFail
			);
			const unsubscribePaymentProcessing = onPaymentProcessing(
				onProcessingPayment
			);
			const unsubscribeCheckoutCompleteSuccess = onCheckoutAfterProcessingWithSuccess(
				onCheckoutComplete
			);
			const unsubscribeCheckoutCompleteFail = onCheckoutAfterProcessingWithError(
				onCheckoutComplete
			);
			return () => {
				unsubscribeCheckoutCompleteFail();
				unsubscribeCheckoutCompleteSuccess();
				unsubscribePaymentProcessing();
				unsubscribeShippingRateFail();
				unsubscribeShippingRateSuccess();
				unsubscribeShippingRateSelectSuccess();
				unsubscribeShippingRateSelectFail();
			};
		}
		return undefined;
	}, [
		canMakePayment,
		isProcessing,
		onShippingRateSuccess,
		onShippingRateFail,
		onShippingRateSelectSuccess,
		onShippingRateSelectFail,
		onPaymentProcessing,
		onCheckoutAfterProcessingWithSuccess,
		onCheckoutAfterProcessingWithError,
		responseTypes,
		noticeContexts,
		completePayment,
		abortPayment,
		clearPaymentRequestEventHandler,
	] );
};
