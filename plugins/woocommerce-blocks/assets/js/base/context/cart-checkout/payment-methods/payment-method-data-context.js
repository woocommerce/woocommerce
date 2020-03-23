/**
 * Internal dependencies
 */
import {
	STATUS,
	DEFAULT_PAYMENT_DATA,
	DEFAULT_PAYMENT_METHOD_DATA,
} from './constants';
import reducer from './reducer';
import { statusOnly, error, failed, success } from './actions';

/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useState,
	useReducer,
} from '@wordpress/element';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentMethodDataContext} PaymentMethodDataContext
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatch} PaymentStatusDispatch
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatchers} PaymentStatusDispatchers
 * @typedef {import('@woocommerce/type-defs/cart').CartBillingData} CartBillingData
 */

const {
	STARTED,
	PROCESSING,
	COMPLETE,
	PRISTINE,
	ERROR,
	FAILED,
	SUCCESS,
} = STATUS;

const PaymentMethodDataContext = createContext( DEFAULT_PAYMENT_METHOD_DATA );

/**
 * @return {PaymentMethodDataContext} The data and functions exposed by the
 *                                    payment method context provider.
 */
export const usePaymentMethodDataContext = () => {
	return useContext( PaymentMethodDataContext );
};

// @todo implement billing (and payment method) data saved to the cart data
// store. That way checkout can obtain it for processing the order.

/**
 * PaymentMethodDataProvider is automatically included in the
 * CheckoutDataProvider.
 *
 * This provides the api interface (via the context hook) for payment method
 * status and data.
 *
 * @param {Object} props                     Incoming props for provider
 * @param {Array}  props.children            The wrapped components in this
 *                                           provider.
 * @param {string} props.activePaymentMethod The initial active payment method
 *                                           to set for the context.
 */
export const PaymentMethodDataProvider = ( {
	children,
	activePaymentMethod: initialActivePaymentMethod,
} ) => {
	const [ activePaymentMethod, setActive ] = useState(
		initialActivePaymentMethod
	);
	const [ paymentStatus, dispatch ] = useReducer(
		reducer,
		DEFAULT_PAYMENT_DATA
	);
	const setActivePaymentMethod = ( paymentMethodSlug ) => {
		setActive( paymentMethodSlug );
		dispatch( statusOnly( PRISTINE ) );
	};

	/**
	 * @type {PaymentStatusDispatch}
	 */
	const setPaymentStatus = () => ( {
		started: () => dispatch( statusOnly( STARTED ) ),
		processing: () => dispatch( statusOnly( PROCESSING ) ),
		completed: () => dispatch( statusOnly( COMPLETE ) ),
		/**
		 * @param {string} errorMessage An error message
		 */
		error: ( errorMessage ) => dispatch( error( errorMessage ) ),
		/**
		 * @param {string} errorMessage An error message
		 * @param {CartBillingData} billingData The billing data accompanying the payment method
		 * @param {Object} paymentMethodData Arbitrary payment method data to accompany the checkout submission.
		 */
		failed: ( errorMessage, billingData, paymentMethodData ) =>
			dispatch(
				failed( {
					errorMessage,
					billingData,
					paymentMethodData,
				} )
			),
		/**
		 * @param {CartBillingData} billingData The billing data accompanying the payment method.
		 * @param {Object} paymentMethodData Arbitrary payment method data to accompany the checkout.
		 */
		success: ( billingData, paymentMethodData ) =>
			dispatch(
				success( {
					billingData,
					paymentMethodData,
				} )
			),
	} );

	const currentStatus = {
		isPristine: paymentStatus === PRISTINE,
		isStarted: paymentStatus === STARTED,
		isProcessing: paymentStatus === PROCESSING,
		isFinished: [ ERROR, FAILED, SUCCESS ].includes( paymentStatus ),
		hasError: paymentStatus === ERROR,
		hasFailed: paymentStatus === FAILED,
		isSuccessful: paymentStatus === SUCCESS,
	};
	/**
	 * @type {PaymentMethodDataContext}
	 */
	const paymentData = {
		setPaymentStatus,
		currentStatus,
		paymentStatuses: STATUS,
		paymentMethodData: paymentStatus.paymentMethodData,
		errorMessage: paymentStatus.errorMessage,
		activePaymentMethod,
		setActivePaymentMethod,
	};
	return (
		<PaymentMethodDataContext.Provider value={ paymentData }>
			{ children }
		</PaymentMethodDataContext.Provider>
	);
};
