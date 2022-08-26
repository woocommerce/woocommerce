/**
 * External dependencies
 */
import type { Reducer } from 'redux';
import { objectHasProp } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	defaultPaymentMethodDataState,
	PaymentMethodDataState,
} from './default-state';
import { ACTION_TYPES } from './action-types';

const reducer: Reducer< PaymentMethodDataState > = (
	state = defaultPaymentMethodDataState,
	action
) => {
	let newState = state;
	switch ( action.type ) {
		case ACTION_TYPES.SET_SHOULD_SAVE_PAYMENT_METHOD:
			newState = {
				...state,
				shouldSavePaymentMethod: action.shouldSavePaymentMethod,
			};
			break;

		case ACTION_TYPES.SET_PAYMENT_METHOD_DATA:
			newState = {
				...state,
				paymentMethodData: action.paymentMethodData,
			};
			break;

		case ACTION_TYPES.SET_PAYMENT_STATUS:
			newState = {
				...state,
				currentStatus: {
					// When the status is changed to pristine, we need to reset the currentStatus properties
					// to their default initial values
					...( action.status?.isPristine === true
						? defaultPaymentMethodDataState.currentStatus
						: state.currentStatus ),
					...action.status,
					isFinished:
						action.status.hasError ||
						action.status.hasFailed ||
						action.status.isSuccessful,
					isDoingExpressPayment:
						! action.status.isPristine &&
						! state.currentStatus.isPristine &&
						state.isExpressPaymentMethodActive,
				},
				paymentMethodData:
					action.paymentMethodData || state.paymentMethodData,
			};
			break;

		case ACTION_TYPES.REMOVE_AVAILABLE_PAYMENT_METHOD:
			const previousAvailablePaymentMethods = {
				...state.availablePaymentMethods,
			};
			delete previousAvailablePaymentMethods[ action.name ];

			newState = {
				...state,
				availablePaymentMethods: {
					...previousAvailablePaymentMethods,
				},
			};
			break;

		case ACTION_TYPES.REMOVE_AVAILABLE_EXPRESS_PAYMENT_METHOD:
			const previousAvailableExpressPaymentMethods = {
				...state.availablePaymentMethods,
			};
			delete previousAvailableExpressPaymentMethods[ action.name ];
			newState = {
				...state,
				availableExpressPaymentMethods: {
					...previousAvailableExpressPaymentMethods,
				},
			};
			break;

		case ACTION_TYPES.SET_PAYMENT_METHODS_INITIALIZED:
			newState = {
				...state,
				paymentMethodsInitialized: action.initialized,
			};
			break;

		case ACTION_TYPES.SET_EXPRESS_PAYMENT_METHODS_INITIALIZED:
			newState = {
				...state,
				expressPaymentMethodsInitialized: action.initialized,
			};
			break;

		case ACTION_TYPES.SET_AVAILABLE_PAYMENT_METHODS:
			newState = {
				...state,
				availablePaymentMethods: action.paymentMethods,
			};
			break;

		case ACTION_TYPES.SET_AVAILABLE_EXPRESS_PAYMENT_METHODS:
			newState = {
				...state,
				availableExpressPaymentMethods: action.paymentMethods,
			};
			break;

		case ACTION_TYPES.SET_ACTIVE_PAYMENT_METHOD:
			const activeSavedToken =
				typeof state.paymentMethodData === 'object' &&
				objectHasProp( action.paymentMethodData, 'token' )
					? action.paymentMethodData.token + ''
					: '';
			newState = {
				...state,
				activeSavedToken,
				activePaymentMethod: action.activePaymentMethod,
				paymentMethodData:
					action.paymentMethodData || state.paymentMethodData,
			};
			break;
		default:
			return newState;
	}
	return newState;
};
export type State = ReturnType< typeof reducer >;

export default reducer;
