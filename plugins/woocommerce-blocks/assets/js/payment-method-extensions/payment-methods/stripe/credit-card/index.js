/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { loadStripe } from '../stripe-utils';
import { StripeCreditCard } from './payment-method';
import { PAYMENT_METHOD_NAME } from './constants';

const stripePromise = loadStripe();

const StripeComponent = ( props ) => {
	const [ errorMessage, setErrorMessage ] = useState( '' );
	useEffect( () => {
		Promise.resolve( stripePromise ).then( ( { error } ) => {
			if ( error ) {
				setErrorMessage( error.message );
			}
		} );
	}, [ stripePromise, setErrorMessage ] );

	useEffect( () => {
		if ( errorMessage ) {
			throw new Error( errorMessage );
		}
	}, [ errorMessage ] );

	return <StripeCreditCard stripe={ stripePromise } { ...props } />;
};

const stripeCcPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: (
		<strong>
			{ __( 'Credit/Debit Card', 'woo-gutenberg-products-block' ) }
		</strong>
	),
	content: <StripeComponent />,
	edit: <StripeComponent />,
	canMakePayment: () => stripePromise,
	ariaLabel: __(
		'Stripe Credit Card payment method',
		'woo-gutenberg-products-block'
	),
};

export default stripeCcPaymentMethod;
