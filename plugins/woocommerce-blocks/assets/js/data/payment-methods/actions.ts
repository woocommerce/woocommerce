/**
 * External dependencies
 */
import { select as wpDataSelect } from '@wordpress/data';
import {
	PaymentMethods,
	ExpressPaymentMethods,
} from '@woocommerce/type-defs/payments';

/**
 * Internal dependencies
 */
import { ACTION_TYPES } from './action-types';
import { checkPaymentMethodsCanPay } from './check-payment-methods';
import { setDefaultPaymentMethod } from './set-default-payment-method';
import { PaymentStatus } from './types';
import { CART_STORE_KEY } from '../cart';

// `Thunks are functions that can be dispatched, similar to actions creators
export * from './thunks';

export const setPaymentStatus = (
	status: PaymentStatus,
	paymentMethodData?: Record< string, unknown >
) => ( {
	type: ACTION_TYPES.SET_PAYMENT_STATUS,
	status,
	paymentMethodData,
} );

export const setPaymentMethodsInitialized = ( initialized: boolean ) => {
	return async ( { select, dispatch } ) => {
		// If the currently selected method is not in this new list, then we need to select a new one, or select a default.
		const methods = select.getAvailablePaymentMethods();
		if ( initialized ) {
			await setDefaultPaymentMethod( methods );
		}
		dispatch( {
			type: ACTION_TYPES.SET_PAYMENT_METHODS_INITIALIZED,
			initialized,
		} );
	};
};

export const setExpressPaymentMethodsInitialized = (
	initialized: boolean
) => ( {
	type: ACTION_TYPES.SET_EXPRESS_PAYMENT_METHODS_INITIALIZED,
	initialized,
} );

export const setShouldSavePaymentMethod = (
	shouldSavePaymentMethod: boolean
) => ( {
	type: ACTION_TYPES.SET_SHOULD_SAVE_PAYMENT_METHOD,
	shouldSavePaymentMethod,
} );

export const setActivePaymentMethod = (
	activePaymentMethod: string,
	paymentMethodData: Record< string, unknown > = {}
) => ( {
	type: ACTION_TYPES.SET_ACTIVE_PAYMENT_METHOD,
	activePaymentMethod,
	paymentMethodData,
} );

export const setPaymentMethodData = (
	paymentMethodData: Record< string, unknown > = {}
) => ( {
	type: ACTION_TYPES.SET_PAYMENT_METHOD_DATA,
	paymentMethodData,
} );

/**
 * Set the available payment methods.
 * An available payment method is one that has been validated and can make a payment.
 */
export const setAvailablePaymentMethods = (
	paymentMethods: PaymentMethods
) => {
	return async ( { dispatch } ) => {
		// If the currently selected method is not in this new list, then we need to select a new one, or select a default.
		await setDefaultPaymentMethod( paymentMethods );
		dispatch( {
			type: ACTION_TYPES.SET_AVAILABLE_PAYMENT_METHODS,
			paymentMethods,
		} );
	};
};

/**
 * Set the available express payment methods.
 * An available payment method is one that has been validated and can make a payment.
 */
export const setAvailableExpressPaymentMethods = (
	paymentMethods: ExpressPaymentMethods
) => ( {
	type: ACTION_TYPES.SET_AVAILABLE_EXPRESS_PAYMENT_METHODS,
	paymentMethods,
} );

/**
 * Remove a payment method name from the available payment methods.
 * This is called when a payment method is removed from the registry.
 */
export const removeAvailablePaymentMethod = ( name: string ) => ( {
	type: ACTION_TYPES.REMOVE_AVAILABLE_PAYMENT_METHOD,
	name,
} );

/**
 * Remove an express payment method name from the available payment methods.
 * This is called when an express payment method is removed from the registry.
 */
export const removeRegisteredExpressPaymentMethod = ( name: string ) => ( {
	type: ACTION_TYPES.REMOVE_AVAILABLE_EXPRESS_PAYMENT_METHOD,
	name,
} );

/**
 * Checks the payment methods held in the registry can make a payment
 * and updates the available payment methods in the store.
 */
export function updateAvailablePaymentMethods() {
	return async ( { dispatch } ) => {
		const registered = await checkPaymentMethodsCanPay();
		const cartTotalsLoaded =
			wpDataSelect( CART_STORE_KEY ).hasFinishedResolution(
				'getCartTotals'
			);
		if ( registered && cartTotalsLoaded ) {
			dispatch( setPaymentMethodsInitialized( true ) );
		}
	};
}

/**
 * Checks the express payment methods held in the registry can make a payment
 * and updates the available express payment methods in the store.
 */
export function updateAvailableExpressPaymentMethods() {
	return async ( { dispatch } ) => {
		const registered = await checkPaymentMethodsCanPay( true );
		const cartTotalsLoaded =
			wpDataSelect( CART_STORE_KEY ).hasFinishedResolution(
				'getCartTotals'
			);
		if ( registered && cartTotalsLoaded ) {
			dispatch( setExpressPaymentMethodsInitialized( true ) );
		}
	};
}
