/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useReducer,
	useRef,
	useEffect,
} from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	CHECKOUT_STORE_KEY,
	PAYMENT_STORE_KEY,
	VALIDATION_STORE_KEY,
} from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useEventEmitters, reducer as emitReducer } from './event-emit';
import { useCustomerData } from '../../../hooks/use-customer-data';
import { emitterCallback } from '../../../event-emit';

type PaymentEventsContextType = {
	// Event registration callback for registering observers for the payment processing event.
	onPaymentProcessing: ReturnType< typeof emitterCallback >;
};

const PaymentEventsContext = createContext< PaymentEventsContextType >( {
	onPaymentProcessing: () => () => () => void null,
} );

export const usePaymentEventsContext = () => {
	return useContext( PaymentEventsContext );
};

/**
 * PaymentEventsProvider is automatically included in the CheckoutProvider.
 *
 * This provides the api interface (via the context hook) for payment status and data.
 *
 * @param {Object} props          Incoming props for provider
 * @param {Object} props.children The wrapped components in this provider.
 */
export const PaymentEventsProvider = ( {
	children,
}: {
	children: React.ReactNode;
} ): JSX.Element => {
	const {
		isProcessing: checkoutIsProcessing,
		isIdle: checkoutIsIdle,
		isCalculating: checkoutIsCalculating,
		hasError: checkoutHasError,
	} = useSelect( ( select ) => {
		const store = select( CHECKOUT_STORE_KEY );
		return {
			isProcessing: store.isProcessing(),
			isIdle: store.isIdle(),
			hasError: store.hasError(),
			isCalculating: store.isCalculating(),
		};
	} );
	const { currentStatus } = useSelect( ( select ) => {
		const store = select( PAYMENT_STORE_KEY );

		return {
			currentStatus: store.getCurrentStatus(),
		};
	} );

	const { createErrorNotice, removeNotice } = useDispatch( 'core/notices' );
	const { setValidationErrors } = useDispatch( VALIDATION_STORE_KEY );
	const [ observers, observerDispatch ] = useReducer( emitReducer, {} );
	const { onPaymentProcessing } = useEventEmitters( observerDispatch );
	const currentObservers = useRef( observers );

	// ensure observers are always current.
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );

	const {
		__internalSetPaymentStatus,
		__internalSetPaymentMethodData,
		__internalEmitPaymentProcessingEvent,
	} = useDispatch( PAYMENT_STORE_KEY );
	const { setBillingAddress, setShippingAddress } = useCustomerData();

	// flip payment to processing if checkout processing is complete, there are no errors, and payment status is started.
	useEffect( () => {
		if (
			checkoutIsProcessing &&
			! checkoutHasError &&
			! checkoutIsCalculating &&
			! currentStatus.isFinished
		) {
			__internalSetPaymentStatus( { isProcessing: true } );
		}
	}, [
		checkoutIsProcessing,
		checkoutHasError,
		checkoutIsCalculating,
		currentStatus.isFinished,
		__internalSetPaymentStatus,
	] );

	// When checkout is returned to idle, set payment status to pristine but only if payment status is already not finished.
	useEffect( () => {
		if ( checkoutIsIdle && ! currentStatus.isSuccessful ) {
			__internalSetPaymentStatus( { isPristine: true } );
		}
	}, [
		checkoutIsIdle,
		currentStatus.isSuccessful,
		__internalSetPaymentStatus,
	] );

	// if checkout has an error sync payment status back to pristine.
	useEffect( () => {
		if ( checkoutHasError && currentStatus.isSuccessful ) {
			__internalSetPaymentStatus( { isPristine: true } );
		}
	}, [
		checkoutHasError,
		currentStatus.isSuccessful,
		__internalSetPaymentStatus,
	] );

	// Emit the payment processing event
	useEffect( () => {
		// Note: the nature of this event emitter is that it will bail on any
		// observer that returns a response that !== true. However, this still
		// allows for other observers that return true for continuing through
		// to the next observer (or bailing if there's a problem).
		if ( currentStatus.isProcessing ) {
			__internalEmitPaymentProcessingEvent(
				currentObservers.current,
				setValidationErrors
			);
		}
	}, [
		currentStatus.isProcessing,
		setValidationErrors,
		__internalSetPaymentStatus,
		removeNotice,
		createErrorNotice,
		setBillingAddress,
		__internalSetPaymentMethodData,
		setShippingAddress,
		__internalEmitPaymentProcessingEvent,
	] );

	const paymentContextData = {
		onPaymentProcessing,
	};

	return (
		<PaymentEventsContext.Provider value={ paymentContextData }>
			{ children }
		</PaymentEventsContext.Provider>
	);
};
