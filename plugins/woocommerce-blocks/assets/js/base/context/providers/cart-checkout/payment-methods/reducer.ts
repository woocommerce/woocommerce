/**
 * External dependencies
 */
import { PaymentMethods } from '@woocommerce/type-defs/payments';

/**
 * Internal dependencies
 */
import {
	ACTION,
	STATUS,
	DEFAULT_PAYMENT_DATA_CONTEXT_STATE,
} from './constants';
import type { PaymentMethodDataContextState } from './types';
import type { ActionType } from './actions';

/**
 * Reducer for payment data state
 */
const reducer = (
	state = DEFAULT_PAYMENT_DATA_CONTEXT_STATE,
	{
		type,
		paymentMethodData,
		shouldSavePaymentMethod = false,
		errorMessage = '',
		paymentMethods = {},
		paymentMethod = '',
	}: ActionType
): PaymentMethodDataContextState => {
	switch ( type ) {
		case STATUS.PRISTINE:
			return {
				// This keeps payment method registration state and any set data. This effectively just resets the
				// status and any error messages.
				...DEFAULT_PAYMENT_DATA_CONTEXT_STATE,
				...state,
				errorMessage: '',
				currentStatus: STATUS.PRISTINE,
			};
		case STATUS.STARTED:
			return {
				...state,
				currentStatus: STATUS.STARTED,
			};
		case STATUS.ERROR:
			return state.currentStatus !== STATUS.ERROR
				? {
						...state,
						currentStatus: STATUS.ERROR,
						errorMessage: errorMessage || state.errorMessage,
				  }
				: state;
		case STATUS.FAILED:
			return state.currentStatus !== STATUS.FAILED
				? {
						...state,
						currentStatus: STATUS.FAILED,
						paymentMethodData:
							paymentMethodData || state.paymentMethodData,
						errorMessage: errorMessage || state.errorMessage,
				  }
				: state;
		case STATUS.SUCCESS:
			return state.currentStatus !== STATUS.SUCCESS
				? {
						...state,
						currentStatus: STATUS.SUCCESS,
						paymentMethodData:
							paymentMethodData || state.paymentMethodData,
				  }
				: state;
		case STATUS.PROCESSING:
			return state.currentStatus !== STATUS.PROCESSING
				? {
						...state,
						currentStatus: STATUS.PROCESSING,
						errorMessage: '',
				  }
				: state;
		case STATUS.COMPLETE:
			return state.currentStatus !== STATUS.COMPLETE
				? {
						...state,
						currentStatus: STATUS.COMPLETE,
				  }
				: state;
		case ACTION.SET_REGISTERED_PAYMENT_METHODS:
			return {
				...state,
				paymentMethods: paymentMethods as PaymentMethods,
			};
		case ACTION.SET_REGISTERED_EXPRESS_PAYMENT_METHODS:
			return {
				...state,
				expressPaymentMethods: paymentMethods,
			};
		case ACTION.SET_SHOULD_SAVE_PAYMENT_METHOD:
			return {
				...state,
				shouldSavePaymentMethod,
			};
		case ACTION.SET_ACTIVE_PAYMENT_METHOD:
			return {
				...state,
				activePaymentMethod: paymentMethod,
				paymentMethodData: paymentMethodData || state.paymentMethodData,
			};
	}
};

export default reducer;
