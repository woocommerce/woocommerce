/**
 * External dependencies
 */
import { store as noticesStore } from '@wordpress/notices';
import deprecated from '@wordpress/deprecated';
import type { BillingAddress, ShippingAddress } from '@woocommerce/settings';
import {
	isObject,
	isString,
	objectHasProp,
	ObserverResponse,
	isErrorResponse,
	isFailResponse,
	isSuccessResponse,
} from '@woocommerce/types';
import type {
	ActionCreatorsOf,
	ConfigOf,
	CurriedSelectorsOf,
	DispatchFunction,
	SelectFunction,
} from '@wordpress/data/build-types/types';
import { paymentStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import {
	emitEventWithAbort,
	noticeContexts,
} from '../../base/context/event-emit';
import { EMIT_TYPES } from '../../base/context/providers/cart-checkout/payment-events/event-emit';
import type { emitProcessingEventType } from './types';
import { store as cartStore } from '../cart';
import {
	isBillingAddress,
	isShippingAddress,
} from '../../types/type-guards/address';
import { isObserverResponse } from '../../types/type-guards/observers';
import { isValidValidationErrorsObject } from '../../types/type-guards/validation';

interface PaymentThunkArgs {
	select?: CurriedSelectorsOf< typeof paymentStore >;
	dispatch: ActionCreatorsOf< ConfigOf< typeof paymentStore > >;
	registry: { dispatch: DispatchFunction; select: SelectFunction };
}

export const __internalSetExpressPaymentError = ( message?: string ) => {
	return ( { registry }: PaymentThunkArgs ) => {
		const { createErrorNotice, removeNotice } =
			registry.dispatch( noticesStore );
		if ( message ) {
			createErrorNotice( message, {
				id: 'wc-express-payment-error',
				context: noticeContexts.EXPRESS_PAYMENTS,
			} );
		} else {
			removeNotice(
				'wc-express-payment-error',
				noticeContexts.EXPRESS_PAYMENTS
			);
		}
	};
};

/**
 * Emit the payment_processing event
 */
export const __internalEmitPaymentProcessingEvent: emitProcessingEventType = (
	currentObserver,
	setValidationErrors
) => {
	return ( { dispatch, registry }: PaymentThunkArgs ) => {
		const { createErrorNotice, removeNotice } =
			registry.dispatch( noticesStore );

		removeNotice( 'wc-payment-error', noticeContexts.PAYMENTS );
		return emitEventWithAbort(
			currentObserver,
			EMIT_TYPES.PAYMENT_SETUP,
			{}
		).then( ( observerResponses ) => {
			let successResponse: ObserverResponse | undefined,
				errorResponse: ObserverResponse | undefined,
				billingAddress: BillingAddress | undefined,
				shippingAddress: ShippingAddress | undefined;
			observerResponses.forEach( ( response ) => {
				if ( isSuccessResponse( response ) ) {
					// The last observer response always "wins" for success.
					successResponse = response;
				}

				// We consider both failed and error responses as an error.
				if (
					isErrorResponse( response ) ||
					isFailResponse( response )
				) {
					errorResponse = response;
				}
				// Extensions may return shippingData, shippingAddress, billingData, and billingAddress in the response,
				// so we need to check for all. If we detect either shippingData or billingData we need to show a
				// deprecated warning for it, but also apply the changes to the wc/store/cart store.
				const {
					billingAddress: billingAddressFromResponse,

					// Deprecated, but keeping it for now, for compatibility with extensions returning it.
					billingData: billingDataFromResponse,
					shippingAddress: shippingAddressFromResponse,

					// Deprecated, but keeping it for now, for compatibility with extensions returning it.
					shippingData: shippingDataFromResponse,
				} = response?.meta || {};

				billingAddress = billingAddressFromResponse as BillingAddress;
				shippingAddress =
					shippingAddressFromResponse as ShippingAddress;

				if ( billingDataFromResponse ) {
					// Set this here so that old extensions still using billingData can set the billingAddress.
					billingAddress = billingDataFromResponse as BillingAddress;
					deprecated(
						'returning billingData from an onPaymentProcessing observer in WooCommerce Blocks',
						{
							version: '9.5.0',
							alternative: 'billingAddress',
							link: 'https://github.com/woocommerce/woocommerce-blocks/pull/6369',
						}
					);
				}

				if (
					objectHasProp( shippingDataFromResponse, 'address' ) &&
					shippingDataFromResponse.address
				) {
					// Set this here so that old extensions still using shippingData can set the shippingAddress.
					shippingAddress =
						shippingDataFromResponse.address as ShippingAddress;
					deprecated(
						'returning shippingData from an onPaymentProcessing observer in WooCommerce Blocks',
						{
							version: '9.5.0',
							alternative: 'shippingAddress',
							link: 'https://github.com/woocommerce/woocommerce-blocks/pull/8163',
						}
					);
				}
			} );

			const { setBillingAddress, setShippingAddress } =
				registry.dispatch( cartStore );

			// Observer returned success, we sync the payment method data and billing address.
			if ( isObserverResponse( successResponse ) && ! errorResponse ) {
				const { paymentMethodData } = successResponse?.meta || {};

				if ( isBillingAddress( billingAddress ) ) {
					setBillingAddress( billingAddress );
				}
				if ( isShippingAddress( shippingAddress ) ) {
					setShippingAddress( shippingAddress );
				}

				dispatch.__internalSetPaymentMethodData(
					isObject( paymentMethodData ) ? paymentMethodData : {}
				);
				dispatch.__internalSetPaymentReady();
			} else if ( isFailResponse( errorResponse ) ) {
				const { paymentMethodData } = errorResponse?.meta || {};

				if (
					objectHasProp( errorResponse, 'message' ) &&
					isString( errorResponse.message ) &&
					errorResponse.message.length
				) {
					let context: string = noticeContexts.PAYMENTS;
					if (
						objectHasProp( errorResponse, 'messageContext' ) &&
						isString( errorResponse.messageContext ) &&
						errorResponse.messageContext.length
					) {
						context = errorResponse.messageContext;
					}
					createErrorNotice( errorResponse.message, {
						id: 'wc-payment-error',
						isDismissible: false,
						context,
					} );
				}

				if ( isBillingAddress( billingAddress ) ) {
					setBillingAddress( billingAddress );
				}

				dispatch.__internalSetPaymentMethodData(
					isObject( paymentMethodData ) ? paymentMethodData : {}
				);
				dispatch.__internalSetPaymentError();
			} else if ( isErrorResponse( errorResponse ) ) {
				if (
					objectHasProp( errorResponse, 'message' ) &&
					isString( errorResponse.message ) &&
					errorResponse.message.length
				) {
					let context: string = noticeContexts.PAYMENTS;
					if (
						objectHasProp( errorResponse, 'messageContext' ) &&
						isString( errorResponse.messageContext ) &&
						errorResponse.messageContext.length
					) {
						context = errorResponse.messageContext;
					}
					createErrorNotice( errorResponse.message, {
						id: 'wc-payment-error',
						isDismissible: false,
						context,
					} );
				}

				dispatch.__internalSetPaymentError();

				if (
					isValidValidationErrorsObject(
						errorResponse.validationErrors
					)
				) {
					setValidationErrors( errorResponse.validationErrors );
				}
			} else {
				// Otherwise there are no payment methods doing anything so just assume payment method is ready.
				dispatch.__internalSetPaymentReady();
			}
		} );
	};
};
