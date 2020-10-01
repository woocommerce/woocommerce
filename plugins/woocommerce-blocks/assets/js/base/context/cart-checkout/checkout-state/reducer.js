/**
 * Internal dependencies
 */
import { TYPES, DEFAULT_STATE, STATUS } from './constants';

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
	SET_ORDER_NOTES,
	SET_SHOULD_CREATE_ACCOUNT,
} = TYPES;

const {
	PRISTINE,
	IDLE,
	PROCESSING,
	BEFORE_PROCESSING,
	AFTER_PROCESSING,
	COMPLETE,
} = STATUS;

/**
 * Prepares the payment_result data from the server checkout endpoint response.
 *
 * @param {Object}        data                 The value of `payment_result` from the checkout
 *                                             processing endpoint response.
 * @param {string}        data.message         If there was a general error message it will appear
 *                                             on this property.
 * @param {string}        data.payment_status  The payment status. One of 'success', 'failure',
 *                                             'pending', 'error'.
 * @param {Array<Object>} data.payment_details An array of Objects with a 'key' property that is a
 *                                             string and value property that is a string. These are
 *                                             converted to a flat object where the key becomes the
 *                                             object property and value the property value.
 *
 * @return {Object} A new object with 'paymentStatus', and 'paymentDetails' as the properties.
 */
export const prepareResponseData = ( data ) => {
	const responseData = {
		message: data?.message || '',
		paymentStatus: data.payment_status,
		paymentDetails: {},
	};
	if ( Array.isArray( data.payment_details ) ) {
		data.payment_details.forEach( ( { key, value } ) => {
			responseData.paymentDetails[ key ] = value;
		} );
	}
	return responseData;
};

/**
 * Reducer for the checkout state
 *
 * @param {Object} state  Current state.
 * @param {Object} action Incoming action object.
 * @param {string} action.url URL passed in.
 * @param {string} action.type Type of action.
 * @param {string} action.orderId Order ID.
 * @param {Array} action.orderNotes Order notes.
 * @param {boolean} action.shouldCreateAccount True if shopper has requested a user account (signup checkbox).
 * @param {Object} action.data Other action payload.
 */
export const reducer = (
	state = DEFAULT_STATE,
	{ url, type, orderId, orderNotes, shouldCreateAccount, data }
) => {
	let newState = state;
	switch ( type ) {
		case SET_PRISTINE:
			newState = DEFAULT_STATE;
			break;
		case SET_IDLE:
			newState =
				state.status !== IDLE
					? {
							...state,
							status: IDLE,
					  }
					: state;
			break;
		case SET_REDIRECT_URL:
			newState =
				url !== state.url
					? {
							...state,
							redirectUrl: url,
					  }
					: state;
			break;
		case SET_PROCESSING_RESPONSE:
			newState = {
				...state,
				processingResponse: data,
			};
			break;

		case SET_COMPLETE:
			newState =
				state.status !== COMPLETE
					? {
							...state,
							status: COMPLETE,
							redirectUrl: data?.redirectUrl || state.redirectUrl,
					  }
					: state;
			break;
		case SET_PROCESSING:
			newState =
				state.status !== PROCESSING
					? {
							...state,
							status: PROCESSING,
							hasError: false,
					  }
					: state;
			// clear any error state.
			newState =
				newState.hasError === false
					? newState
					: { ...newState, hasError: false };
			break;
		case SET_BEFORE_PROCESSING:
			newState =
				state.status !== BEFORE_PROCESSING
					? {
							...state,
							status: BEFORE_PROCESSING,
							hasError: false,
					  }
					: state;
			break;
		case SET_AFTER_PROCESSING:
			newState =
				state.status !== AFTER_PROCESSING
					? {
							...state,
							status: AFTER_PROCESSING,
					  }
					: state;
			break;
		case SET_HAS_ERROR:
			newState = state.hasError
				? state
				: {
						...state,
						hasError: true,
				  };
			newState =
				state.status === PROCESSING ||
				state.status === BEFORE_PROCESSING
					? {
							...newState,
							status: IDLE,
					  }
					: newState;
			break;
		case SET_NO_ERROR:
			newState = state.hasError
				? {
						...state,
						hasError: false,
				  }
				: state;
			break;
		case INCREMENT_CALCULATING:
			newState = {
				...state,
				calculatingCount: state.calculatingCount + 1,
			};
			break;
		case DECREMENT_CALCULATING:
			newState = {
				...state,
				calculatingCount: Math.max( 0, state.calculatingCount - 1 ),
			};
			break;
		case SET_ORDER_ID:
			newState = {
				...state,
				orderId,
			};
			break;
		case SET_SHOULD_CREATE_ACCOUNT:
			if ( shouldCreateAccount !== state.shouldCreateAccount ) {
				newState = {
					...state,
					shouldCreateAccount,
				};
			}
			break;
		case SET_ORDER_NOTES:
			if ( state.orderNotes !== orderNotes ) {
				newState = {
					...state,
					orderNotes,
				};
			}
			break;
	}
	// automatically update state to idle from pristine as soon as it
	// initially changes.
	if (
		newState !== state &&
		type !== SET_PRISTINE &&
		newState.status === PRISTINE
	) {
		newState.status = IDLE;
	}
	return newState;
};
