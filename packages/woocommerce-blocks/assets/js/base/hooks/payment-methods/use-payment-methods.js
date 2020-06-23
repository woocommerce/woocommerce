/**
 * External dependencies
 */
import { usePaymentMethodDataContext } from '@woocommerce/base-context';

const usePaymentMethodState = ( express = false ) => {
	const {
		paymentMethods,
		expressPaymentMethods,
		paymentMethodsInitialized,
		expressPaymentMethodsInitialized,
	} = usePaymentMethodDataContext();
	return express
		? {
				paymentMethods: expressPaymentMethods,
				isInitialized: expressPaymentMethodsInitialized,
		  }
		: { paymentMethods, isInitialized: paymentMethodsInitialized };
};

export const usePaymentMethods = () => usePaymentMethodState();
export const useExpressPaymentMethods = () => usePaymentMethodState( true );
