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
	paymentMethodData?: Record< string, unknown > | undefined;
	paymentMethods?: PaymentMethods | ExpressPaymentMethods;
	paymentMethod?: string;
	shouldSavePaymentMethod?: boolean;
}

/**
 * All the actions that can be dispatched for payment methods.
 */
export const actions = {
	statusOnly: ( type: STATUS ): ActionType => ( {
		type,
	} ),
	error: ( errorMessage: string ): ActionType => ( {
		type: STATUS.ERROR,
		errorMessage,
	} ),
	failed: ( {
		errorMessage,
		paymentMethodData,
	}: {
		errorMessage: string;
		paymentMethodData: Record< string, unknown >;
	} ): ActionType => ( {
		type: STATUS.FAILED,
		errorMessage,
		paymentMethodData,
	} ),
	success: ( {
		paymentMethodData,
	}: {
		paymentMethodData?: Record< string, unknown >;
	} ): ActionType => ( {
		type: STATUS.SUCCESS,
		paymentMethodData,
	} ),
	setRegisteredPaymentMethods: (
		paymentMethods: PaymentMethods
	): ActionType => ( {
		type: ACTION.SET_REGISTERED_PAYMENT_METHODS,
		paymentMethods,
	} ),
	setRegisteredExpressPaymentMethods: (
		paymentMethods: ExpressPaymentMethods
	): ActionType => ( {
		type: ACTION.SET_REGISTERED_EXPRESS_PAYMENT_METHODS,
		paymentMethods,
	} ),
	setShouldSavePaymentMethod: (
		shouldSavePaymentMethod: boolean
	): ActionType => ( {
		type: ACTION.SET_SHOULD_SAVE_PAYMENT_METHOD,
		shouldSavePaymentMethod,
	} ),
	setActivePaymentMethod: (
		paymentMethod: string,
		paymentMethodData: Record< string, unknown >
	): ActionType => ( {
		type: ACTION.SET_ACTIVE_PAYMENT_METHOD,
		paymentMethod,
		paymentMethodData,
	} ),
};

export default actions;
