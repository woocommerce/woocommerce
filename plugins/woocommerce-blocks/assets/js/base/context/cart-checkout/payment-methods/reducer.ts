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

const hasSavedPaymentToken = (
	paymentMethodData: Record< string, unknown >
): boolean => {
	return !! (
		typeof paymentMethodData === 'object' && paymentMethodData.isSavedToken
	);
};

/**
 * Reducer for payment data state
 */
const reducer = (
	state = DEFAULT_PAYMENT_DATA_CONTEXT_STATE,
	{
		type,
		paymentMethodData = {},
		shouldSavePaymentMethod = false,
		errorMessage = '',
		paymentMethods = {},
	}: ActionType
): PaymentMethodDataContextState => {
	switch ( type ) {
		case STATUS.STARTED:
			return state.currentStatus !== STATUS.STARTED
				? {
						...state,
						currentStatus: STATUS.STARTED,
				  }
				: state;
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
						hasSavedToken: hasSavedPaymentToken(
							paymentMethodData
						),
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

		case STATUS.PRISTINE:
			return {
				...DEFAULT_PAYMENT_DATA_CONTEXT_STATE,
				currentStatus: STATUS.PRISTINE,
				// keep payment method registration state
				paymentMethods: {
					...state.paymentMethods,
				},
				expressPaymentMethods: {
					...state.expressPaymentMethods,
				},
				shouldSavePaymentMethod: state.shouldSavePaymentMethod,
			};
		case ACTION.SET_REGISTERED_PAYMENT_METHODS:
			return {
				...state,
				paymentMethods,
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
	}
};

export default reducer;
