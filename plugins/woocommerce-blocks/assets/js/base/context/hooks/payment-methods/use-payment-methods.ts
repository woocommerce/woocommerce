/**
 * External dependencies
 */
import { useShallowEqual } from '@woocommerce/base-hooks';
import type {
	PaymentMethods,
	ExpressPaymentMethods,
} from '@woocommerce/type-defs/payments';
import {
	getPaymentMethods,
	getExpressPaymentMethods,
} from '@woocommerce/blocks-registry';
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
	const { paymentMethodsInitialized, expressPaymentMethodsInitialized } =
		useSelect( ( select ) => {
			const store = select( PAYMENT_METHOD_DATA_STORE_KEY );

			return {
				paymentMethodsInitialized: store.paymentMethodsInitialized(),
				expressPaymentMethodsInitialized:
					store.expressPaymentMethodsInitialized(),
			};
		} );

	const paymentMethods = getPaymentMethods();
	const expressPaymentMethods = getExpressPaymentMethods();

	const currentPaymentMethods = useShallowEqual( paymentMethods );
	const currentExpressPaymentMethods = useShallowEqual(
		expressPaymentMethods
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
