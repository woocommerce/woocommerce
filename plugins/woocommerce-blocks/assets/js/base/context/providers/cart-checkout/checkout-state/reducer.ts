/**
 * Internal dependencies
 */
import { DEFAULT_STATE, STATUS } from './constants';
import { ActionType, ACTION } from './actions';
import type { CheckoutStateContextState, PaymentResultDataType } from './types';

/**
 * Reducer for the checkout state
 *
 * @param {Object} state  Current state.
 * @param {Object} action Incoming action object.
 * @param {string} action.url URL passed in.
 * @param {string} action.type Type of action.
 * @param {string} action.customerId Customer ID.
 * @param {string} action.orderId Order ID.
 * @param {Array} action.orderNotes Order notes.
 * @param {boolean} action.shouldCreateAccount True if shopper has requested a user account (sign-up checkbox).
 * @param {Object} action.data Other action payload.
 */
export const reducer = (
	state = DEFAULT_STATE,
	{
		url,
		type,
		customerId,
		orderId,
		orderNotes,
		shouldCreateAccount,
		data,
	}: ActionType
): CheckoutStateContextState => {
	let newState = state;
	switch ( type ) {
		case ACTION.SET_PRISTINE:
			newState = DEFAULT_STATE;
			break;
		case ACTION.SET_IDLE:
			newState =
				state.status !== STATUS.IDLE
					? {
							...state,
							status: STATUS.IDLE,
					  }
					: state;
			break;
		case ACTION.SET_REDIRECT_URL:
			newState =
				url !== undefined && url !== state.redirectUrl
					? {
							...state,
							redirectUrl: url,
					  }
					: state;
			break;
		case ACTION.SET_PROCESSING_RESPONSE:
			newState = {
				...state,
				processingResponse: data as PaymentResultDataType,
			};
			break;

		case ACTION.SET_COMPLETE:
			newState =
				state.status !== STATUS.COMPLETE
					? {
							...state,
							status: STATUS.COMPLETE,
							// @todo Investigate why redirectURL could be non-truthy and whether this would cause a bug if multiple gateways were used for payment e.g. 1st set the redirect URL but failed, and then the 2nd did not provide a redirect URL and succeeded.
							redirectUrl:
								data !== undefined &&
								typeof data.redirectUrl === 'string' &&
								data.redirectUrl
									? data.redirectUrl
									: state.redirectUrl,
					  }
					: state;
			break;
		case ACTION.SET_PROCESSING:
			newState =
				state.status !== STATUS.PROCESSING
					? {
							...state,
							status: STATUS.PROCESSING,
							hasError: false,
					  }
					: state;
			// clear any error state.
			newState =
				newState.hasError === false
					? newState
					: { ...newState, hasError: false };
			break;
		case ACTION.SET_BEFORE_PROCESSING:
			newState =
				state.status !== STATUS.BEFORE_PROCESSING
					? {
							...state,
							status: STATUS.BEFORE_PROCESSING,
							hasError: false,
					  }
					: state;
			break;
		case ACTION.SET_AFTER_PROCESSING:
			newState =
				state.status !== STATUS.AFTER_PROCESSING
					? {
							...state,
							status: STATUS.AFTER_PROCESSING,
					  }
					: state;
			break;
		case ACTION.SET_HAS_ERROR:
			newState = state.hasError
				? state
				: {
						...state,
						hasError: true,
				  };
			newState =
				state.status === STATUS.PROCESSING ||
				state.status === STATUS.BEFORE_PROCESSING
					? {
							...newState,
							status: STATUS.IDLE,
					  }
					: newState;
			break;
		case ACTION.SET_NO_ERROR:
			newState = state.hasError
				? {
						...state,
						hasError: false,
				  }
				: state;
			break;
		case ACTION.INCREMENT_CALCULATING:
			newState = {
				...state,
				calculatingCount: state.calculatingCount + 1,
			};
			break;
		case ACTION.DECREMENT_CALCULATING:
			newState = {
				...state,
				calculatingCount: Math.max( 0, state.calculatingCount - 1 ),
			};
			break;
		case ACTION.SET_CUSTOMER_ID:
			newState =
				customerId !== undefined
					? {
							...state,
							customerId,
					  }
					: state;
			break;
		case ACTION.SET_ORDER_ID:
			newState =
				orderId !== undefined
					? {
							...state,
							orderId,
					  }
					: state;
			break;
		case ACTION.SET_SHOULD_CREATE_ACCOUNT:
			if (
				shouldCreateAccount !== undefined &&
				shouldCreateAccount !== state.shouldCreateAccount
			) {
				newState = {
					...state,
					shouldCreateAccount,
				};
			}
			break;
		case ACTION.SET_ORDER_NOTES:
			if ( orderNotes !== undefined && state.orderNotes !== orderNotes ) {
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
		type !== ACTION.SET_PRISTINE &&
		newState.status === STATUS.PRISTINE
	) {
		newState.status = STATUS.IDLE;
	}
	return newState;
};
