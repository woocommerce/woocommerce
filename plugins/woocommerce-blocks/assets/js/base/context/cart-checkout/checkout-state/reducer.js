/**
 * Internal dependencies
 */
import { TYPES, DEFAULT_STATE, STATUS } from './constants';

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

const { PRISTINE, IDLE, PROCESSING, PROCESSING_COMPLETE, COMPLETE } = STATUS;

/**
 * Reducer for the checkout state
 *
 * @param {Object} state  Current state.
 * @param {Object} action Incoming action object.
 */
export const reducer = ( state = DEFAULT_STATE, { url, type, orderId } ) => {
	let newState;
	switch ( type ) {
		case SET_PRISTINE:
			newState = DEFAULT_STATE;
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
		case SET_COMPLETE:
			newState =
				state.status !== COMPLETE
					? {
							...state,
							status: COMPLETE,
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
		case SET_PROCESSING_COMPLETE:
			newState =
				state.status !== SET_PROCESSING_COMPLETE
					? {
							...state,
							status: PROCESSING_COMPLETE,
							hasError: false,
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
				state.status === PROCESSING_COMPLETE
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
