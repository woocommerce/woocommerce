/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';
import {
	getStripeServerData,
	stripePromise,
	getErrorMessageForTypeAndCode,
} from '../../stripe-utils';
import { ccSvg } from './cc';

/**
 * External dependencies
 */
import {
	Elements,
	CardElement,
	CardNumberElement,
	CardExpiryElement,
	CardCvcElement,
	useStripe,
} from '@stripe/react-stripe-js';
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * @typedef {import('../../stripe-utils/type-defs').Stripe} Stripe
 * @typedef {import('../../stripe-utils/type-defs').StripePaymentRequest} StripePaymentRequest
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').RegisteredPaymentMethodProps} RegisteredPaymentMethodProps
 */

const elementOptions = {
	style: {
		base: {
			iconColor: '#666EE8',
			color: '#31325F',
			fontSize: '15px',
			'::placeholder': {
				color: '#fff',
			},
		},
	},
	classes: {
		focus: 'focused',
		empty: 'empty',
		invalid: 'has-error',
	},
};

const useElementOptions = ( overloadedOptions ) => {
	const [ isActive, setIsActive ] = useState( false );
	const [ options, setOptions ] = useState( {
		...elementOptions,
		...overloadedOptions,
	} );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		const color = isActive ? '#CFD7E0' : '#fff';

		setOptions( ( prevOptions ) => {
			const showIcon =
				typeof prevOptions.showIcon !== 'undefined'
					? { showIcon: isActive }
					: {};
			return {
				...options,
				style: {
					...options.style,
					base: {
						...options.style.base,
						'::placeholder': {
							color,
						},
					},
				},
				...showIcon,
			};
		} );
	}, [ isActive ] );

	const onActive = useCallback(
		( isEmpty ) => {
			if ( ! isEmpty ) {
				setIsActive( true );
			} else {
				setIsActive( ( prevActive ) => ! prevActive );
			}
		},
		[ setIsActive ]
	);
	return { options, onActive, error, setError };
};

const baseTextInputStyles = 'wc-block-gateway-input';

const InlineCard = ( {
	inputErrorComponent: ValidationInputError,
	onChange,
} ) => {
	const [ isEmpty, setIsEmpty ] = useState( true );
	const { options, onActive, error, setError } = useElementOptions( {
		hidePostalCode: true,
	} );
	const errorCallback = ( event ) => {
		if ( event.error ) {
			setError( event.error.message );
		} else {
			setError( '' );
		}
		setIsEmpty( event.empty );
		onChange( event );
	};
	return (
		<>
			<div className="wc-block-gateway-container wc-inline-card-element">
				<CardElement
					id="wc-stripe-inline-card-element"
					className={ baseTextInputStyles }
					options={ options }
					onBlur={ () => onActive( isEmpty ) }
					onFocus={ () => onActive( isEmpty ) }
					onChange={ errorCallback }
				/>
				<label htmlFor="wc-stripe-inline-card-element">
					{ __(
						'Credit Card Information',
						'woo-gutenberg-products-block'
					) }
				</label>
			</div>
			<ValidationInputError errorMessage={ error } />
		</>
	);
};

