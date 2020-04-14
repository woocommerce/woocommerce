/**
 * Internal dependencies
 */
import { DEFAULT_STRIPE_EVENT_HANDLERS } from './constants';
import {
	getStripeServerData,
	getPaymentRequest,
	updatePaymentRequest,
	canDoPaymentRequest,
	getTotalPaymentItem,
	getBillingData,
	getPaymentMethodData,
	getShippingData,
	normalizeShippingAddressForCheckout,
	normalizeShippingOptions,
	normalizeLineItems,
	normalizeShippingOptionSelectionsForCheckout,
} from '../stripe-utils';

/**
 * External dependencies
 */
import { useRef, useState, useEffect } from '@wordpress/element';
import {
	Elements,
	PaymentRequestButtonElement,
	useStripe,
} from '@stripe/react-stripe-js';
import { __ } from '@wordpress/i18n';

/**
 * @typedef {import('../stripe-utils/type-defs').Stripe} Stripe
 * @typedef {import('../stripe-utils/type-defs').StripePaymentRequest} StripePaymentRequest
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').RegisteredPaymentMethodProps} RegisteredPaymentMethodProps
 */

/**
 * @typedef {Object} WithStripe
 *
 * @property {Stripe} [stripe] Stripe api (might not be present)
 */

/**
 * @typedef {RegisteredPaymentMethodProps & WithStripe} StripeRegisteredPaymentMethodProps
 */

/**
 * PaymentRequestExpressComponent
 *
 * @param {StripeRegisteredPaymentMethodProps} props Incoming props
 */
