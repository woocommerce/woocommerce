/**
 * External dependencies
 */
import { useShallowEqual } from '@woocommerce/base-hooks';
import type {
	PaymentMethods,
	ExpressPaymentMethods,
	PaymentMethodConfigInstance,
	ExpressPaymentMethodConfigInstance,
} from '@woocommerce/types';
import {
	getPaymentMethods,
	getExpressPaymentMethods,
} from '@woocommerce/blocks-registry';
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';

interface PaymentMethodState {
	paymentMethods: PaymentMethods;
	isInitialized: boolean;
}
interface ExpressPaymentMethodState {
	paymentMethods: ExpressPaymentMethods;
	isInitialized: boolean;
}

const usePaymentMethodState = (
	express = false
): PaymentMethodState | ExpressPaymentMethodState => {
	const {
		paymentMethodsInitialized,
		expressPaymentMethodsInitialized,
		availablePaymentMethods,
		availableExpressPaymentMethods,
	} = useSelect( ( select ) => {
		const store = select( paymentStore );

		return {
			paymentMethodsInitialized: store.paymentMethodsInitialized(),
			expressPaymentMethodsInitialized:
				store.expressPaymentMethodsInitialized(),
			availableExpressPaymentMethods:
				store.getAvailableExpressPaymentMethods(),
			availablePaymentMethods: store.getAvailablePaymentMethods(),
		};
	} );

	const availablePaymentMethodNames = Object.values(
		availablePaymentMethods
	).map( ( { name } ) => name );
	const availableExpressPaymentMethodNames = Object.values(
		availableExpressPaymentMethods
	).map( ( { name } ) => name );

	const registeredPaymentMethods = getPaymentMethods();
	const registeredExpressPaymentMethods = getExpressPaymentMethods();

	// Remove everything from registeredPaymentMethods that is not in availablePaymentMethodNames.
	const paymentMethods = Object.keys( registeredPaymentMethods ).reduce(
		( acc: Record< string, PaymentMethodConfigInstance >, key ) => {
			if ( availablePaymentMethodNames.includes( key ) ) {
				acc[ key ] = registeredPaymentMethods[ key ];
			}
			return acc;
		},
		{}
	);
	// Remove everything from registeredExpressPaymentMethods that is not in availableExpressPaymentMethodNames.
	const expressPaymentMethods = Object.keys(
		registeredExpressPaymentMethods
	).reduce(
		( acc: Record< string, ExpressPaymentMethodConfigInstance >, key ) => {
			if ( availableExpressPaymentMethodNames.includes( key ) ) {
				acc[ key ] = registeredExpressPaymentMethods[ key ];
			}
			return acc;
		},
		{}
	);

	// Filter out express payment methods if fraud protection is active.
	const filteredExpressPaymentMethods =
		typeof window !== 'undefined' &&
		window.wcFraudProtection &&
		window.wcFraudProtection.sessionStatus !== 'allowed' &&
		( window.wcFraudProtection.applyTo === 'checkout' ||
			window.wcFraudProtection.applyTo === 'both' )
			? {}
			: expressPaymentMethods;

	const currentPaymentMethods = useShallowEqual( paymentMethods );
	const currentExpressPaymentMethods = useShallowEqual(
		filteredExpressPaymentMethods
	);

	return {
		paymentMethods: express
			? currentExpressPaymentMethods
			: currentPaymentMethods,
		isInitialized: express
			? expressPaymentMethodsInitialized
			: paymentMethodsInitialized,
	};
};

export const usePaymentMethods = ():
	| PaymentMethodState
	| ExpressPaymentMethodState => usePaymentMethodState( false );
export const useExpressPaymentMethods = (): ExpressPaymentMethodState =>
	usePaymentMethodState( true );
