/**
 * External dependencies
 */
import { usePaymentMethodDataContext } from '@woocommerce/base-context';
import { usePaymentMethods } from '@woocommerce/base-hooks';
import { useEffect } from '@wordpress/element';

const useActivePaymentMethod = () => {
	const {
		activePaymentMethod,
		setActivePaymentMethod,
	} = usePaymentMethodDataContext();
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
					? paymentMethods[ paymentMethodIds[ 0 ] ].id
					: null
			);
		}
	}, [
		activePaymentMethod,
		setActivePaymentMethod,
		isInitialized,
		paymentMethods,
	] );
	return { activePaymentMethod, setActivePaymentMethod };
};

export default useActivePaymentMethod;
