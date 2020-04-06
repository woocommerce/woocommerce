/**
 * Internal dependencies
 */
import {
	STATUS,
	DEFAULT_PAYMENT_DATA,
	DEFAULT_PAYMENT_METHOD_DATA,
} from './constants';
import reducer from './reducer';
import {
	statusOnly,
	error,
	failed,
	success,
	setRegisteredPaymentMethod,
	setRegisteredExpressPaymentMethod,
} from './actions';
import {
	usePaymentMethods,
	useExpressPaymentMethods,
} from './use-payment-method-registration';
import { useBillingDataContext } from '../billing';
import { useCheckoutContext } from '../checkout-state';
import {
	EMIT_TYPES,
	emitterSubscribers,
	emitEvent,
	emitEventWithAbort,
	reducer as emitReducer,
} from './event-emit';
import { useValidationContext } from '../validation';

/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useState,
	useReducer,
	useCallback,
	useEffect,
	useRef,
	useMemo,
} from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';
import { useStoreNotices } from '@woocommerce/base-hooks';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentMethodDataContext} PaymentMethodDataContext
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatch} PaymentStatusDispatch
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatchers} PaymentStatusDispatchers
 * @typedef {import('@woocommerce/type-defs/billing').BillingData} BillingData
 * @typedef {import('@woocommerce/type-defs/contexts').CustomerPaymentMethod} CustomerPaymentMethod
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

const isSuccessResponse = ( response ) => {
	return (
		( typeof response === 'object' &&
			typeof response.billingData !== 'undefined' &&
			typeof response.paymentMethodData !== 'undefined' ) ||
		response === true
	);
};

const isFailResponse = ( response ) => {
	return response && typeof response.fail === 'object';
};

const isErrorResponse = ( response ) => {
	return response && typeof response.errorMessage !== 'undefined';
};

/**
 * PaymentMethodDataProvider is automatically included in the
 * CheckoutDataProvider.
 *
 * This provides the api interface (via the context hook) for payment method
 * status and data.
 *
 * @param {Object} props                     Incoming props for provider
 * @param {Object} props.children            The wrapped components in this
 *                                           provider.
 * @param {string} props.activePaymentMethod The initial active payment method
 *                                           to set for the context.
 */