const CardElements = ( {
	onChange,
	inputErrorComponent: ValidationInputError,
} ) => {
	const [ isEmpty, setIsEmpty ] = useState( true );
	const {
		options: cardNumOptions,
		onActive: cardNumOnActive,
		error: cardNumError,
		setError: cardNumSetError,
	} = useElementOptions( { showIcon: false } );
	const {
		options: cardExpiryOptions,
		onActive: cardExpiryOnActive,
		error: cardExpiryError,
		setError: cardExpirySetError,
	} = useElementOptions();
	const {
		options: cardCvcOptions,
		onActive: cardCvcOnActive,
		error: cardCvcError,
		setError: cardCvcSetError,
	} = useElementOptions();
	const errorCallback = ( errorSetter ) => ( event ) => {
		if ( event.error ) {
			errorSetter( event.error.message );
		} else {
			errorSetter( '' );
		}
		setIsEmpty( event.empty );
		onChange( event );
	};
	return (
		<div className="wc-block-card-elements">
			<div className="wc-block-gateway-container wc-card-number-element">
				<CardNumberElement
					onChange={ errorCallback( cardNumSetError ) }
					options={ cardNumOptions }
					className={ baseTextInputStyles }
					id="wc-stripe-card-number-element"
					onFocus={ () => cardNumOnActive( isEmpty ) }
					onBlur={ () => cardNumOnActive( isEmpty ) }
				/>
				<label htmlFor="wc-stripe-card-number-element">
					{ __( 'Card Number', 'woo-gutenberg-product-blocks' ) }
				</label>
				<ValidationInputError errorMessage={ cardNumError } />
			</div>
			<div className="wc-block-gateway-container wc-card-expiry-element">
				<CardExpiryElement
					onChange={ errorCallback( cardExpirySetError ) }
					options={ cardExpiryOptions }
					className={ baseTextInputStyles }
					onFocus={ cardExpiryOnActive }
					onBlur={ cardExpiryOnActive }
					id="wc-stripe-card-expiry-element"
				/>
				<label htmlFor="wc-stripe-card-expiry-element">
					{ __( 'Expiry Date', 'woo-gutenberg-product-blocks' ) }
				</label>
				<ValidationInputError errorMessage={ cardExpiryError } />
			</div>
			<div className="wc-block-gateway-container wc-card-cvc-element">
				<CardCvcElement
					onChange={ errorCallback( cardCvcSetError ) }
					options={ cardCvcOptions }
					className={ baseTextInputStyles }
					onFocus={ cardCvcOnActive }
					onBlur={ cardCvcOnActive }
					id="wc-stripe-card-code-element"
				/>
				<label htmlFor="wc-stripe-card-code-element">
					{ __( 'CVV/CVC', 'woo-gutenberg-product-blocks' ) }
				</label>
				<ValidationInputError errorMessage={ cardCvcError } />
			</div>
		</div>
	);
};

const useStripeCheckoutSubscriptions = (
	eventRegistration,
	paymentStatus,
	billing,
	sourceId,
	setSourceId,
	shouldSavePayment,
	stripe
) => {
	const onStripeError = useRef( ( event ) => {
		return event;
	} );
	// hook into and register callbacks for events.
	useEffect( () => {
		onStripeError.current = ( event ) => {
			const type = event.error.type;
			const code = event.error.code || '';
			let message = getErrorMessageForTypeAndCode( type, code );
			message = message || event.error.message;
			paymentStatus.setPaymentStatus().error( message );

			// @todo we'll want to do inline invalidation errors for any element
			// inputs
			return {};
		};
		const createSource = async ( stripeBilling ) => {
			return await stripe.createSource( stripeBilling );
		};
		const onSubmit = async () => {
			paymentStatus.setPaymentStatus().processing();
			const { billingData } = billing;
			// use token if it's set.
			if ( sourceId !== 0 ) {
				paymentStatus.setPaymentStatus().success( billingData, {
					paymentMethod: PAYMENT_METHOD_NAME,
					paymentRequestType: 'cc',
					sourceId,
					shouldSavePayment,
				} );
				return true;
			}
			const stripeBilling = {
				address: {
					line1: billingData.address_1,
					line2: billingData.address_2,
					city: billingData.city,
					state: billingData.state,
					postal_code: billingData.postcode,
					country: billingData.country,
				},
			};
			if ( billingData.phone ) {
				stripeBilling.phone = billingData.phone;
			}
			if ( billingData.email ) {
				stripeBilling.email = billingData.email;
			}
			if ( billingData.first_name || billingData.last_name ) {
				stripeBilling.name = `${ billingData.first_name } ${ billingData.last_name }`;
			}

			const response = await createSource( stripeBilling );
			if ( response.error ) {
				return onStripeError.current( response );
			}
			paymentStatus.setPaymentStatus().success( billingData, {
				sourceId: response.source.id,
				paymentMethod: PAYMENT_METHOD_NAME,
				paymentRequestType: 'cc',
				shouldSavePayment,
			} );
			setSourceId( response.source.id );
			return true;
		};
		const onComplete = () => {
			paymentStatus.setPaymentStatus().completed();
		};
		const onError = () => {
			paymentStatus.setPaymentStatus().started();
		};
		// @todo Right now all the registered callbacks will go stale, so we need
		// either implement useRef or make sure functions being used from these
		// callbacks don't change so we can add them as dependencies.
		// validation and stripe processing (get source etc).
		const unsubscribeProcessing = eventRegistration.onCheckoutProcessing(
			onSubmit
		);
		const unsubscribeCheckoutComplete = eventRegistration.onCheckoutCompleteSuccess(
			onComplete
		);
		const unsubscribeCheckoutCompleteError = eventRegistration.onCheckoutCompleteError(
			onError
		);
		return () => {
			unsubscribeProcessing();
			unsubscribeCheckoutComplete();
			unsubscribeCheckoutCompleteError();
		};
	}, [
		eventRegistration.onCheckoutProcessing,
		eventRegistration.onCheckoutCompleteSuccess,
		eventRegistration.onCheckoutCompleteError,
		paymentStatus.setPaymentStatus,
		stripe,
		sourceId,
		billing.billingData,
		setSourceId,
		shouldSavePayment,
	] );
	return onStripeError.current;
};

