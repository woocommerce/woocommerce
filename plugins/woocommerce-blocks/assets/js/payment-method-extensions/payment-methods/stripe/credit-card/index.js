/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { loadStripe } from '../stripe-utils';
import { StripeCreditCard } from './payment-method';
import { PAYMENT_METHOD_NAME } from './constants';

const stripePromise = loadStripe();

const Edit = ( props ) => {
	return <StripeCreditCard stripe={ stripePromise } { ...props } />;
};

const stripeCcPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	label: (
		<strong>
			{ __( 'Credit/Debit Card', 'woo-gutenberg-products-block' ) }
		</strong>
	),
	content: <StripeCreditCard stripe={ stripePromise } />,
	edit: <Edit />,
	canMakePayment: () => stripePromise,
	ariaLabel: __(
		'Stripe Credit Card payment method',
		'woo-gutenberg-products-block'
	),
};

export default stripeCcPaymentMethod;
