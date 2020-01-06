/**
 * External dependencies
 */
import { useCheckoutContext } from '@woocommerce/base-context/checkout-context';
import { usePaymentMethods } from '@woocommerce/base-hooks';
import { useEffect } from '@wordpress/element';

const useActivePaymentMethod = () => {
	const {
		activePaymentMethod,
		setActivePaymentMethod,
	} = useCheckoutContext();
	const { paymentMethods, isInitialized } = usePaymentMethods();
	// if payment method has not been set yet, let's set it.
	useEffect( () => {
		// if not initialized yet bail
		if ( ! isInitialized ) {
			return;
		}
		if ( ! activePaymentMethod && activePaymentMethod !== null ) {
			const paymentMethodIds = Object.keys( paymentMethods );
			setActivePaymentMethod(
				paymentMethodIds.length > 0
					? paymentMethods[ paymentMethodIds[ 0 ] ].name
					: null
			);
		}
	}, [ activePaymentMethod, setActivePaymentMethod, isInitialized ] );
	return { activePaymentMethod, setActivePaymentMethod };
};

export default useActivePaymentMethod;
