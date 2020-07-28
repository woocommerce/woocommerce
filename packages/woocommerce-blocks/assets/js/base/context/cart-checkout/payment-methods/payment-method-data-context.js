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
import { useStoreNotices, useEmitResponse } from '@woocommerce/base-hooks';
import { useEditorContext } from '@woocommerce/base-context';

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
	setRegisteredPaymentMethods,
	setRegisteredExpressPaymentMethods,
	setShouldSavePaymentMethod,
} from './actions';
import {
	usePaymentMethods,
	useExpressPaymentMethods,
} from './use-payment-method-registration';
import { useBillingDataContext } from '../billing';
import { useCheckoutContext } from '../checkout-state';
import { useShippingDataContext } from '../shipping';
import {
	EMIT_TYPES,
	emitterSubscribers,
	emitEventWithAbort,
	reducer as emitReducer,
} from './event-emit';
import { useValidationContext } from '../validation';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentMethodDataContext} PaymentMethodDataContext
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatch} PaymentStatusDispatch
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentStatusDispatchers} PaymentStatusDispatchers
 * @typedef {import('@woocommerce/type-defs/billing').BillingData} BillingData
 * @typedef {import('@woocommerce/type-defs/contexts').CustomerPaymentMethod} CustomerPaymentMethod
 * @typedef {import('@woocommerce/type-defs/contexts').ShippingDataResponse} ShippingDataResponse
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
 */
