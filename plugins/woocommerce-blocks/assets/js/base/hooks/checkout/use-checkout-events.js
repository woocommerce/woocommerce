/**
 * External dependencies
 */
import useCheckoutContext from '@woocommerce/base-context/checkout-context';

export const useCheckoutEvents = () => {
	const {
		isCheckoutComplete,
		setIsCheckoutComplete,
		checkoutHasError,
		setCheckoutHasError,
		isCalculating,
		setIsCalculating,
	} = useCheckoutContext();
	const setHasError = () => {
		setCheckoutHasError( true );
	};
	const cancelCheckoutError = () => {
		setCheckoutHasError( false );
	};
	const setComplete = () => {
		cancelCheckoutError();
		setIsCheckoutComplete( true );
	};
	const setCalculating = () => {
		setIsCalculating( true );
	};
	const cancelCalculating = () => {
		setIsCalculating( false );
	};
	return {
		setIsCheckoutComplete: setComplete,
		setCheckoutHasError: setHasError,
		cancelCheckoutError,
		setIsCalculating: setCalculating,
		cancelCalculating,
		isCalculating,
		isCheckoutComplete,
		checkoutHasError,
	};
};
