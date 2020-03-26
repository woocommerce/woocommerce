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
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PaymentMethodDataProvider } from '../payment-methods';
import { ShippingDataProvider } from '../shipping';
import { BillingDataProvider } from '../billing';
import { actions } from './actions';
import { reducer } from './reducer';
import { DEFAULT_STATE, STATUS } from './constants';
import {
	EMIT_TYPES,
	emitterSubscribers,
	emitEvent,
	emitEventWithAbort,
	reducer as emitReducer,
} from './event-emit';
import CheckoutProcessor from './processor/index.js';

/**
 * @typedef {import('@woocommerce/type-defs/checkout').CheckoutDispatchActions} CheckoutDispatchActions
 * @typedef {import('@woocommerce/type-defs/contexts').CheckoutDataContext} CheckoutDataContext
 */

const CheckoutContext = createContext( {
	submitLabel: '',
	onSubmit: () => void null,
	isComplete: false,
	isIdle: false,
	isCalculating: false,
	isProcessing: false,
	hasError: false,
	redirectUrl: '',
	onCheckoutCompleteSuccess: () => void null,
	onCheckoutCompleteError: () => void null,
	onCheckoutProcessing: () => void null,
	dispatchActions: {
		resetCheckout: () => void null,
		setRedirectUrl: () => void null,
		setHasError: () => void null,
		clearError: () => void null,
		incrementCalculating: () => void null,
		decrementCalculating: () => void null,
	},
} );

/**
 * @return {CheckoutDataContext} Returns the checkout data context value
 */
export const useCheckoutContext = () => {
	return useContext( CheckoutContext );
};

/**
 * Checkout provider
 * This wraps the checkout and provides an api interface for the checkout to
 * children via various hooks.
 *
 * @param {Object}  props                     Incoming props for the provider.
 * @param {Array}   props.children            The children being wrapped.
 * @param {string}  props.activePaymentMethod Can be used to set what the
 *                                            initial active payment method is
 *                                            set to.
 * @param {string}  props.redirectUrl         Initialize what the checkout will
 *                                            redirect to after successful
 *                                            submit.
 * @param {string}  props.submitLabel         What will be used for the checkout
 *                                            submit button label.
 * @param {boolean} props.isEditor            Whether the checkout is in the
 *                                            editor context or not.
 */
export const CheckoutProvider = ( {
	children,
	activePaymentMethod: initialActivePaymentMethod,
	redirectUrl,
	isEditor,
	submitLabel = __( 'Place Order', 'woo-gutenberg-product-block' ),
} ) => {
	// note, this is done intentionally so that the default state now has
	// the redirectUrl for when checkout is reset to PRISTINE state.
	DEFAULT_STATE.redirectUrl = redirectUrl;
	const [ checkoutState, dispatch ] = useReducer( reducer, DEFAULT_STATE );
	const [ observers, subscriber ] = useReducer( emitReducer, {} );
	const currentObservers = useRef( observers );
	// set observers on ref so it's always current
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );
	const onCheckoutCompleteSuccess = emitterSubscribers( subscriber )
		.onCheckoutCompleteSuccess;
	const onCheckoutCompleteError = emitterSubscribers( subscriber )
		.onCheckoutCompleteError;
	const onCheckoutProcessing = emitterSubscribers( subscriber )
		.onCheckoutProcessing;

	/**
	 * @type {CheckoutDispatchActions}
	 */
	const dispatchActions = useMemo(
		() => ( {
			resetCheckout: () => void dispatch( actions.setPristine() ),
			setRedirectUrl: ( url ) =>
				void dispatch( actions.setRedirectUrl( url ) ),
			setHasError: () => void dispatch( actions.setHasError() ),
			clearError: () => void dispatch( actions.clearError() ),
			incrementCalculating: () =>
				void dispatch( actions.incrementCalculating() ),
			decrementCalculating: () =>
				void dispatch( actions.decrementCalculating() ),
		} ),
		[]
	);

	// emit events
	useEffect( () => {
		const status = checkoutState.status;
		if ( status === STATUS.PROCESSING ) {
			emitEventWithAbort(
				currentObservers.current,
				EMIT_TYPES.CHECKOUT_PROCESSING,
				{}
			).then( ( response ) => {
				if ( response !== true ) {
					// @todo handle any validation error property values in the
					// response
					dispatchActions.setHasError();
				}
				dispatch( actions.setComplete() );
			} );
		}
		if ( checkoutState.isComplete ) {
			if ( checkoutState.hasError ) {
				emitEvent(
					currentObservers.current,
					EMIT_TYPES.CHECKOUT_COMPLETE_WITH_ERROR,
					{}
				);
			} else {
				emitEvent(
					currentObservers.current,
					EMIT_TYPES.CHECKOUT_COMPLETE_WITH_SUCCESS,
					{}
				).then( () => {
					// all observers have done their thing so let's redirect
					// (if no error)
					if ( ! checkoutState.hasError ) {
						window.location = checkoutState.redirectUrl;
					}
				} );
			}
		}
	}, [
		checkoutState.status,
		checkoutState.hasError,
		checkoutState.isComplete,
		checkoutState.redirectUrl,
	] );

	const onSubmit = () => {
		dispatch( actions.setProcessing() );
	};

	/**
	 * @type {CheckoutDataContext}
	 */
	const checkoutData = {
		submitLabel,
		onSubmit,
		isComplete: checkoutState.status === STATUS.COMPLETE,
		isIdle: checkoutState.status === STATUS.IDLE,
		isCalculating: checkoutState.status === STATUS.CALCULATING,
		isProcessing: checkoutState.status === STATUS.PROCESSING,
		hasError: checkoutState.hasError,
		redirectUrl: checkoutState.redirectUrl,
		onCheckoutCompleteSuccess,
		onCheckoutCompleteError,
		onCheckoutProcessing,
		dispatchActions,
		isEditor,
	};
	return (
		<CheckoutContext.Provider value={ checkoutData }>
			<BillingDataProvider>
				<PaymentMethodDataProvider
					activePaymentMethod={ initialActivePaymentMethod }
				>
					<ShippingDataProvider>
						{ children }
						<CheckoutProcessor />
					</ShippingDataProvider>
				</PaymentMethodDataProvider>
			</BillingDataProvider>
		</CheckoutContext.Provider>
	);
};