export const PaymentMethodDataProvider = ( {
	children,
	activePaymentMethod: initialActivePaymentMethod,
} ) => {
	const { setBillingData } = useBillingDataContext();
	const {
		isComplete: checkoutIsComplete,
		isProcessingComplete: checkoutIsProcessingComplete,
		hasError: checkoutHasError,
	} = useCheckoutContext();
	const [ activePaymentMethod, setActive ] = useState(
		initialActivePaymentMethod
	);
	const [ observers, subscriber ] = useReducer( emitReducer, {} );
	const currentObservers = useRef( observers );
	const customerPaymentMethods = getSetting( 'customerPaymentMethods', {} );
	const [ paymentStatus, dispatch ] = useReducer(
		reducer,
		DEFAULT_PAYMENT_DATA
	);
	const setActivePaymentMethod = ( paymentMethodSlug ) => {
		setActive( paymentMethodSlug );
		dispatch( statusOnly( PRISTINE ) );
	};
	const paymentMethodsInitialized = usePaymentMethods( ( paymentMethod ) =>
		dispatch( setRegisteredPaymentMethod( paymentMethod ) )
	);
	const expressPaymentMethodsInitialized = useExpressPaymentMethods(
		( paymentMethod ) => {
			dispatch( setRegisteredExpressPaymentMethod( paymentMethod ) );
		}
	);
	const { setValidationErrors } = useValidationContext();
	const { addErrorNotice, removeNotice } = useStoreNotices();

	const setExpressPaymentError = ( message ) => {
		addErrorNotice( message, {
			context: 'wc/express-payment-area',
			id: 'wc-express-payment-error',
		} );
		if ( ! message ) {
			removeNotice( 'wc-express-payment-error' );
		}
	};
	// ensure observers are always current.
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );
	const onPaymentProcessing = useMemo(
		() => emitterSubscribers( subscriber ).onPaymentProcessing,
		[ subscriber ]
	);
	const onPaymentSuccess = useMemo(
		() => emitterSubscribers( subscriber ).onPaymentSuccess,
		[ subscriber ]
	);
	const onPaymentFail = useMemo(
		() => emitterSubscribers( subscriber ).onPaymentFail,
		[ subscriber ]
	);
	const onPaymentError = useMemo(
		() => emitterSubscribers( subscriber ).onPaymentError,
		[ subscriber ]
	);

	// flip payment to processing if checkout processing is complete and there
	// are no errors.
	useEffect( () => {
		if ( checkoutIsProcessingComplete && ! checkoutHasError ) {
			setPaymentStatus().processing();
		}
	}, [ checkoutIsProcessingComplete, checkoutHasError ] );

	// set initial active payment method if it's undefined.
	useEffect( () => {
		const paymentMethodKeys = Object.keys( paymentStatus.paymentMethods );
		if (
			paymentMethodsInitialized &&
			! activePaymentMethod &&
			paymentMethodKeys.length > 0
		) {
			setActivePaymentMethod(
				Object.keys( paymentStatus.paymentMethods )[ 0 ]
			);
		}
	}, [
		activePaymentMethod,
		paymentMethodsInitialized,
		paymentStatus.paymentMethods,
	] );

	const currentStatus = useMemo(
		() => ( {
			isPristine: paymentStatus.currentStatus === PRISTINE,
			isStarted: paymentStatus.currentStatus === STARTED,
			isProcessing: paymentStatus.currentStatus === PROCESSING,
			isFinished: [ ERROR, FAILED, SUCCESS ].includes(
				paymentStatus.currentStatus
			),
			hasError: paymentStatus.currentStatus === ERROR,
			hasFailed: paymentStatus.currentStatus === FAILED,
			isSuccessful: paymentStatus.currentStatus === SUCCESS,
		} ),
		[ paymentStatus.currentStatus ]
	);

	/**
	 * @type {PaymentStatusDispatch}
	 */
	const setPaymentStatus = useCallback(
		() => ( {
			started: () => dispatch( statusOnly( STARTED ) ),
			processing: () => dispatch( statusOnly( PROCESSING ) ),
			completed: () => dispatch( statusOnly( COMPLETE ) ),
			/**
			 * @param {string} errorMessage An error message
			 */
			error: ( errorMessage ) => dispatch( error( errorMessage ) ),
			/**
			 * @param {string}           errorMessage      An error message
			 * @param {Object}           paymentMethodData Arbitrary payment method data to
			 *                                             accompany the checkout submission.
			 * @param {BillingData|null} [billingData]     The billing data accompanying the
			 *                                             payment method.
			 */
			failed: ( errorMessage, paymentMethodData, billingData = null ) => {
				if ( billingData ) {
					setBillingData( billingData );
				}
				dispatch(
					failed( {
						errorMessage,
						paymentMethodData,
					} )
				);
			},
			/**
			 * @param {Object}           [paymentMethodData] Arbitrary payment method data to
			 *                                               accompany the checkout.
			 * @param {BillingData|null} [billingData]       The billing data accompanying the
			 *                                               payment method.
			 */
			success: ( paymentMethodData = {}, billingData = null ) => {
				if ( billingData ) {
					setBillingData( billingData );
				}
				dispatch(
					success( {
						paymentMethodData,
					} )
				);
			},
		} ),
		[ dispatch ]
	);

	// emit events.
	useEffect( () => {
		// Note: the nature of this event emitter is that it will bail on a
		// successful payment (that is an observer hooked in that returns an
		// object in the shape of a successful payment). However, this still
		// allows for other observers that return true for continuing through
		// to the next observer (or bailing if there's a problem).
		if ( currentStatus.isProcessing ) {
			emitEventWithAbort(
				currentObservers.current,
				EMIT_TYPES.PAYMENT_PROCESSING,
				{}
			).then( ( response ) => {
				if ( isSuccessResponse( response ) ) {
					setPaymentStatus().success(
						response.paymentMethodData,
						response.billingData
					);
				} else if ( isFailResponse( response ) ) {
					setPaymentStatus().failed(
						response.fail.errorMessage,
						response.fail.paymentMethodData,
						response.fail.billingData
					);
				} else if ( isErrorResponse( response ) ) {
					setPaymentStatus().error( response.errorMessage );
					setValidationErrors( response.validationErrors );
				}
			} );
		}
		if (
			currentStatus.isSuccessful &&
			checkoutIsComplete &&
			! checkoutHasError
		) {
			emitEvent(
				currentObservers.current,
				EMIT_TYPES.PAYMENT_SUCCESS,
				{}
			).then( () => {
				setPaymentStatus().completed();
			} );
		}
		if ( currentStatus.hasFailed ) {
			emitEvent( currentObservers.current, EMIT_TYPES.PAYMENT_FAIL, {} );
		}
		if ( currentStatus.hasError ) {
			emitEvent( currentObservers.current, EMIT_TYPES.PAYMENT_ERROR, {} );
		}
	}, [
		currentStatus,
		setValidationErrors,
		setPaymentStatus,
		checkoutIsComplete,
		checkoutHasError,
	] );

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
		onPaymentProcessing,
		onPaymentSuccess,
		onPaymentFail,
		onPaymentError,
		customerPaymentMethods,
		paymentMethods: paymentStatus.paymentMethods,
		expressPaymentMethods: paymentStatus.expressPaymentMethods,
		paymentMethodsInitialized,
		expressPaymentMethodsInitialized,
		setExpressPaymentError,
	};
	return (
		<PaymentMethodDataContext.Provider value={ paymentData }>
			{ children }
		</PaymentMethodDataContext.Provider>
	);
};
