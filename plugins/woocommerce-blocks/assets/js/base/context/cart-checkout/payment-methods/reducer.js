/**
 * Internal dependencies
 */
import { STATUS, DEFAULT_PAYMENT_DATA } from './constants';
const {
	STARTED,
	ERROR,
	FAILED,
	SUCCESS,
	PROCESSING,
	PRISTINE,
	COMPLETE,
} = STATUS;

const SET_BILLING_DATA = 'set_billing_data';

/**
 * Reducer for payment data state
 *
 * @param {Object} state  Current state.
 * @param {Object} action Current action.
 */
const reducer = (
	state = DEFAULT_PAYMENT_DATA,
	{ type, billingData, paymentMethodData, errorMessage }
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
				billingData: billingData || state.billingData,
				paymentMethodData: paymentMethodData || state.paymentMethodData,
				errorMessage: errorMessage || state.errorMessage,
			};
		case SUCCESS:
			return {
				...state,
				currentStatus: SUCCESS,
				billingData: billingData || state.billingData,
				paymentMethodData: paymentMethodData || state.paymentMethodData,
			};
		case PROCESSING:
			return {
				...state,
				currentStatus: PROCESSING,
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
			};
		case SET_BILLING_DATA:
			return {
				...state,
				billingData,
			};
	}
	return state;
};

export default reducer;
