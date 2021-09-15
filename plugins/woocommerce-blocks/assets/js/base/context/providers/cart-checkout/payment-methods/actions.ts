/**
 * External dependencies
 */
import {
	PaymentMethods,
	ExpressPaymentMethods,
} from '@woocommerce/type-defs/payments';

/**
 * Internal dependencies
 */
import { ACTION, STATUS } from './constants';

export interface ActionType {
	type: ACTION | STATUS;
	errorMessage?: string;
	paymentMethodData?: Record< string, unknown >;
	paymentMethods?: PaymentMethods | ExpressPaymentMethods;
	shouldSavePaymentMethod?: boolean;
}

/**
 * All the actions that can be dispatched for payment methods.
 */
export const actions = {
	statusOnly: ( type: STATUS ): { type: STATUS } => ( { type } as const ),
	error: ( errorMessage: string ): ActionType =>
		( {
			type: STATUS.ERROR,
			errorMessage,
		} as const ),
	failed: ( {
		errorMessage,
		paymentMethodData,
	}: {
		errorMessage: string;
		paymentMethodData: Record< string, unknown >;
	} ): ActionType =>
		( {
			type: STATUS.FAILED,
			errorMessage,
			paymentMethodData,
		} as const ),
	success: ( {
		paymentMethodData,
	}: {
		paymentMethodData?: Record< string, unknown >;
	} ): ActionType =>
		( {
			type: STATUS.SUCCESS,
			paymentMethodData,
		} as const ),
	started: ( {
		paymentMethodData,
	}: {
		paymentMethodData?: Record< string, unknown >;
	} ): ActionType =>
		( {
			type: STATUS.STARTED,
			paymentMethodData,
		} as const ),
	setRegisteredPaymentMethods: (
		paymentMethods: PaymentMethods
	): ActionType =>
		( {
			type: ACTION.SET_REGISTERED_PAYMENT_METHODS,
			paymentMethods,
		} as const ),
	setRegisteredExpressPaymentMethods: (
		paymentMethods: ExpressPaymentMethods
	): ActionType =>
		( {
			type: ACTION.SET_REGISTERED_EXPRESS_PAYMENT_METHODS,
			paymentMethods,
		} as const ),
	setShouldSavePaymentMethod: (
		shouldSavePaymentMethod: boolean
	): ActionType =>
		( {
			type: ACTION.SET_SHOULD_SAVE_PAYMENT_METHOD,
			shouldSavePaymentMethod,
		} as const ),
};

export default actions;
