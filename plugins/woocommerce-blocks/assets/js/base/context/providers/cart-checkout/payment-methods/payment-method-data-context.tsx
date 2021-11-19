/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useReducer,
	useCallback,
	useRef,
	useEffect,
	useMemo,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import type {
	CustomerPaymentMethods,
	PaymentMethodDataContextType,
} from './types';
import {
	STATUS,
	DEFAULT_PAYMENT_DATA_CONTEXT_STATE,
	DEFAULT_PAYMENT_METHOD_DATA,
} from './constants';
import reducer from './reducer';
import {
	usePaymentMethods,
	useExpressPaymentMethods,
} from './use-payment-method-registration';
import { usePaymentMethodDataDispatchers } from './use-payment-method-dispatchers';
import { useActivePaymentMethod } from './use-active-payment-method';
import { useCheckoutContext } from '../checkout-state';
import { useEditorContext } from '../../editor-context';
import {
	EMIT_TYPES,
	useEventEmitters,
	emitEventWithAbort,
	reducer as emitReducer,
} from './event-emit';
import { useValidationContext } from '../../validation';
import { useStoreNotices } from '../../../hooks/use-store-notices';
import { useEmitResponse } from '../../../hooks/use-emit-response';
import { getCustomerPaymentMethods } from './utils';

const PaymentMethodDataContext = createContext( DEFAULT_PAYMENT_METHOD_DATA );

export const usePaymentMethodDataContext = (): PaymentMethodDataContextType => {
	return useContext( PaymentMethodDataContext );
};

/**
 * PaymentMethodDataProvider is automatically included in the CheckoutDataProvider.
 *
 * This provides the api interface (via the context hook) for payment method status and data.
 *
 * @param {Object} props          Incoming props for provider
 * @param {Object} props.children The wrapped components in this provider.
 */
