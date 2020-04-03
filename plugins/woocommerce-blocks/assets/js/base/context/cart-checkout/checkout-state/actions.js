/**
 * Internal dependencies
 */
import { TYPES } from './constants';

const {
	SET_PRISTINE,
	SET_PROCESSING,
	SET_PROCESSING_COMPLETE,
	SET_REDIRECT_URL,
	SET_COMPLETE,
	SET_HAS_ERROR,
	SET_NO_ERROR,
	INCREMENT_CALCULATING,
	DECREMENT_CALCULATING,
	SET_ORDER_ID,
} = TYPES;

/**
 * All the actions that can be dispatched for the checkout.
 */
export const actions = {
	setPristine: () => ( {
		type: SET_PRISTINE,
	} ),
	setProcessing: () => ( {
		type: SET_PROCESSING,
	} ),
	setRedirectUrl: ( url ) => ( {
		type: SET_REDIRECT_URL,
		url,
	} ),
	setComplete: () => ( {
		type: SET_COMPLETE,
	} ),
	setProcessingComplete: () => ( {
		type: SET_PROCESSING_COMPLETE,
	} ),
	setHasError: ( hasError = true ) => {
		const type = hasError ? SET_HAS_ERROR : SET_NO_ERROR;
		return { type };
	},
	incrementCalculating: () => ( {
		type: INCREMENT_CALCULATING,
	} ),
	decrementCalculating: () => ( {
		type: DECREMENT_CALCULATING,
	} ),
	setOrderId: ( orderId ) => ( {
		type: SET_ORDER_ID,
		orderId,
	} ),
};
