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

/**
 * Internal dependencies
 */
import {
	STATUS,
	DEFAULT_PAYMENT_DATA_CONTEXT_STATE,
	DEFAULT_PAYMENT_METHOD_DATA,
} from './constants';
import reducer from './reducer';
import {
	statusOnly,
	error,
	failed,
	success,
	started,
	setRegisteredPaymentMethods,
	setRegisteredExpressPaymentMethods,
	setShouldSavePaymentMethod,
} from './actions';
import {
	usePaymentMethods,
	useExpressPaymentMethods,
} from './use-payment-method-registration';
import { useCustomerDataContext } from '../customer';
import { useCheckoutContext } from '../checkout-state';
import { useShippingDataContext } from '../shipping';
import { useEditorContext } from '../../editor-context';
import {
	EMIT_TYPES,
	useEventEmitters,
	emitEventWithAbort,
	reducer as emitReducer,
} from './event-emit';
import { useValidationContext } from '../../validation';
import { useStoreEvents } from '../../../hooks/use-store-events';
import { useStoreNotices } from '../../../hooks/use-store-notices';
import { useEmitResponse } from '../../../hooks/use-emit-response';

import type {
	PaymentStatusDispatchers,
	PaymentMethods,
	CustomerPaymentMethods,
	PaymentMethodsDispatcherType,
	PaymentMethodDataContextType,
} from './types';
import { getCustomerPaymentMethods } from './utils';

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

export const usePaymentMethodDataContext = (): PaymentMethodDataContextType => {
	return useContext( PaymentMethodDataContext );
};

/**
 * PaymentMethodDataProvider is automatically included in the
 * CheckoutDataProvider.
 *
 * This provides the api interface (via the context hook) for payment method
 * status and data.
 *
 * @param {Object} props          Incoming props for provider
 * @param {Object} props.children The wrapped components in this provider.
 */
export const PaymentMethodDataProvider = ( {
	children,
}: {
	children: React.ReactChildren;
} ): JSX.Element => {
	const { setBillingData } = useCustomerDataContext();
	const {
		isProcessing: checkoutIsProcessing,
		isIdle: checkoutIsIdle,
		isCalculating: checkoutIsCalculating,
		hasError: checkoutHasError,
	} = useCheckoutContext();
	const { isEditor, getPreviewData } = useEditorContext();
	const {
		isSuccessResponse,
		isErrorResponse,
		isFailResponse,
		noticeContexts,
	} = useEmitResponse();
	const { dispatchCheckoutEvent } = useStoreEvents();

	const [ activePaymentMethod, setActive ] = useState( '' ); // The active payment method - e.g. Stripe CC or BACS.
	const [ activeSavedToken, setActiveSavedToken ] = useState( '' ); // If a previously saved payment method is active, the token for that method. For example, a for a Stripe CC card saved to user account.
	const [ observers, observerDispatch ] = useReducer( emitReducer, {} );
	const [ paymentData, dispatch ] = useReducer(
		reducer,
		DEFAULT_PAYMENT_DATA_CONTEXT_STATE
	);
	const currentObservers = useRef( observers );
	const { onPaymentProcessing } = useEventEmitters( observerDispatch );

	// ensure observers are always current.
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );

	const setActivePaymentMethod = useCallback(
		( paymentMethodSlug ) => {
			setActive( paymentMethodSlug );
			dispatch( statusOnly( PRISTINE ) );
			dispatchCheckoutEvent( 'set-active-payment-method', {
				paymentMethodSlug,
			} );
		},
		[ setActive, dispatch, dispatchCheckoutEvent ]
	);

	const paymentMethodsDispatcher = useCallback<
		PaymentMethodsDispatcherType
	>(
		( paymentMethods ) => {
			dispatch(
				setRegisteredPaymentMethods( paymentMethods as PaymentMethods )
			);
		},
		[ dispatch ]
	);

	const expressPaymentMethodsDispatcher = useCallback<
		PaymentMethodsDispatcherType
	>(
		( paymentMethods ) => {
			dispatch(
				setRegisteredExpressPaymentMethods(
					paymentMethods as PaymentMethods
				)
			);
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

	const customerPaymentMethods = useMemo( (): CustomerPaymentMethods => {
		if ( isEditor ) {
			return getPreviewData(
				'previewSavedPaymentMethods'
			) as CustomerPaymentMethods;
		}
		if (
			! paymentMethodsInitialized ||
			Object.keys( paymentData.paymentMethods ).length === 0
		) {
			return {};
		}
		return getCustomerPaymentMethods( paymentData.paymentMethods );
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

	const setPaymentStatus = useCallback(
		(): PaymentStatusDispatchers => ( {
			started: ( paymentMethodData ) => {
				dispatch(
					started( {
						paymentMethodData,
					} )
				);
			},
			processing: () => dispatch( statusOnly( PROCESSING ) ),
			completed: () => dispatch( statusOnly( COMPLETE ) ),
			error: ( errorMessage ) => dispatch( error( errorMessage ) ),
			failed: (
				errorMessage,
				paymentMethodData,
				billingData = undefined
			) => {
				if ( billingData ) {
					setBillingData( billingData );
				}
				dispatch(
					failed( {
						errorMessage: errorMessage || '',
						paymentMethodData: paymentMethodData || {},
					} )
				);
			},
			success: (
				paymentMethodData,
				billingData = undefined,
				shippingData = undefined
			) => {
				if ( billingData ) {
					setBillingData( billingData );
				}
				if (
					typeof shippingData !== undefined &&
					shippingData?.address
				) {
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
			dispatch( statusOnly( PRISTINE ) );
		}
	}, [ checkoutIsIdle, currentStatus.isSuccessful ] );

	// if checkout has an error and payment is not being made with a saved token and payment status is success, then let's sync payment status back to pristine.
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
		const allPaymentMethodKeys = [
			...paymentMethodKeys,
			...Object.keys( paymentData.expressPaymentMethods ),
		];
		if ( ! paymentMethodsInitialized || ! paymentMethodKeys.length ) {
			return;
		}

		setActive( ( currentActivePaymentMethod ) => {
			// If there's no active payment method, or the active payment method has
			// been removed (e.g. COD vs shipping methods), set one as active.
			// Note: It's possible that the active payment method might be an
			// express payment method. So registered express payment methods are
			// included in the check here.
			if (
				! currentActivePaymentMethod ||
				! allPaymentMethodKeys.includes( currentActivePaymentMethod )
			) {
				dispatch( statusOnly( PRISTINE ) );
				return Object.keys( paymentData.paymentMethods )[ 0 ];
			}
			return currentActivePaymentMethod;
		} );
	}, [
		paymentMethodsInitialized,
		paymentData.paymentMethods,
		paymentData.expressPaymentMethods,
		setActive,
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
		shouldSavePayment: paymentData.shouldSavePaymentMethod,
		setShouldSavePayment,
	};

	return (
		<PaymentMethodDataContext.Provider value={ paymentContextData }>
			{ children }
		</PaymentMethodDataContext.Provider>
	);
};
