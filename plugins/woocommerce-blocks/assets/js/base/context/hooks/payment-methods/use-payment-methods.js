/**
 * External dependencies
 */
import { useShallowEqual } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { usePaymentMethodDataContext } from '../../providers/cart-checkout/payment-methods';

const usePaymentMethodState = ( express = false ) => {
	const {
		paymentMethods,
		expressPaymentMethods,
		paymentMethodsInitialized,
		expressPaymentMethodsInitialized,
	} = usePaymentMethodDataContext();

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

export const usePaymentMethods = () => usePaymentMethodState();
export const useExpressPaymentMethods = () => usePaymentMethodState( true );
