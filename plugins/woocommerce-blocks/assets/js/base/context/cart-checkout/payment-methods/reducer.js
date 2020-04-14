/**
 * Internal dependencies
 */
import { ACTION_TYPES, DEFAULT_PAYMENT_DATA } from './constants';
const {
	STARTED,
	ERROR,
	FAILED,
	SUCCESS,
	PROCESSING,
	PRISTINE,
	COMPLETE,
	SET_REGISTERED_PAYMENT_METHODS,
	SET_REGISTERED_EXPRESS_PAYMENT_METHODS,
} = ACTION_TYPES;

/**
 * Reducer for payment data state
 *
 * @param {Object} state  Current state.
 * @param {Object} action Current action.
 */
const reducer = (
	state = DEFAULT_PAYMENT_DATA,
	{ type, paymentMethodData, errorMessage, paymentMethods }
) => {
	switch ( type ) {
		case STARTED:
			return state.currentStatus !== STARTED
				? {
						...state,
						currentStatus: STARTED,
				  }
				: state;
		case ERROR:
			return state.currentStatus !== ERROR
				? {
						...state,
						currentStatus: ERROR,
						errorMessage: errorMessage || state.errorMessage,
				  }
				: state;
		case FAILED:
			return state.currentStatus !== FAILED
				? {
						...state,
						currentStatus: FAILED,
						paymentMethodData:
							paymentMethodData || state.paymentMethodData,
						errorMessage: errorMessage || state.errorMessage,
				  }
				: state;
		case SUCCESS:
			return state.currentStatus !== SUCCESS
				? {
						...state,
						currentStatus: SUCCESS,
						paymentMethodData:
							paymentMethodData || state.paymentMethodData,
				  }
				: state;
		case PROCESSING:
			return state.currentStatus !== PROCESSING
				? {
						...state,
						currentStatus: PROCESSING,
						errorMessage: '',
				  }
				: state;
		case COMPLETE:
			return state.currentStatus !== COMPLETE
				? {
						...state,
						currentStatus: COMPLETE,
				  }
				: state;

		case PRISTINE:
			return {
				...DEFAULT_PAYMENT_DATA,
				currentStatus: PRISTINE,
				// keep payment method registration state
				paymentMethods: {
					...state.paymentMethods,
				},
				expressPaymentMethods: {
					...state.expressPaymentMethods,
				},
			};
		case SET_REGISTERED_PAYMENT_METHODS:
			return {
				...state,
				paymentMethods: {
					...state.paymentMethods,
					...paymentMethods,
				},
			};
		case SET_REGISTERED_EXPRESS_PAYMENT_METHODS:
			return {
				...state,
				expressPaymentMethods: {
					...state.expressPaymentMethods,
					...paymentMethods,
				},
			};
	}
	return state;
};

export default reducer;
