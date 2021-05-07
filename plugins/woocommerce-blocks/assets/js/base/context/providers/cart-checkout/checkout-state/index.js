/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useReducer,
	useRef,
	useMemo,
	useEffect,
	useCallback,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { usePrevious } from '@woocommerce/base-hooks';
import deprecated from '@wordpress/deprecated';
/**
 * Internal dependencies
 */
import { actions } from './actions';
import { reducer, prepareResponseData } from './reducer';
import { DEFAULT_STATE, STATUS } from './constants';
import {
	EMIT_TYPES,
	emitterObservers,
	emitEvent,
	emitEventWithAbort,
	reducer as emitReducer,
} from './event-emit';
import { useValidationContext } from '../../validation';
import { useStoreNotices } from '../../../hooks/use-store-notices';
import { useStoreEvents } from '../../../hooks/use-store-events';
import { useCheckoutNotices } from '../../../hooks/use-checkout-notices';
import { useEmitResponse } from '../../../hooks/use-emit-response';

/**
 * @typedef {import('@woocommerce/type-defs/checkout').CheckoutDispatchActions} CheckoutDispatchActions
 * @typedef {import('@woocommerce/type-defs/contexts').CheckoutDataContext} CheckoutDataContext
 */

const CheckoutContext = createContext( {
	isComplete: false,
	isIdle: false,
	isCalculating: false,
	isProcessing: false,
	isBeforeProcessing: false,
	isAfterProcessing: false,
	hasError: false,
	redirectUrl: '',
	orderId: 0,
	orderNotes: '',
	customerId: 0,
	onSubmit: () => void null,
	// deprecated for onCheckoutValidationBeforeProcessing
	onCheckoutBeforeProcessing: ( callback ) => void callback,
	onCheckoutValidationBeforeProcessing: ( callback ) => void callback,
	onCheckoutAfterProcessingWithSuccess: ( callback ) => void callback,
	onCheckoutAfterProcessingWithError: ( callback ) => void callback,
	dispatchActions: {
		resetCheckout: () => void null,
		setRedirectUrl: ( url ) => void url,
		setHasError: ( hasError ) => void hasError,
		setAfterProcessing: ( response ) => void response,
		incrementCalculating: () => void null,
		decrementCalculating: () => void null,
		setCustomerId: ( id ) => void id,
		setOrderId: ( id ) => void id,
		setOrderNotes: ( orderNotes ) => void orderNotes,
	},
	hasOrder: false,
	isCart: false,
	shouldCreateAccount: false,
	setShouldCreateAccount: ( value ) => void value,
} );

/**
 * @return {CheckoutDataContext} Returns the checkout data context value
 */
export const useCheckoutContext = () => {
	return useContext( CheckoutContext );
};

/**
 * Checkout state provider
 * This provides provides an api interface exposing checkout state for use with
 * cart or checkout blocks.
 *
 * @param {Object}  props                     Incoming props for the provider.
 * @param {Object}  props.children            The children being wrapped.
 * @param {string}  props.redirectUrl         Initialize what the checkout will redirect to after successful submit.
 * @param {boolean} props.isCart              If context provider is being used in cart context.
 */
