/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	CardElement,
	CardNumberElement,
	CardExpiryElement,
	CardCvcElement,
} from '@stripe/react-stripe-js';

/**
 * Internal dependencies
 */
import { useElementOptions } from './use-element-options';

/** @typedef {import('react')} React */

const baseTextInputStyles = 'wc-block-gateway-input';

/**
 * InlineCard component
 *
 * @param {Object} props Incoming props for the component.
 * @param {React.ReactElement} props.inputErrorComponent
 * @param {function(any):any} props.onChange
 */
export const InlineCard = ( {
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
						'woocommerce'
					) }
				</label>
			</div>
			<ValidationInputError errorMessage={ error } />
		</>
	);
};

/**
 * CardElements component.
 *
 * @param {Object} props
 * @param {function(any):any} props.onChange
 * @param {React.ReactElement} props.inputErrorComponent
 */
export const CardElements = ( {
	onChange,
	inputErrorComponent: ValidationInputError,
} ) => {
	const [ isEmpty, setIsEmpty ] = useState( {
		cardNumber: true,
		cardExpiry: true,
		cardCvc: true,
	} );
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
	const errorCallback = ( errorSetter, elementId ) => ( event ) => {
		if ( event.error ) {
			errorSetter( event.error.message );
		} else {
			errorSetter( '' );
		}
		setIsEmpty( { ...isEmpty, [ elementId ]: event.empty } );
		onChange( event );
	};
	return (
		<div className="wc-block-card-elements">
			<div className="wc-block-gateway-container wc-card-number-element">
				<CardNumberElement
					onChange={ errorCallback( cardNumSetError, 'cardNumber' ) }
					options={ cardNumOptions }
					className={ baseTextInputStyles }
					id="wc-stripe-card-number-element"
					onFocus={ () => cardNumOnActive( isEmpty.cardNumber ) }
					onBlur={ () => cardNumOnActive( isEmpty.cardNumber ) }
				/>
				<label htmlFor="wc-stripe-card-number-element">
					{ __( 'Card Number', 'woo-gutenberg-product-blocks' ) }
				</label>
				<ValidationInputError errorMessage={ cardNumError } />
			</div>
			<div className="wc-block-gateway-container wc-card-expiry-element">
				<CardExpiryElement
					onChange={ errorCallback(
						cardExpirySetError,
						'cardExpiry'
					) }
					options={ cardExpiryOptions }
					className={ baseTextInputStyles }
					onFocus={ () => cardExpiryOnActive( isEmpty.cardExpiry ) }
					onBlur={ () => cardExpiryOnActive( isEmpty.cardExpiry ) }
					id="wc-stripe-card-expiry-element"
				/>
				<label htmlFor="wc-stripe-card-expiry-element">
					{ __( 'Expiry Date', 'woo-gutenberg-product-blocks' ) }
				</label>
				<ValidationInputError errorMessage={ cardExpiryError } />
			</div>
			<div className="wc-block-gateway-container wc-card-cvc-element">
				<CardCvcElement
					onChange={ errorCallback( cardCvcSetError, 'cardCvc' ) }
					options={ cardCvcOptions }
					className={ baseTextInputStyles }
					onFocus={ () => cardCvcOnActive( isEmpty.cardCvc ) }
					onBlur={ () => cardCvcOnActive( isEmpty.cardCvc ) }
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
