/**
 * External dependencies
 */
import {
	isValidValidationErrorsObject,
	type CheckoutResponse,
	isSuccessResponse,
} from '@woocommerce/types';
import { store as noticesStore } from '@wordpress/notices';
import { dispatch as wpDispatch, select as wpSelect } from '@wordpress/data';
import type {
	ActionCreatorsOf,
	ConfigOf,
	CurriedSelectorsOf,
	DispatchFunction,
	SelectFunction,
} from '@wordpress/data/build-types/types';
import {
	CHECKOUT_EVENTS,
	checkoutEventsEmitter,
} from '@woocommerce/blocks-checkout-events';

/**
 * Internal dependencies
 */
import { store as paymentStore } from '../payment';
import type { CheckoutStoreDescriptor } from './index';
import { removeNoticesByStatus } from '../../utils/notices';
import {
	getPaymentResultFromCheckoutResponse,
	runCheckoutFailObservers,
	runCheckoutSuccessObservers,
} from './utils';
import type {
	emitValidateEventType,
	emitAfterProcessingEventsType,
	CheckoutPutData,
} from './types';
import { apiFetchWithHeaders } from '../shared-controls';
import { CheckoutPutAbortController } from '../utils/clear-put-requests';
import { CART_STORE_KEY } from '../cart';

export interface CheckoutThunkArgs {
	select: CurriedSelectorsOf< CheckoutStoreDescriptor >;
	dispatch: ActionCreatorsOf< ConfigOf< CheckoutStoreDescriptor > >;
	registry: { dispatch: DispatchFunction; select: SelectFunction };
}

/**
 * Based on the result of the payment, update the redirect url,
 * set the payment processing response in the checkout data store
 * and change the status to AFTER_PROCESSING
 */
export const __internalProcessCheckoutResponse = (
	response: CheckoutResponse
) => {
	return ( { dispatch }: CheckoutThunkArgs ) => {
		const paymentResult = getPaymentResultFromCheckoutResponse( response );
		dispatch.__internalSetRedirectUrl( paymentResult?.redirectUrl || '' );
		// The local `dispatch` here is bound  to the actions of the data store. We need to use the global dispatch here
		// to dispatch an action on a different store.
		wpDispatch( paymentStore ).__internalSetPaymentResult( paymentResult );
		dispatch.__internalSetAfterProcessing();
	};
};

/**
 * Emit the CHECKOUT_VALIDATION event and process all
 * registered observers
 */
export const __internalEmitValidateEvent: emitValidateEventType = ( {
	setValidationErrors,
} ) => {
	return ( { dispatch, registry }: CheckoutThunkArgs ) => {
		const { createErrorNotice } = registry.dispatch( noticesStore );
		removeNoticesByStatus( 'error' );
		checkoutEventsEmitter
			.emit( CHECKOUT_EVENTS.CHECKOUT_VALIDATION )
			.then( ( responses ) => {
				// If responses length is 0, then no observer returned a response that wasn't `true` or void, therefore,
				// we can assume all observers passed and continue to processing. We also need to check if all responses
				// are of type `success`, so we can skip adding any errors too.
				if (
					responses.length === 0 ||
					responses.every( isSuccessResponse )
				) {
					dispatch.__internalSetProcessing();
					return;
				}
				// If any observer returned a response, by this point we know that it's either failure or error due to
				// the checks above.
				responses.forEach(
					( {
						errorMessage,
						validationErrors,
						context = 'wc/checkout',
					} ) => {
						if (
							// TODO: for a more consistent experience across observing events we should normalize the
							// return values. For example this one expects `errorMessage` whereas `onCheckoutFail`
							// expects `message`. It would be good to ensure all observer responses are the same shape
							// otherwise this leads to confusion when typing internally, and confusion for the consumer.
							typeof errorMessage === 'string' &&
							errorMessage
						) {
							createErrorNotice( errorMessage, { context } );
						}
						if (
							isValidValidationErrorsObject( validationErrors )
						) {
							setValidationErrors( validationErrors );
						}
					}
				);
				dispatch.__internalSetIdle();
				dispatch.__internalSetHasError();
			} );
	};
};

/**
 * Emit the CHECKOUT_FAIL if the checkout contains an error,
 * or the CHECKOUT_SUCCESS if not. Set checkout errors according
 * to the observer responses
 */
export const __internalEmitAfterProcessingEvents: emitAfterProcessingEventsType =
	( { notices } ) => {
		return ( { select, dispatch, registry } ) => {
			const { createErrorNotice } = registry.dispatch( noticesStore );
			const data = {
				redirectUrl: select.getRedirectUrl(),
				orderId: select.getOrderId(),
				customerId: select.getCustomerId(),
				orderNotes: select.getOrderNotes(),
				processingResponse: wpSelect( paymentStore ).getPaymentResult(),
			};
			if ( select.hasError() ) {
				// allow payment methods or other things to customize the error
				// with a fallback if nothing customizes it.
				checkoutEventsEmitter
					.emitWithAbort( CHECKOUT_EVENTS.CHECKOUT_FAIL, data )
					.then( ( observerResponses ) => {
						runCheckoutFailObservers( {
							observerResponses,
							notices,
							dispatch,
							createErrorNotice,
							data,
						} );
					} );
			} else {
				checkoutEventsEmitter
					.emitWithAbort( CHECKOUT_EVENTS.CHECKOUT_SUCCESS, data )
					.then( ( observerResponses ) => {
						runCheckoutSuccessObservers( {
							observerResponses,
							dispatch,
							createErrorNotice,
						} );
					} );
			}
		};
	};

export const updateDraftOrder = ( data: CheckoutPutData ) => {
	return async ( { registry } ) => {
		const { receiveCartContents } = registry.dispatch( CART_STORE_KEY );
		try {
			const response = await apiFetchWithHeaders( {
				path: '/wc/store/v1/checkout?__experimental_calc_totals=true',
				method: 'PUT',
				data,
				signal: CheckoutPutAbortController.signal,
			} );
			if ( response?.response?.__experimentalCart ) {
				receiveCartContents( response.response.__experimentalCart );
			}
			return response;
		} catch ( error ) {
			return Promise.reject( error );
		}
	};
};

export const disableCheckoutFor = ( asyncFunc: () => Promise< unknown > ) => {
	return async ( { dispatch }: CheckoutThunkArgs ) => {
		dispatch.__internalStartCalculation();
		try {
			return await asyncFunc();
			// No catch block here as we don't want to swallow any potential errors
			// coming from asyncFunc.
		} finally {
			dispatch.__internalFinishCalculation();
		}
	};
};
