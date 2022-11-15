/**
 * External dependencies
 */
import { store as noticesStore } from '@wordpress/notices';

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
		emitEventWithAbort(
			currentObserver,
			EMIT_TYPES.PAYMENT_PROCESSING,
			{}
		).then( ( observerResponses ) => {
			let successResponse, errorResponse;
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
			} );

			const { setBillingAddress, setShippingAddress } =
				registry.dispatch( CART_STORE_KEY );

			if ( successResponse && ! errorResponse ) {
				const { paymentMethodData, billingAddress, shippingData } =
					successResponse?.meta || {};

				if ( billingAddress ) {
					setBillingAddress( billingAddress );
				}
				if (
					typeof shippingData !== undefined &&
					shippingData?.address
				) {
					setShippingAddress(
						shippingData.address as Record< string, unknown >
					);
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

				const { paymentMethodData, billingAddress } =
					errorResponse?.meta || {};

				if ( billingAddress ) {
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
