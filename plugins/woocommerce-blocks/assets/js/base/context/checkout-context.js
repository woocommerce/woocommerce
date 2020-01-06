/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useState,
	useMemo,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const CheckoutContext = createContext( {} );

export const useCheckoutContext = () => {
	return useContext( CheckoutContext );
};

const CheckoutProvider = ( {
	children,
	initialActivePaymentMethod,
	placeOrderLabel = __( 'Place Order', 'woo-gutenberg-product-block' ),
} ) => {
	const [ successRedirectUrl, setSuccessRedirectUrl ] = useState( '' );
	const [ failureRedirectUrl, setFailureRedirectUrl ] = useState( '' );
	const [ isCheckoutComplete, setIsCheckoutComplete ] = useState( false );
	const [ checkoutHasError, setCheckoutHasError ] = useState( false );
	const [ notices, updateNotices ] = useState( [] );
	const [ isCalculating, setIsCalculating ] = useState( false );
	const [ activePaymentMethod, setActivePaymentMethod ] = useState(
		initialActivePaymentMethod
	);
	const contextValue = useMemo( () => {
		return {
			successRedirectUrl,
			setSuccessRedirectUrl,
			failureRedirectUrl,
			setFailureRedirectUrl,
			isCheckoutComplete,
			setIsCheckoutComplete,
			checkoutHasError,
			setCheckoutHasError,
			isCalculating,
			setIsCalculating,
			notices,
			updateNotices,
			activePaymentMethod,
			setActivePaymentMethod,
			placeOrderLabel,
		};
	}, [
		successRedirectUrl,
		failureRedirectUrl,
		isCheckoutComplete,
		isCalculating,
		checkoutHasError,
		activePaymentMethod,
		placeOrderLabel,
		notices,
	] );
	return (
		<CheckoutContext.Provider value={ contextValue }>
			{ children }
		</CheckoutContext.Provider>
	);
};

export default CheckoutProvider;
