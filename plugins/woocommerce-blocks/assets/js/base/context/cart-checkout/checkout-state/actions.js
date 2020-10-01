/**
 * Internal dependencies
 */
import { TYPES } from './constants';

const {
	SET_PRISTINE,
	SET_IDLE,
	SET_PROCESSING,
	SET_BEFORE_PROCESSING,
	SET_AFTER_PROCESSING,
	SET_PROCESSING_RESPONSE,
	SET_REDIRECT_URL,
	SET_COMPLETE,
	SET_HAS_ERROR,
	SET_NO_ERROR,
	INCREMENT_CALCULATING,
	DECREMENT_CALCULATING,
	SET_ORDER_ID,
	SET_SHOULD_CREATE_ACCOUNT,
	SET_ORDER_NOTES,
} = TYPES;

/**
 * All the actions that can be dispatched for the checkout.
 */
export const actions = {
	setPristine: () => ( {
		type: SET_PRISTINE,
	} ),
	setIdle: () => ( {
		type: SET_IDLE,
	} ),
	setProcessing: () => ( {
		type: SET_PROCESSING,
	} ),
	setRedirectUrl: ( url ) => ( {
		type: SET_REDIRECT_URL,
		url,
	} ),
	setProcessingResponse: ( data ) => ( {
		type: SET_PROCESSING_RESPONSE,
		data,
	} ),
	setComplete: ( data ) => ( {
		type: SET_COMPLETE,
		data,
	} ),
	setBeforeProcessing: () => ( {
		type: SET_BEFORE_PROCESSING,
	} ),
	setAfterProcessing: () => ( {
		type: SET_AFTER_PROCESSING,
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
	setShouldCreateAccount: ( shouldCreateAccount ) => ( {
		type: SET_SHOULD_CREATE_ACCOUNT,
		shouldCreateAccount,
	} ),
	setOrderNotes: ( orderNotes ) => ( {
		type: SET_ORDER_NOTES,
		orderNotes,
	} ),
};
