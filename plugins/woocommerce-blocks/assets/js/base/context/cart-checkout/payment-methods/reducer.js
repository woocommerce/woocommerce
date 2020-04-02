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
	SET_REGISTERED_PAYMENT_METHOD,
	SET_REGISTERED_EXPRESS_PAYMENT_METHOD,
} = ACTION_TYPES;

/**
 * Reducer for payment data state
 *
 * @param {Object} state  Current state.
 * @param {Object} action Current action.
 */
const reducer = (
	state = DEFAULT_PAYMENT_DATA,
	{ type, paymentMethodData, errorMessage, paymentMethod }
) => {
	switch ( type ) {
		case STARTED:
			return {
				...state,
				currentStatus: STARTED,
			};
		case ERROR:
			return {
				...state,
				currentStatus: ERROR,
				errorMessage: errorMessage || state.errorMessage,
			};
		case FAILED:
			return {
				...state,
				currentStatus: FAILED,
				paymentMethodData: paymentMethodData || state.paymentMethodData,
				errorMessage: errorMessage || state.errorMessage,
			};
		case SUCCESS:
			return {
				...state,
				currentStatus: SUCCESS,
				paymentMethodData: paymentMethodData || state.paymentMethodData,
			};
		case PROCESSING:
			return {
				...state,
				currentStatus: PROCESSING,
				errorMessage: '',
			};
		case COMPLETE:
			return {
				...state,
				currentStatus: COMPLETE,
			};

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
		case SET_REGISTERED_PAYMENT_METHOD:
			return {
				...state,
				paymentMethods: {
					...state.paymentMethods,
					[ paymentMethod.id ]: paymentMethod,
				},
			};
		case SET_REGISTERED_EXPRESS_PAYMENT_METHOD:
			return {
				...state,
				expressPaymentMethods: {
					...state.expressPaymentMethods,
					[ paymentMethod.id ]: paymentMethod,
				},
			};
	}
	return state;
};

export default reducer;
