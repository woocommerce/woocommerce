/**
 * External dependencies
 */
import { store as noticesStore } from '@wordpress/notices';
import deprecated from '@wordpress/deprecated';
import type { BillingAddress, ShippingAddress } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import {
	emitEventWithAbort,
	isErrorResponse,
	isFailResponse,
	isSuccessResponse,
	noticeContexts,
} from '../../base/context/event-emit';
import { EMIT_TYPES } from '../../base/context/providers/cart-checkout/payment-events/event-emit';
import type { emitProcessingEventType } from './types';
import { CART_STORE_KEY } from '../cart';
import {
	isBillingAddress,
	isShippingAddress,
} from '../../types/type-guards/address';

export const __internalSetExpressPaymentError = ( message?: string ) => {
	return ( { registry } ) => {
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
	return ( { dispatch, registry } ) => {
		const { createErrorNotice, removeNotice } =
			registry.dispatch( 'core/notices' );
		removeNotice( 'wc-payment-error', noticeContexts.PAYMENTS );
		return emitEventWithAbort(
			currentObserver,
			EMIT_TYPES.PAYMENT_PROCESSING,
			{}
		).then( ( observerResponses ) => {
			let successResponse,
				errorResponse,
				billingAddress: BillingAddress | undefined,
				shippingAddress: ShippingAddress | undefined;
			observerResponses.forEach( ( response ) => {
				if ( isSuccessResponse( response ) ) {
					// the last observer response always "wins" for success.
					successResponse = response;
				}
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

				billingAddress = billingAddressFromResponse;
				shippingAddress = shippingAddressFromResponse;

				if ( billingDataFromResponse ) {
					// Set this here so that old extensions still using billingData can set the billingAddress.
					billingAddress = billingDataFromResponse;
					deprecated(
						'returning billingData from an onPaymentProcessing observer in WooCommerce Blocks',
						{
							version: '9.5.0',
							alternative: 'billingAddress',
							link: 'https://github.com/woocommerce/woocommerce-blocks/pull/6369',
						}
					);
				}

				if ( shippingDataFromResponse ) {
					// Set this here so that old extensions still using shippingData can set the shippingAddress.
					shippingAddress = shippingDataFromResponse;
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
				registry.dispatch( CART_STORE_KEY );

			if ( successResponse && ! errorResponse ) {
				const { paymentMethodData } = successResponse?.meta || {};

				if ( billingAddress && isBillingAddress( billingAddress ) ) {
					setBillingAddress( billingAddress );
				}
				if (
					typeof shippingAddress !== 'undefined' &&
					isShippingAddress( shippingAddress )
				) {
					setShippingAddress( shippingAddress );
				}
				dispatch.__internalSetPaymentMethodData( paymentMethodData );
				dispatch.__internalSetPaymentSuccess();
			} else if ( errorResponse && isFailResponse( errorResponse ) ) {
				if ( errorResponse.message && errorResponse.message.length ) {
					createErrorNotice( errorResponse.message, {
						id: 'wc-payment-error',
						isDismissible: false,
						context:
							errorResponse?.messageContext ||
							noticeContexts.PAYMENTS,
					} );
				}

				const { paymentMethodData } = errorResponse?.meta || {};
				if ( billingAddress && isBillingAddress( billingAddress ) ) {
					setBillingAddress( billingAddress );
				}
				dispatch.__internalSetPaymentFailed();
				dispatch.__internalSetPaymentMethodData( paymentMethodData );
			} else if ( errorResponse ) {
				if ( errorResponse.message && errorResponse.message.length ) {
					createErrorNotice( errorResponse.message, {
						id: 'wc-payment-error',
						isDismissible: false,
						context:
							errorResponse?.messageContext ||
							noticeContexts.PAYMENTS,
					} );
				}

				dispatch.__internalSetPaymentError();
				setValidationErrors( errorResponse?.validationErrors );
			} else {
				// otherwise there are no payment methods doing anything so
				// just consider success
				dispatch.__internalSetPaymentSuccess();
			}
		} );
	};
};