// @todo add intents?

/**
 * Stripe Credit Card component
 *
 * @param {RegisteredPaymentMethodProps} props Incoming props
 */
const CreditCardComponent = ( {
	paymentStatus,
	billing,
	eventRegistration,
	components,
} ) => {
	const { ValidationInputError, CheckboxControl } = components;
	const [ sourceId, setSourceId ] = useState( 0 );
	const stripe = useStripe();
	const [ shouldSavePayment, setShouldSavePayment ] = useState( true );
	const onStripeError = useStripeCheckoutSubscriptions(
		eventRegistration,
		paymentStatus,
		billing,
		sourceId,
		shouldSavePayment,
		stripe
	);
	const onChange = ( paymentEvent ) => {
		if ( paymentEvent.error ) {
			onStripeError( paymentEvent );
		}
		setSourceId( 0 );
	};
	const renderedCardElement = getStripeServerData().inline_cc_form ? (
		<InlineCard
			onChange={ onChange }
			inputErrorComponent={ ValidationInputError }
		/>
	) : (
		<CardElements
			onChange={ onChange }
			inputErrorComponent={ ValidationInputError }
		/>
	);

	// we need to pass along source for customer from server if it's available
	// and pre-populate for checkout (so it'd need to be returned with the
	// order endpoint and available on billing details?)
	// so this will need to be an option for selecting if there's a saved
	// source attached with the order (see woocommerce/templates/myaccount/payment-methods.php)
	// so that data will need to be included with the order endpoint (billing data) to choose from.

	//@todo do need to add save payment method checkbox here.
	return (
		<>
			{ renderedCardElement }
			<CheckboxControl
				className="wc-block-checkout__save-card-info"
				label={ __(
					'Save payment information to my account for future purchases.',
					'woo-gutenberg-products-block'
				) }
				checked={ shouldSavePayment }
				onChange={ () => setShouldSavePayment( ! shouldSavePayment ) }
			/>
			<img
				src={ ccSvg }
				alt={ __(
					'Accepted cards for processing',
					'woo-gutenberg-products-block'
				) }
				className="wc-blocks-credit-card-images"
			/>
		</>
	);
};

export const StripeCreditCard = ( props ) => {
	const { locale } = getStripeServerData().button;
	const { activePaymentMethod } = props;

	return activePaymentMethod === PAYMENT_METHOD_NAME ? (
		<Elements stripe={ stripePromise } locale={ locale }>
			<CreditCardComponent { ...props } />
		</Elements>
	) : null;
};