export const PaymentMethodDataProvider = ( {
	children,
}: {
	children: React.ReactNode;
} ): JSX.Element => {
	const {
		isProcessing: checkoutIsProcessing,
		isIdle: checkoutIsIdle,
		isCalculating: checkoutIsCalculating,
		hasError: checkoutHasError,
	} = useCheckoutContext();
	const { isEditor, getPreviewData } = useEditorContext();
	const { setValidationErrors } = useValidationContext();
	const { addErrorNotice, removeNotice } = useStoreNotices();
	const {
		isSuccessResponse,
		isErrorResponse,
		isFailResponse,
		noticeContexts,
	} = useEmitResponse();
	const [ observers, observerDispatch ] = useReducer( emitReducer, {} );
	const { onPaymentProcessing } = useEventEmitters( observerDispatch );
	const currentObservers = useRef( observers );

	// ensure observers are always current.
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );

	const [ paymentData, dispatch ] = useReducer(
		reducer,
		DEFAULT_PAYMENT_DATA_CONTEXT_STATE
	);
	const {
		dispatchActions,
		setPaymentStatus,
	} = usePaymentMethodDataDispatchers( dispatch );

	const paymentMethodsInitialized = usePaymentMethods(
		dispatchActions.setRegisteredPaymentMethods
	);

	const expressPaymentMethodsInitialized = useExpressPaymentMethods(
		dispatchActions.setRegisteredExpressPaymentMethods
	);

	const {
		activePaymentMethod,
		activeSavedToken,
		setActivePaymentMethod,
		setActiveSavedToken,
	} = useActivePaymentMethod();

	const customerPaymentMethods = useMemo( (): CustomerPaymentMethods => {
		if ( isEditor ) {
			return getPreviewData(
				'previewSavedPaymentMethods'
			) as CustomerPaymentMethods;
		}
		return paymentMethodsInitialized
			? getCustomerPaymentMethods( paymentData.paymentMethods )
			: {};
	}, [
		isEditor,
		getPreviewData,
		paymentMethodsInitialized,
		paymentData.paymentMethods,
	] );

	const setExpressPaymentError = useCallback(
		( message ) => {
			if ( message ) {
				addErrorNotice( message, {
					id: 'wc-express-payment-error',
					context: noticeContexts.EXPRESS_PAYMENTS,
				} );
			} else {
				removeNotice(
					'wc-express-payment-error',
					noticeContexts.EXPRESS_PAYMENTS
				);
			}
		},
		[ addErrorNotice, noticeContexts.EXPRESS_PAYMENTS, removeNotice ]
	);

	const isExpressPaymentMethodActive = Object.keys(
		paymentData.expressPaymentMethods
	).includes( activePaymentMethod );

	const currentStatus = useMemo(
		() => ( {
			isPristine: paymentData.currentStatus === STATUS.PRISTINE,
			isStarted: paymentData.currentStatus === STATUS.STARTED,
			isProcessing: paymentData.currentStatus === STATUS.PROCESSING,
			isFinished: [
				STATUS.ERROR,
				STATUS.FAILED,
				STATUS.SUCCESS,
			].includes( paymentData.currentStatus ),
			hasError: paymentData.currentStatus === STATUS.ERROR,
			hasFailed: paymentData.currentStatus === STATUS.FAILED,
			isSuccessful: paymentData.currentStatus === STATUS.SUCCESS,
			isDoingExpressPayment:
				paymentData.currentStatus !== STATUS.PRISTINE &&
				isExpressPaymentMethodActive,
		} ),
		[ paymentData.currentStatus, isExpressPaymentMethodActive ]
	);

	// Update the active (selected) payment method when it is empty, or invalid.
	useEffect( () => {
		const paymentMethodKeys = Object.keys( paymentData.paymentMethods );
		const allPaymentMethodKeys = [
			...paymentMethodKeys,
			...Object.keys( paymentData.expressPaymentMethods ),
		];
		if ( ! paymentMethodsInitialized || ! paymentMethodKeys.length ) {
			return;
		}

		setActivePaymentMethod( ( currentActivePaymentMethod ) => {
			// If there's no active payment method, or the active payment method has
			// been removed (e.g. COD vs shipping methods), set one as active.
			// Note: It's possible that the active payment method might be an
			// express payment method. So registered express payment methods are
			// included in the check here.
			if (
				! currentActivePaymentMethod ||
				! allPaymentMethodKeys.includes( currentActivePaymentMethod )
			) {
				setPaymentStatus().pristine();
				return Object.keys( paymentData.paymentMethods )[ 0 ];
			}
			return currentActivePaymentMethod;
		} );
	}, [
		paymentMethodsInitialized,
		paymentData.paymentMethods,
		paymentData.expressPaymentMethods,
		setActivePaymentMethod,
		setPaymentStatus,
	] );

	// flip payment to processing if checkout processing is complete, there are no errors, and payment status is started.
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

	// When checkout is returned to idle, set payment status to pristine but only if payment status is already not finished.
	useEffect( () => {
		if ( checkoutIsIdle && ! currentStatus.isSuccessful ) {
			setPaymentStatus().pristine();
		}
	}, [ checkoutIsIdle, currentStatus.isSuccessful, setPaymentStatus ] );

	// if checkout has an error and payment is not being made with a saved token and payment status is success, then let's sync payment status back to pristine.
	useEffect( () => {
		if (
			checkoutHasError &&
			currentStatus.isSuccessful &&
			! paymentData.hasSavedToken
		) {
			setPaymentStatus().pristine();
		}
	}, [
		checkoutHasError,
		currentStatus.isSuccessful,
		paymentData.hasSavedToken,
		setPaymentStatus,
	] );

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
			).then( ( observerResponses ) => {
				let successResponse, errorResponse;
				observerResponses.forEach( ( response ) => {
					if ( isSuccessResponse( response ) ) {
						// the last observer response always "wins" for success.
						successResponse = response;
					}
					if (
						isErrorResponse( response ) ||
						isFailResponse( response )
					) {
						errorResponse = response;
					}
				} );
				if ( successResponse && ! errorResponse ) {
					setPaymentStatus().success(
						successResponse?.meta?.paymentMethodData,
						successResponse?.meta?.billingData,
						successResponse?.meta?.shippingData
					);
				} else if ( errorResponse && isFailResponse( errorResponse ) ) {
					if (
						errorResponse.message &&
						errorResponse.message.length
					) {
						addErrorNotice( errorResponse.message, {
							id: 'wc-payment-error',
							isDismissible: false,
							context:
								errorResponse?.messageContext ||
								noticeContexts.PAYMENTS,
						} );
					}
					setPaymentStatus().failed(
						errorResponse?.message,
						errorResponse?.meta?.paymentMethodData,
						errorResponse?.meta?.billingData
					);
				} else if ( errorResponse ) {
					if (
						errorResponse.message &&
						errorResponse.message.length
					) {
						addErrorNotice( errorResponse.message, {
							id: 'wc-payment-error',
							isDismissible: false,
							context:
								errorResponse?.messageContext ||
								noticeContexts.PAYMENTS,
						} );
					}
					setPaymentStatus().error( errorResponse.message );
					setValidationErrors( errorResponse?.validationErrors );
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

	const paymentContextData: PaymentMethodDataContextType = {
		setPaymentStatus,
		currentStatus,
		paymentStatuses: STATUS,
		paymentMethodData: paymentData.paymentMethodData,
		errorMessage: paymentData.errorMessage,
		activePaymentMethod,
		setActivePaymentMethod,
		activeSavedToken,
		setActiveSavedToken,
		onPaymentProcessing,
		customerPaymentMethods,
		paymentMethods: paymentData.paymentMethods,
		expressPaymentMethods: paymentData.expressPaymentMethods,
		paymentMethodsInitialized,
		expressPaymentMethodsInitialized,
		setExpressPaymentError,
		isExpressPaymentMethodActive,
		shouldSavePayment: paymentData.shouldSavePaymentMethod,
		setShouldSavePayment: dispatchActions.setShouldSavePayment,
	};

	return (
		<PaymentMethodDataContext.Provider value={ paymentContextData }>
			{ children }
		</PaymentMethodDataContext.Provider>
	);
};
