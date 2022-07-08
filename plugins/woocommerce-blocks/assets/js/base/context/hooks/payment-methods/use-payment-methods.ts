/**
 * External dependencies
 */
import { useShallowEqual } from '@woocommerce/base-hooks';
import type {
	PaymentMethods,
	ExpressPaymentMethods,
} from '@woocommerce/type-defs/payments';
import { useSelect } from '@wordpress/data';
import { PAYMENT_METHOD_DATA_STORE_KEY } from '@woocommerce/block-data';

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
		const store = select( PAYMENT_METHOD_DATA_STORE_KEY );

		return {
			paymentMethodsInitialized: store.paymentMethodsInitialized(),
			expressPaymentMethodsInitialized:
				store.expressPaymentMethodsInitialized(),
			availablePaymentMethods: store.getAvailablePaymentMethods(),
			availableExpressPaymentMethods:
				store.getAvailableExpressPaymentMethods(),
		};
	} );

	const currentPaymentMethods = useShallowEqual( availablePaymentMethods );
	const currentExpressPaymentMethods = useShallowEqual(
		availableExpressPaymentMethods
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