export const CheckoutStateProvider = ( {
	children,
	redirectUrl,
	isCart = false,
} ) => {
	// note, this is done intentionally so that the default state now has
	// the redirectUrl for when checkout is reset to PRISTINE state.
	DEFAULT_STATE.redirectUrl = redirectUrl;
	const [ checkoutState, dispatch ] = useReducer( reducer, DEFAULT_STATE );
	const [ observers, observerDispatch ] = useReducer( emitReducer, {} );
	const currentObservers = useRef( observers );
	const { setValidationErrors } = useValidationContext();
	const { addErrorNotice, removeNotices } = useStoreNotices();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const isCalculating = checkoutState.calculatingCount > 0;
	const {
		isSuccessResponse,
		isErrorResponse,
		isFailResponse,
		shouldRetry,
	} = useEmitResponse();
	const {
		checkoutNotices,
		paymentNotices,
		expressPaymentNotices,
	} = useCheckoutNotices();

	// set observers on ref so it's always current.
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );
	/**
	 * @deprecated use onCheckoutValidationBeforeProcessing instead
	 *
	 * To prevent the deprecation message being shown at render time
	 * we need an extra function between useMemo and emitterObservers
	 * so that the deprecated message gets shown only at invocation time.
	 * (useMemo calls the passed function at render time)
	 * See: https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4039/commits/a502d1be8828848270993264c64220731b0ae181
	 */
	const onCheckoutBeforeProcessing = useMemo( () => {
		const callback = emitterObservers( observerDispatch )
			.onCheckoutValidationBeforeProcessing;

		return function ( ...args ) {
			deprecated( 'onCheckoutBeforeProcessing', {
				alternative: 'onCheckoutValidationBeforeProcessing',
				plugin: 'WooCommerce Blocks',
			} );

			return callback( ...args );
		};
	}, [ observerDispatch ] );

	const onCheckoutValidationBeforeProcessing = useMemo(
		() =>
			emitterObservers( observerDispatch )
				.onCheckoutValidationBeforeProcessing,
		[ observerDispatch ]
	);
	const onCheckoutAfterProcessingWithSuccess = useMemo(
		() =>
			emitterObservers( observerDispatch )
				.onCheckoutAfterProcessingWithSuccess,
		[ observerDispatch ]
	);
	const onCheckoutAfterProcessingWithError = useMemo(
		() =>
			emitterObservers( observerDispatch )
				.onCheckoutAfterProcessingWithError,
		[ observerDispatch ]
	);

	/**
	 * @type {CheckoutDispatchActions}
	 */
	const dispatchActions = useMemo(
		() => ( {
			resetCheckout: () => void dispatch( actions.setPristine() ),
			setRedirectUrl: ( url ) =>
				void dispatch( actions.setRedirectUrl( url ) ),
			setHasError: ( hasError ) =>
				void dispatch( actions.setHasError( hasError ) ),
			incrementCalculating: () =>
				void dispatch( actions.incrementCalculating() ),
			decrementCalculating: () =>
				void dispatch( actions.decrementCalculating() ),
			setCustomerId: ( id ) =>
				void dispatch( actions.setCustomerId( id ) ),
			setOrderId: ( orderId ) =>
				void dispatch( actions.setOrderId( orderId ) ),
			setOrderNotes: ( orderNotes ) =>
				void dispatch( actions.setOrderNotes( orderNotes ) ),
			setAfterProcessing: ( response ) => {
				// capture general error message if this is an error response.
				if (
					! response.payment_result &&
					response.message &&
					response?.data?.status !== 200
				) {
					response.payment_result = {
						...response.payment_result,
						message: response.message,
					};
				}
				if ( response.payment_result ) {
					if (
						// eslint-disable-next-line camelcase
						response.payment_result?.redirect_url
					) {
						dispatch(
							actions.setRedirectUrl(
								response.payment_result.redirect_url
							)
						);
					}
					dispatch(
						actions.setProcessingResponse(
							prepareResponseData( response.payment_result )
						)
					);
				}
				void dispatch( actions.setAfterProcessing() );
			},
		} ),
		[]
	);

	// emit events.
	useEffect( () => {
		const status = checkoutState.status;
		if ( status === STATUS.BEFORE_PROCESSING ) {
			removeNotices( 'error' );
			emitEvent(
				currentObservers.current,
				EMIT_TYPES.CHECKOUT_VALIDATION_BEFORE_PROCESSING,
				{}
			).then( ( response ) => {
				if ( response !== true ) {
					if ( Array.isArray( response ) ) {
						response.forEach(
							( { errorMessage, validationErrors } ) => {
								addErrorNotice( errorMessage );
								setValidationErrors( validationErrors );
							}
						);
					}
					dispatch( actions.setIdle() );
				} else {
					dispatch( actions.setProcessing() );
				}
			} );
		}
	}, [
		checkoutState.status,
		setValidationErrors,
		addErrorNotice,
		removeNotices,
		dispatch,
	] );

	const previousStatus = usePrevious( checkoutState.status );
	const previousHasError = usePrevious( checkoutState.hasError );

	useEffect( () => {
		if (
			checkoutState.status === previousStatus &&
			checkoutState.hasError === previousHasError
		) {
			return;
		}

		const handleErrorResponse = ( observerResponses ) => {
			let errorResponse = null;
			observerResponses.forEach( ( response ) => {
				const { message, messageContext } = response;
				if (
					( isErrorResponse( response ) ||
						isFailResponse( response ) ) &&
					message
				) {
					const errorOptions = messageContext
						? { context: messageContext }
						: undefined;
					errorResponse = response;
					addErrorNotice( message, errorOptions );
				}
			} );
			return errorResponse;
		};

		if ( checkoutState.status === STATUS.AFTER_PROCESSING ) {
			const data = {
				redirectUrl: checkoutState.redirectUrl,
				orderId: checkoutState.orderId,
				customerId: checkoutState.customerId,
				customerNote: checkoutState.customerNote,
				processingResponse: checkoutState.processingResponse,
			};
			if ( checkoutState.hasError ) {
				// allow payment methods or other things to customize the error
				// with a fallback if nothing customizes it.
				emitEventWithAbort(
					currentObservers.current,
					EMIT_TYPES.CHECKOUT_AFTER_PROCESSING_WITH_ERROR,
					data
				).then( ( observerResponses ) => {
					const errorResponse = handleErrorResponse(
						observerResponses
					);
					if ( errorResponse !== null ) {
						// irrecoverable error so set complete
						if ( ! shouldRetry( errorResponse ) ) {
							dispatch( actions.setComplete( errorResponse ) );
						} else {
							dispatch( actions.setIdle() );
						}
					} else {
						const hasErrorNotices =
							checkoutNotices.some(
								( notice ) => notice.status === 'error'
							) ||
							expressPaymentNotices.some(
								( notice ) => notice.status === 'error'
							) ||
							paymentNotices.some(
								( notice ) => notice.status === 'error'
							);
						if ( ! hasErrorNotices ) {
							// no error handling in place by anything so let's fall
							// back to default
							const message =
								data.processingResponse?.message ||
								__(
									'Something went wrong. Please contact us to get assistance.',
									'woo-gutenberg-products-block'
								);
							addErrorNotice( message, {
								id: 'checkout',
							} );
						}

						dispatch( actions.setIdle() );
					}
				} );
			} else {
				emitEventWithAbort(
					currentObservers.current,
					EMIT_TYPES.CHECKOUT_AFTER_PROCESSING_WITH_SUCCESS,
					data
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
						dispatch( actions.setComplete( successResponse ) );
					} else if ( errorResponse ) {
						if ( errorResponse.message ) {
							const errorOptions = errorResponse.messageContext
								? { context: errorResponse.messageContext }
								: undefined;
							addErrorNotice(
								errorResponse.message,
								errorOptions
							);
						}
						if ( ! shouldRetry( errorResponse ) ) {
							dispatch( actions.setComplete( errorResponse ) );
						} else {
							// this will set an error which will end up
							// triggering the onCheckoutAfterProcessingWithError emitter.
							// and then setting checkout to IDLE state.
							dispatch( actions.setHasError( true ) );
						}
					} else {
						// nothing hooked in had any response type so let's just
						// consider successful
						dispatch( actions.setComplete() );
					}
				} );
			}
		}
	}, [
		checkoutState.status,
		checkoutState.hasError,
		checkoutState.redirectUrl,
		checkoutState.orderId,
		checkoutState.customerId,
		checkoutState.customerNote,
		checkoutState.processingResponse,
		previousStatus,
		previousHasError,
		dispatchActions,
		addErrorNotice,
		isErrorResponse,
		isFailResponse,
		isSuccessResponse,
		shouldRetry,
		checkoutNotices,
		expressPaymentNotices,
		paymentNotices,
	] );

	const onSubmit = useCallback( () => {
		dispatchCheckoutEvent( 'submit' );
		dispatch( actions.setBeforeProcessing() );
	}, [ dispatchCheckoutEvent ] );

	/**
	 * @type {CheckoutDataContext}
	 */
	const checkoutData = {
		onSubmit,
		isComplete: checkoutState.status === STATUS.COMPLETE,
		isIdle: checkoutState.status === STATUS.IDLE,
		isCalculating,
		isProcessing: checkoutState.status === STATUS.PROCESSING,
		isBeforeProcessing: checkoutState.status === STATUS.BEFORE_PROCESSING,
		isAfterProcessing: checkoutState.status === STATUS.AFTER_PROCESSING,
		hasError: checkoutState.hasError,
		redirectUrl: checkoutState.redirectUrl,
		onCheckoutBeforeProcessing,
		onCheckoutValidationBeforeProcessing,
		onCheckoutAfterProcessingWithSuccess,
		onCheckoutAfterProcessingWithError,
		dispatchActions,
		isCart,
		orderId: checkoutState.orderId,
		hasOrder: !! checkoutState.orderId,
		customerId: checkoutState.customerId,
		orderNotes: checkoutState.orderNotes,
		shouldCreateAccount: checkoutState.shouldCreateAccount,
		setShouldCreateAccount: ( value ) =>
			dispatch( actions.setShouldCreateAccount( value ) ),
	};
	return (
		<CheckoutContext.Provider value={ checkoutData }>
			{ children }
		</CheckoutContext.Provider>
	);
};
