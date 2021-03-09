/**
 * Internal dependencies
 */
import { ACTION, STATUS } from './constants';
import type { PaymentMethods } from './types';

export interface ActionType {
	type: ACTION | STATUS;
	errorMessage?: string;
	paymentMethodData?: Record< string, unknown >;
	paymentMethods?: PaymentMethods;
	shouldSavePaymentMethod?: boolean;
}

/**
 * Used to dispatch a status update only for the given type.
 */
export const statusOnly = ( type: STATUS ): { type: STATUS } => ( { type } );

/**
 * Used to dispatch an error message along with setting current payment status to ERROR.
 *
 * @param {string} errorMessage Whatever error message accompanying the error condition.
 * @return {ActionType} The action object.
 */
export const error = ( errorMessage: string ): ActionType => ( {
	type: STATUS.ERROR,
	errorMessage,
} );

/**
 * Used to dispatch a payment failed status update.
 */
export const failed = ( {
	errorMessage,
	paymentMethodData,
}: {
	errorMessage: string;
	paymentMethodData: Record< string, unknown >;
} ): ActionType => ( {
	type: STATUS.FAILED,
	errorMessage,
	paymentMethodData,
} );

/**
 * Used to dispatch a payment success status update.
 */
export const success = ( {
	paymentMethodData,
}: {
	paymentMethodData: Record< string, unknown >;
} ): ActionType => ( {
	type: STATUS.SUCCESS,
	paymentMethodData,
} );

/**
 * Used to dispatch an action for updating a registered payment method in the state.
 *
 * @param {Object} paymentMethods Payment methods to register.
 * @return {Object} An action object.
 */
export const setRegisteredPaymentMethods = (
	paymentMethods: PaymentMethods
): ActionType => ( {
	type: ACTION.SET_REGISTERED_PAYMENT_METHODS,
	paymentMethods,
} );

/**
 * Used to dispatch an action for updating a registered express payment method in the state.
 *
 * @param {Object} paymentMethods Payment methods to register.
 * @return {Object} An action object.
 */
export const setRegisteredExpressPaymentMethods = (
	paymentMethods: PaymentMethods
): ActionType => ( {
	type: ACTION.SET_REGISTERED_EXPRESS_PAYMENT_METHODS,
	paymentMethods,
} );

/**
 * Set a flag indicating that the payment method info (e.g. a payment card) should be saved to user account after order completion.
 *
 * @param {boolean} shouldSavePaymentMethod
 * @return {Object} An action object.
 */
export const setShouldSavePaymentMethod = (
	shouldSavePaymentMethod: boolean
): ActionType => ( {
	type: ACTION.SET_SHOULD_SAVE_PAYMENT_METHOD,
	shouldSavePaymentMethod,
} );
