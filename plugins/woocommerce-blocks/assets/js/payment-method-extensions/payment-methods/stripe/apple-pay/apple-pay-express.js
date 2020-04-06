/**
 * Internal dependencies
 */
import {
	DEFAULT_STRIPE_EVENT_HANDLERS,
	PAYMENT_METHOD_NAME,
} from './constants';
import {
	getBillingData,
	getPaymentMethodData,
	normalizeShippingAddressForCheckout,
	normalizeShippingOptions,
	normalizeLineItems,
} from './normalize';
import {
	getStripeServerData,
	getPaymentRequest,
	updatePaymentRequest,
	canDoApplePay,
	getTotalPaymentItem,
	stripePromise,
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

// @todo, note it should be trivial to implement cross browser payment api
// handling here:
// - ApplePay in Safari
// - ChromePay in Chrome
// - not supported for other browsers yet (but if assuming stripe implements the
//   official PaymentRequest API in their library this should enable support!).

/**
 * AppleExpressComponent
 *
 * @param {StripeRegisteredPaymentMethodProps} props Incoming props
 */
const ApplePayExpressComponent = ( {
	paymentStatus,
	shippingData,
	billing,
	eventRegistration,
	onSubmit,
	activePaymentMethod,
	setActivePaymentMethod,
} ) => {
	/**
	 * @type {[ StripePaymentRequest|null, function( StripePaymentRequest ):StripePaymentRequest|null]}
	 */
	// @ts-ignore
	const [ paymentRequest, setPaymentRequest ] = useState( null );
	const stripe = useStripe();
	const [ canMakePayment, setCanMakePayment ] = useState( false );
	const eventHandlers = useRef( DEFAULT_STRIPE_EVENT_HANDLERS );
	const currentBilling = useRef( billing );
	const currentShipping = useRef( shippingData );
	const currentPaymentRequest = useRef( paymentRequest );
	const currentPaymentStatus = useRef( paymentStatus );
	const currentEventRegistration = useRef( eventRegistration );
	const isActive = useRef( activePaymentMethod === PAYMENT_METHOD_NAME );

	// update refs when any change
	useEffect( () => {
		currentBilling.current = billing;
		currentShipping.current = shippingData;
		currentPaymentRequest.current = paymentRequest;
		currentPaymentStatus.current = paymentStatus;
		currentEventRegistration.current = eventRegistration;
		isActive.current = activePaymentMethod === PAYMENT_METHOD_NAME;
	}, [ billing, shippingData, paymentRequest, paymentStatus, isActive ] );

	//set paymentRequest
	useEffect( () => {
		// can't do anything if stripe isn't available yet or we have zero total.
		if ( ! stripe || ! billing.cartTotal.value ) {
			return;
		}

		// if payment request hasn't been set yet then set it
		if ( ! currentPaymentRequest.current ) {
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
		if ( ! paymentStatus.isPristine && currentPaymentRequest.current ) {
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
		paymentStatus.isPristine,
		stripe,
	] );

	// whenever paymentRequest changes, then we need to update whether
	// payment can be made.
	useEffect( () => {
		if ( paymentRequest ) {
			canDoApplePay( paymentRequest ).then( ( result ) => {
				setCanMakePayment( result );
			} );
		}
	}, [ paymentRequest ] );

	// kick off payment processing.
	const onButtonClick = () => {
		setActivePaymentMethod( PAYMENT_METHOD_NAME );
		currentPaymentStatus.current.setPaymentStatus().processing();
	};

	const abortPayment = ( paymentMethod, message ) => {
		paymentMethod.complete( 'fail' );
		currentPaymentStatus.current
			.setPaymentStatus()
			.failed(
				message,
				getBillingData( paymentMethod ),
				getPaymentMethodData( paymentMethod, PAYMENT_METHOD_NAME )
			);
	};

	const completePayment = ( paymentMethod ) => {
		paymentMethod.complete( 'success' );
		currentPaymentStatus.current.setPaymentStatus().completed();
	};

	const processPayment = ( paymentMethod ) => {
		currentPaymentStatus.current
			.setPaymentStatus()
			.success(
				getBillingData( paymentMethod ),
				getPaymentMethodData( paymentMethod, PAYMENT_METHOD_NAME )
			);
		onSubmit();
	};

	// event callbacks.
	const onShippingRatesEvent = ( forSuccess = true ) => () => {
		const handlers = eventHandlers.current;
		if (
			typeof handlers.shippingAddressChange === 'function' &&
			currentPaymentStatus.current.isProcessing
		) {
			handlers.shippingAddressChange.updateWith( {
				status: forSuccess ? 'success' : 'fail',
				shippingOptions: normalizeShippingOptions(
					currentShipping.current.shippingRates
				),
				total: getTotalPaymentItem( currentBilling.current.cartTotal ),
				displayItems: normalizeLineItems(
					currentBilling.current.cartTotalItems
				),
			} );
		}
	};

	const onShippingSelectedRate = ( forSuccess = true ) => () => {
		const handlers = eventHandlers.current;
		if (
			typeof handlers.shippingOptionsChange === 'function' &&
			currentPaymentStatus.current.isProcessing
		) {
			const updateObject = forSuccess
				? {
						status: 'success',
						total: getTotalPaymentItem(
							currentBilling.current.cartTotal
						),
						displayItems: normalizeLineItems(
							currentBilling.current.cartTotalItems
						),
				  }
				: {
						status: 'fail',
				  };
			handlers.shippingOptionsChange.updateWith( updateObject );
		}
	};

	const onCheckoutComplete = ( forSuccess = true ) => () => {
		const handlers = eventHandlers.current;
		if (
			typeof handlers.sourceEvent === 'function' &&
			currentPaymentStatus.current.isSuccessful
		) {
			if ( forSuccess ) {
				completePayment( handlers.sourceEvent );
			} else {
				abortPayment( handlers.sourceEvent );
			}
		}
	};

	// when canMakePayment is true, then we set listeners on payment request for
	// handling updates.
	useEffect( () => {
		if ( paymentRequest && canMakePayment && isActive.current ) {
			paymentRequest.on( 'shippingaddresschange', ( event ) => {
				currentShipping.current.setShippingAddress(
					normalizeShippingAddressForCheckout( event.shippingAddress )
				);
				eventHandlers.current.shippingAddressChange = event;
			} );
			paymentRequest.on( 'shippingoptionchange', ( event ) => {
				currentShipping.current.setSelectedRates(
					normalizeShippingOptions( event.shipping )
				);
				eventHandlers.current.shippingOptionChange = event;
			} );
			paymentRequest.on( 'source', ( paymentMethod ) => {
				if (
					// eslint-disable-next-line no-undef
					! getStripeServerData().allowPrepaidCard &&
					paymentMethod.source.card.funding
				) {
					// @todo this error message can be converted to use wp.i18n
					// and be inline.
					abortPayment(
						paymentMethod,
						// eslint-disable-next-line no-undef
						__(
							"Sorry, we're not accepting prepaid cards at this time.",
							'woocommerce-gateway-stripe'
						)
					);
					return;
				}
				eventHandlers.current.sourceEvent = paymentMethod;
				processPayment( paymentMethod );
			} );
		}
	}, [ paymentRequest, canMakePayment ] );

	// subscribe to events.
	useEffect( () => {
		if ( canMakePayment && isActive.current ) {
			const subscriber = currentEventRegistration.current;
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
			const unsubscribeCheckoutCompleteSuccess = subscriber.onCheckoutCompleteSuccess(
				onCheckoutComplete()
			);
			const unsubscribeCheckoutCompleteFail = subscriber.onCheckoutCompleteError(
				onCheckoutComplete( false )
			);
			return () => {
				unsubscribeCheckoutCompleteFail();
				unsubscribeCheckoutCompleteSuccess();
				unsubscribeShippingRateFail();
				unsubscribeShippingRateSuccess();
				unsubscribeShippingRateSelectSuccess();
				unsubscribeShippingRateSelectFail();
			};
		}
	}, [ canMakePayment ] );

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
 * ApplePayExpress with stripe provider
 *
 * @param {RegisteredPaymentMethodProps|{}} props
 */
export const ApplePayExpress = ( props ) => {
	const { locale } = getStripeServerData().button;
	return (
		<Elements stripe={ stripePromise } locale={ locale }>
			<ApplePayExpressComponent { ...props } />
		</Elements>
	);
};
