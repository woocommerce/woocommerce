/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './constants';

const {
	ERROR,
	FAILED,
	SUCCESS,
	SET_REGISTERED_PAYMENT_METHODS,
	SET_REGISTERED_EXPRESS_PAYMENT_METHODS,
	SET_SHOULD_SAVE_PAYMENT_METHOD,
} = ACTION_TYPES;

/**
 * Used to dispatch a status update only for the given type.
 *
 * @param {string} type
 *
 * @return {Object} The action object.
 */
export const statusOnly = ( type ) => ( { type } );

/**
 * Used to dispatch an error message along with setting current payment status
 * to ERROR.
 *
 * @param {string} errorMessage Whatever error message accompanying the error
 *                              condition.
 *
 * @return {Object} The action object.
 */
export const error = ( errorMessage ) => ( {
	type: ERROR,
	errorMessage,
} );

/**
 * Used to dispatch a payment failed status update.
 *
 * @param {Object}             action                   Incoming data for the
 *                                                      action.
 * @param {string}             action.errorMessage      Any message accompanying
 *                                                      the failed payment.
 * @param {Object}             action.paymentMethodData Arbitrary extra
 *                                                      information about the
 *                                                      payment method in use
 *                                                      (varies per payment
 *                                                      method).
 *
 * @return {Object} An action object.
 */
export const failed = ( { errorMessage, paymentMethodData } ) => ( {
	type: FAILED,
	errorMessage,
	paymentMethodData,
} );

/**
 * Used to dispatch a payment success status update.
 *
 * @param {Object}             action                   Incoming data for the
 *                                                      action.
 * @param {Object}             action.paymentMethodData Arbitrary extra
 *                                                      information about the
 *                                                      payment method in use
 *                                                      (varies per payment
 *                                                      method).
 *
 * @return {Object} An action object.
 */
export const success = ( { paymentMethodData } ) => ( {
	type: SUCCESS,
	paymentMethodData,
} );

/**
 * Used to dispatch an action for updating a registered payment method in the
 * state.
 *
 * @param {Object} paymentMethods Payment methods to register.
 * @return {Object} An action object.
 */
export const setRegisteredPaymentMethods = ( paymentMethods ) => ( {
	type: SET_REGISTERED_PAYMENT_METHODS,
	paymentMethods,
} );

/**
 * Used to dispatch an action for updating a registered express payment
 * method in the state.
 *
 * @param {Object} paymentMethods Payment methods to register.
 * @return {Object} An action object.
 */
export const setRegisteredExpressPaymentMethods = ( paymentMethods ) => ( {
	type: SET_REGISTERED_EXPRESS_PAYMENT_METHODS,
	paymentMethods,
} );

/**
 * Set a flag indicating that the payment method info (e.g. a payment card)
 * should be saved to user account after order completion.
 *
 * @param {boolean} shouldSavePaymentMethod
 * @return {Object} An action object.
 */
export const setShouldSavePaymentMethod = ( shouldSavePaymentMethod ) => ( {
	type: SET_SHOULD_SAVE_PAYMENT_METHOD,
	shouldSavePaymentMethod,
} );
