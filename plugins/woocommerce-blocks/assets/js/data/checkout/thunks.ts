/**
 * External dependencies
 */
import type { CheckoutResponse } from '@woocommerce/types';
import { store as noticesStore } from '@wordpress/notices';
import { dispatch as wpDispatch, select as wpSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY as PAYMENT_STORE_KEY } from '../payment/constants';
import { removeNoticesByStatus } from '../../utils/notices';
import {
	getPaymentResultFromCheckoutResponse,
	runCheckoutAfterProcessingWithErrorObservers,
	runCheckoutAfterProcessingWithSuccessObservers,
} from './utils';
import {
	EVENTS,
	emitEvent,
	emitEventWithAbort,
} from '../../base/context/providers/cart-checkout/checkout-events/event-emit';
import type {
	emitValidateEventType,
	emitAfterProcessingEventsType,
} from './types';
import type { DispatchFromMap } from '../mapped-types';
import * as actions from './actions';

/**
 * Based on the result of the payment, update the redirect url,
 * set the payment processing response in the checkout data store
 * and change the status to AFTER_PROCESSING
 */
export const __internalProcessCheckoutResponse = (
	response: CheckoutResponse
) => {
	return ( {
		dispatch,
	}: {
		dispatch: DispatchFromMap< typeof actions >;
	} ) => {
		const paymentResult = getPaymentResultFromCheckoutResponse( response );
		dispatch.__internalSetRedirectUrl( paymentResult?.redirectUrl || '' );
		// The local `dispatch` here is bound  to the actions of the data store. We need to use the global dispatch here
		// to dispatch an action on a different store.
		wpDispatch( PAYMENT_STORE_KEY ).__internalSetPaymentResult(
			paymentResult
		);
		dispatch.__internalSetAfterProcessing();
	};
};

/**
 * Emit the CHECKOUT_VALIDATION_BEFORE_PROCESSING event and process all
 * registered observers
 */
export const __internalEmitValidateEvent: emitValidateEventType = ( {
	observers,
	setValidationErrors, // TODO: Fix this type after we move to validation store
} ) => {
	return ( { dispatch, registry } ) => {
		const { createErrorNotice } = registry.dispatch( noticesStore );
		removeNoticesByStatus( 'error' );
		emitEvent(
			observers,
			EVENTS.CHECKOUT_VALIDATION_BEFORE_PROCESSING,
			{}
		).then( ( response ) => {
			if ( response !== true ) {
				if ( Array.isArray( response ) ) {
					response.forEach(
						( { errorMessage, validationErrors } ) => {
							createErrorNotice( errorMessage, {
								context: 'wc/checkout',
							} );
							setValidationErrors( validationErrors );
						}
					);
				}
				dispatch.__internalSetIdle();
				dispatch.__internalSetHasError();
			} else {
				dispatch.__internalSetProcessing();
			}
		} );
	};
};

/**
 * Emit the CHECKOUT_AFTER_PROCESSING_WITH_ERROR if the checkout contains an error,
 * or the CHECKOUT_AFTER_PROCESSING_WITH_SUCCESS if not. Set checkout errors according
 * to the observer responses
 */
export const __internalEmitAfterProcessingEvents: emitAfterProcessingEventsType =
	( { observers, notices } ) => {
		return ( { select, dispatch, registry } ) => {
			const { createErrorNotice } = registry.dispatch( noticesStore );
			const data = {
				redirectUrl: select.getRedirectUrl(),
				orderId: select.getOrderId(),
				customerId: select.getCustomerId(),
				orderNotes: select.getOrderNotes(),
				processingResponse:
					wpSelect( PAYMENT_STORE_KEY ).getPaymentResult(),
			};
			if ( select.hasError() ) {
				// allow payment methods or other things to customize the error
				// with a fallback if nothing customizes it.
				emitEventWithAbort(
					observers,
					EVENTS.CHECKOUT_AFTER_PROCESSING_WITH_ERROR,
					data
				).then( ( observerResponses ) => {
					runCheckoutAfterProcessingWithErrorObservers( {
						observerResponses,
						notices,
						dispatch,
						createErrorNotice,
						data,
					} );
				} );
			} else {
				emitEventWithAbort(
					observers,
					EVENTS.CHECKOUT_AFTER_PROCESSING_WITH_SUCCESS,
					data
				).then( ( observerResponses: unknown[] ) => {
					runCheckoutAfterProcessingWithSuccessObservers( {
						observerResponses,
						dispatch,
						createErrorNotice,
					} );
				} );
			}
		};
	};