const PaymentRequestExpressComponent = ( {
	shippingData,
	billing,
	eventRegistration,
	onSubmit,
	setExpressPaymentError,
	emitResponse,
	onClick,
	onClose,
} ) => {
	/**
	 * @type {[ StripePaymentRequest|null, function( StripePaymentRequest ):StripePaymentRequest|null]}
	 */
	// @ts-ignore
	const [ paymentRequest, setPaymentRequest ] = useState( null );
	const stripe = useStripe();
	const [ canMakePayment, setCanMakePayment ] = useState( false );
	const [ paymentRequestType, setPaymentRequestType ] = useState( '' );
	const [ isProcessing, setIsProcessing ] = useState( false );
	const [ isFinished, setIsFinished ] = useState( false );
	const eventHandlers = useRef( DEFAULT_STRIPE_EVENT_HANDLERS );
	const currentBilling = useRef( billing );
	const currentShipping = useRef( shippingData );
	const currentPaymentRequest = useRef( paymentRequest );

	// update refs when any change.
	useEffect( () => {
		currentBilling.current = billing;
		currentShipping.current = shippingData;
		currentPaymentRequest.current = paymentRequest;
	}, [ billing, shippingData, paymentRequest ] );

	// set paymentRequest.
	useEffect( () => {
		// can't do anything if stripe isn't available yet or we have zero total.
		if ( ! stripe || ! billing.cartTotal.value ) {
			return;
		}

		// if payment request hasn't been set yet then set it.
		if ( ! currentPaymentRequest.current && ! isFinished ) {
			setPaymentRequest(
				getPaymentRequest( {
					total: billing.cartTotal,
					currencyCode: billing.currency.code.toLowerCase(),
					countryCode: shippingData.shippingAddress.country,
					shippingRequired: shippingData.needsShipping,
					cartTotalItems: billing.cartTotalItems,
					stripe,
				} )
			);
		}
		// otherwise we just update it (but only if payment processing hasn't
		// already started).
		if ( ! isProcessing && currentPaymentRequest.current && ! isFinished ) {
			updatePaymentRequest( {
				// @ts-ignore
				paymentRequest: currentPaymentRequest.current,
				total: billing.cartTotal,
				currencyCode: billing.currency.code.toLowerCase(),
				cartTotalItems: billing.cartTotalItems,
			} );
		}
	}, [
		billing.cartTotal,
		billing.currency.code,
		shippingData.shippingAddress.country,
		shippingData.needsShipping,
		billing.cartTotalItems,
		stripe,
		isProcessing,
		isFinished,
	] );

	// whenever paymentRequest changes, then we need to update whether
	// payment can be made.
	useEffect( () => {
		if ( paymentRequest ) {
			canDoPaymentRequest( paymentRequest ).then( ( result ) => {
				if ( result.requestType ) {
					setPaymentRequestType( result.requestType );
				}
				setCanMakePayment( result.canPay );
			} );
		}
	}, [ paymentRequest ] );

	// kick off payment processing.
	const onButtonClick = () => {
		setIsProcessing( true );
		setIsFinished( false );
		setExpressPaymentError( '' );
		onClick();
	};

	const abortPayment = ( paymentMethod, message ) => {
		const response = {
			fail: {
				message,
				billingData: getBillingData( paymentMethod ),
				paymentMethodData: getPaymentMethodData(
					paymentMethod,
					paymentRequestType
				),
			},
		};
		paymentMethod.complete( 'fail' );
		setIsProcessing( false );
		setIsFinished( true );
		return response;
	};

	const completePayment = ( paymentMethod ) => {
		paymentMethod.complete( 'success' );
		setIsFinished( true );
		setIsProcessing( false );
	};

	// event callbacks.
	const onShippingRatesEvent = ( forSuccess = true ) => ( shippingRates ) => {
		const handlers = eventHandlers.current;
		const billingData = currentBilling.current;
		if ( handlers.shippingAddressChange && isProcessing ) {
			handlers.shippingAddressChange.updateWith( {
				status: forSuccess ? 'success' : 'fail',
				shippingOptions: normalizeShippingOptions( shippingRates ),
				total: getTotalPaymentItem( billingData.cartTotal ),
				displayItems: normalizeLineItems( billingData.cartTotalItems ),
			} );
			handlers.shippingAddressChange = null;
		}
	};

	const onShippingSelectedRate = ( forSuccess = true ) => () => {
		const handlers = eventHandlers.current;
		const shipping = currentShipping.current;
		const billingData = currentBilling.current;
		if (
			handlers.shippingOptionChange &&
			! shipping.isSelectingRate &&
			isProcessing
		) {
			const updateObject = forSuccess
				? {
						status: 'success',
						total: getTotalPaymentItem( billingData.cartTotal ),
						displayItems: normalizeLineItems(
							billingData.cartTotalItems
						),
				  }
				: {
						status: 'fail',
				  };
			handlers.shippingOptionChange.updateWith( updateObject );
			handlers.shippingOptionChange = null;
		}
	};

	const onPaymentProcessing = () => {
		const handlers = eventHandlers.current;
		if ( handlers.sourceEvent && isProcessing ) {
			const response = {
				type: emitResponse.responseTypes.SUCCESS,
				meta: {
					billingData: getBillingData( handlers.sourceEvent ),
					paymentMethodData: getPaymentMethodData(
						handlers.sourceEvent,
						paymentRequestType
					),
					shippingData: getShippingData( handlers.sourceEvent ),
				},
			};
			return response;
		}
		return { type: emitResponse.responseTypes.SUCCESS };
	};

	const onCheckoutComplete = ( checkoutResponse ) => {
		const handlers = eventHandlers.current;
		let response = { type: emitResponse.responseTypes.SUCCESS };
		if ( handlers.sourceEvent && isProcessing ) {
			const { paymentStatus, paymentDetails } = checkoutResponse;
			if ( paymentStatus === emitResponse.responseTypes.SUCCESS ) {
				completePayment( handlers.sourceEvent );
			}
			if (
				paymentStatus === emitResponse.responseTypes.ERROR ||
				paymentStatus === emitResponse.responseTypes.FAIL
			) {
				const paymentResponse = abortPayment(
					handlers.sourceEvent,
					paymentDetails?.errorMessage
				);
				response = {
					type: emitResponse.responseTypes.ERROR,
					message: paymentResponse.message,
					messageContext:
						emitResponse.noticeContexts.EXPRESS_PAYMENTS,
					retry: true,
				};
			}
			handlers.sourceEvent = null;
		}
		return response;
	};

	// when canMakePayment is true, then we set listeners on payment request for
	// handling updates.
	useEffect( () => {
		if ( paymentRequest && canMakePayment && isProcessing ) {
			paymentRequest.on( 'shippingaddresschange', ( event ) => {
				// @todo check if there is an address change, and if not, then
				// just call updateWith and don't call setShippingAddress here
				// because the state won't change upstream.
				currentShipping.current.setShippingAddress(
					normalizeShippingAddressForCheckout( event.shippingAddress )
				);
				eventHandlers.current.shippingAddressChange = event;
			} );
			paymentRequest.on( 'shippingoptionchange', ( event ) => {
				currentShipping.current.setSelectedRates(
					normalizeShippingOptionSelectionsForCheckout(
						event.shippingOption
					)
				);
				eventHandlers.current.shippingOptionChange = event;
			} );
			paymentRequest.on( 'source', ( paymentMethod ) => {
				if (
					// eslint-disable-next-line no-undef
					! getStripeServerData().allowPrepaidCard &&
					paymentMethod.source.card.funding
				) {
					setExpressPaymentError(
						__(
							"Sorry, we're not accepting prepaid cards at this time.",
							'woocommerce-gateway-stripe'
						)
					);
					return;
				}
				eventHandlers.current.sourceEvent = paymentMethod;
				// kick off checkout processing step.
				onSubmit();
			} );
			paymentRequest.on( 'cancel', () => {
				setIsFinished( true );
				setIsProcessing( false );
				onClose();
			} );
		}
	}, [ paymentRequest, canMakePayment, isProcessing, onClose ] );

	// subscribe to events.
	useEffect( () => {
		if ( canMakePayment && isProcessing ) {
			const subscriber = eventRegistration;
			const unsubscribeShippingRateSuccess = subscriber.onShippingRateSuccess(
				onShippingRatesEvent()
			);
			const unsubscribeShippingRateFail = subscriber.onShippingRateFail(
				onShippingRatesEvent( false )
			);
			const unsubscribeShippingRateSelectSuccess = subscriber.onShippingRateSelectSuccess(
				onShippingSelectedRate()
			);
			const unsubscribeShippingRateSelectFail = subscriber.onShippingRateSelectFail(
				onShippingRatesEvent( false )
			);
			const unsubscribePaymentProcessing = subscriber.onPaymentProcessing(
				onPaymentProcessing
			);
			const unsubscribeCheckoutCompleteSuccess = subscriber.onCheckoutAfterProcessingWithSuccess(
				onCheckoutComplete
			);
			const unsubscribeCheckoutCompleteFail = subscriber.onCheckoutAfterProcessingWithError(
				onCheckoutComplete
			);
			return () => {
				unsubscribeCheckoutCompleteFail();
				unsubscribeCheckoutCompleteSuccess();
				unsubscribePaymentProcessing();
				unsubscribeShippingRateFail();
				unsubscribeShippingRateSuccess();
				unsubscribeShippingRateSelectSuccess();
				unsubscribeShippingRateSelectFail();
			};
		}
		return undefined;
	}, [
		canMakePayment,
		isProcessing,
		eventRegistration.onShippingRateSuccess,
		eventRegistration.onShippingRateFail,
		eventRegistration.onShippingRateSelectSuccess,
		eventRegistration.onShippingRateSelectFail,
		eventRegistration.onPaymentProcessing,
		eventRegistration.onCheckoutAfterProcessingWithSuccess,
		eventRegistration.onCheckoutAfterProcessingWithError,
	] );

	// locale is not a valid value for the paymentRequestButton style.
	const { theme } = getStripeServerData().button;

	const paymentRequestButtonStyle = {
		paymentRequestButton: {
			type: 'default',
			theme,
			height: '48px',
		},
	};

	return canMakePayment && paymentRequest ? (
		<PaymentRequestButtonElement
			onClick={ onButtonClick }
			options={ {
				style: paymentRequestButtonStyle,
				paymentRequest,
			} }
		/>
	) : null;
};

/**
 * PaymentRequestExpress with stripe provider
 *
 * @param {StripeRegisteredPaymentMethodProps} props
 */
export const PaymentRequestExpress = ( props ) => {
	const { locale } = getStripeServerData().button;
	const { stripe } = props;
	return (
		<Elements stripe={ stripe } locale={ locale }>
			<PaymentRequestExpressComponent { ...props } />
		</Elements>
	);
};