export const PaymentMethodDataProvider = ( { children } ) => {
	const { setBillingData } = useBillingDataContext();
	const {
		isProcessing: checkoutIsProcessing,
		isIdle: checkoutIsIdle,
		isCalculating: checkoutIsCalculating,
		hasError: checkoutHasError,
	} = useCheckoutContext();
	const {
		isSuccessResponse,
		isErrorResponse,
		isFailResponse,
		noticeContexts,
	} = useEmitResponse();
	const [ activePaymentMethod, setActive ] = useState( '' );
	const [ observers, subscriber ] = useReducer( emitReducer, {} );
	const currentObservers = useRef( observers );

	const { isEditor, previewData } = useEditorContext();
	const customerPaymentMethods =
		isEditor && previewData?.previewSavedPaymentMethods
			? previewData?.previewSavedPaymentMethods
			: getSetting( 'customerPaymentMethods', {} );
	const [ paymentData, dispatch ] = useReducer(
		reducer,
		DEFAULT_PAYMENT_DATA
	);
	const setActivePaymentMethod = useCallback(
		( paymentMethodSlug ) => {
			setActive( paymentMethodSlug );
			dispatch( statusOnly( PRISTINE ) );
		},
		[ setActive, dispatch ]
	);
	const paymentMethodsDispatcher = useCallback(
		( paymentMethods ) => {
			dispatch( setRegisteredPaymentMethods( paymentMethods ) );
		},
		[ dispatch ]
	);
	const expressPaymentMethodsDispatcher = useCallback(
		( paymentMethods ) => {
			dispatch( setRegisteredExpressPaymentMethods( paymentMethods ) );
		},
		[ dispatch ]
	);
	const paymentMethodsInitialized = usePaymentMethods(
		paymentMethodsDispatcher
	);
	const expressPaymentMethodsInitialized = useExpressPaymentMethods(
		expressPaymentMethodsDispatcher
	);
	const { setValidationErrors } = useValidationContext();
	const { addErrorNotice, removeNotice } = useStoreNotices();
	const { setShippingAddress } = useShippingDataContext();
	const setShouldSavePayment = useCallback(
		( shouldSave ) => {
			dispatch( setShouldSavePaymentMethod( shouldSave ) );
		},
		[ dispatch ]
	);

	const setExpressPaymentError = useCallback(
		( message ) => {
			if ( message ) {
				addErrorNotice( message, {
					context: 'wc/express-payment-area',
					id: 'wc-express-payment-error',
				} );
			} else {
				removeNotice(
					'wc-express-payment-error',
					'wc/express-payment-area'
				);
			}
		},
		[ addErrorNotice, removeNotice ]
	);
	// ensure observers are always current.
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );
	const onPaymentProcessing = useMemo(
		() => emitterSubscribers( subscriber ).onPaymentProcessing,
		[ subscriber ]
	);

	const currentStatus = useMemo(
		() => ( {
			isPristine: paymentData.currentStatus === PRISTINE,
			isStarted: paymentData.currentStatus === STARTED,
			isProcessing: paymentData.currentStatus === PROCESSING,
			isFinished: [ ERROR, FAILED, SUCCESS ].includes(
				paymentData.currentStatus
			),
			hasError: paymentData.currentStatus === ERROR,
			hasFailed: paymentData.currentStatus === FAILED,
			isSuccessful: paymentData.currentStatus === SUCCESS,
		} ),
		[ paymentData.currentStatus ]
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
			 * @param {Object} [paymentMethodData] Arbitrary payment method data to
			 * accompany the checkout.
			 * @param {BillingData|null} [billingData] The billing data accompanying the
			 * payment method.
			 * @param {ShippingDataResponse|null} [shippingData] The shipping data accompanying the
			 * payment method.
			 */
			success: (
				paymentMethodData = {},
				billingData = null,
				shippingData = null
			) => {
				if ( billingData ) {
					setBillingData( billingData );
				}
				if ( shippingData !== null && shippingData?.address ) {
					setShippingAddress( shippingData.address );
				}
				dispatch(
					success( {
						paymentMethodData,
					} )
				);
			},
		} ),
		[ dispatch, setBillingData, setShippingAddress ]
	);

	// flip payment to processing if checkout processing is complete, there are
	// no errors, and payment status is started.
	useEffect( () => {
		if (
			checkoutIsProcessing &&
			! checkoutHasError &&
			! checkoutIsCalculating &&
			! currentStatus.isFinished
		) {
			setPaymentStatus().processing();
		}
	}, [
		checkoutIsProcessing,
		checkoutHasError,
		checkoutIsCalculating,
		currentStatus.isFinished,
		setPaymentStatus,
	] );

	// When checkout is returned to idle, set payment status to pristine
	// but only if payment status is already not finished.
	useEffect( () => {
		if ( checkoutIsIdle && ! currentStatus.isSuccessful ) {
			dispatch( statusOnly( PRISTINE ) );
		}
	}, [ checkoutIsIdle, currentStatus.isSuccessful ] );

	// if checkout has an error and payment is not being made with a saved token
	// and payment status is success, then let's sync payment status back to
	// pristine.
	useEffect( () => {
		if (
			checkoutHasError &&
			currentStatus.isSuccessful &&
			! paymentData.hasSavedToken
		) {
			dispatch( statusOnly( PRISTINE ) );
		}
	}, [
		checkoutHasError,
		currentStatus.isSuccessful,
		paymentData.hasSavedToken,
	] );

	// Set active (selected) payment method as needed.
	useEffect( () => {
		const paymentMethodKeys = Object.keys( paymentData.paymentMethods );
		if ( ! paymentMethodsInitialized || ! paymentMethodKeys.length ) {
			return;
		}

		// If there's no active payment method, or the active payment method has
		// been removed (e.g. COD vs shipping methods), set one as active.
		if (
			! activePaymentMethod ||
			! paymentMethodKeys.includes( activePaymentMethod )
		) {
			setActivePaymentMethod(
				Object.keys( paymentData.paymentMethods )[ 0 ]
			);
		}
	}, [
		activePaymentMethod,
		paymentMethodsInitialized,
		paymentData.paymentMethods,
		setActivePaymentMethod,
	] );

	// emit events.
	useEffect( () => {
		// Note: the nature of this event emitter is that it will bail on any
		// observer that returns a response that !== true. However, this still
		// allows for other observers that return true for continuing through
		// to the next observer (or bailing if there's a problem).
		if ( currentStatus.isProcessing ) {
			removeNotice( 'wc-payment-error', noticeContexts.PAYMENTS );
			emitEventWithAbort(
				currentObservers.current,
				EMIT_TYPES.PAYMENT_PROCESSING,
				{}
			).then( ( response ) => {
				if ( isSuccessResponse( response ) ) {
					setPaymentStatus().success(
						response?.meta?.paymentMethodData,
						response?.meta?.billingData,
						response?.meta?.shippingData
					);
				} else if ( isFailResponse( response ) ) {
					if ( response.message && response.message.length ) {
						addErrorNotice( response.message, {
							id: 'wc-payment-error',
							isDismissible: false,
							context:
								response?.messageContext ||
								noticeContexts.PAYMENTS,
						} );
					}
					setPaymentStatus().failed(
						response?.message,
						response?.meta?.paymentMethodData,
						response?.meta?.billingData
					);
				} else if ( isErrorResponse( response ) ) {
					if ( response.message && response.message.length ) {
						addErrorNotice( response.message, {
							id: 'wc-payment-error',
							isDismissible: false,
							context:
								response?.messageContext ||
								noticeContexts.PAYMENTS,
						} );
					}
					setPaymentStatus().error( response.message );
					setValidationErrors( response?.validationErrors );
				} else {
					// otherwise there are no payment methods doing anything so
					// just consider success
					setPaymentStatus().success();
				}
			} );
		}
	}, [
		currentStatus.isProcessing,
		setValidationErrors,
		setPaymentStatus,
		removeNotice,
		noticeContexts.PAYMENTS,
		isSuccessResponse,
		isFailResponse,
		isErrorResponse,
		addErrorNotice,
	] );

	/**
	 * @type {PaymentMethodDataContext}
	 */
	const paymentContextData = {
		setPaymentStatus,
		currentStatus,
		paymentStatuses: STATUS,
		paymentMethodData: paymentData.paymentMethodData,
		errorMessage: paymentData.errorMessage,
		activePaymentMethod,
		setActivePaymentMethod,
		onPaymentProcessing,
		customerPaymentMethods,
		paymentMethods: paymentData.paymentMethods,
		expressPaymentMethods: paymentData.expressPaymentMethods,
		paymentMethodsInitialized,
		expressPaymentMethodsInitialized,
		setExpressPaymentError,
		shouldSavePayment: paymentData.shouldSavePaymentMethod,
		setShouldSavePayment,
	};
	return (
		<PaymentMethodDataContext.Provider value={ paymentContextData }>
			{ children }
		</PaymentMethodDataContext.Provider>
	);
};
