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

const EditPlaceHolder = () => <div>TODO: Card edit preview soon...</div>;

const stripePromise = loadStripe();

const stripeCcPaymentMethod = {
	id: PAYMENT_METHOD_NAME,
	label: (
		<strong>
			{ __( 'Credit/Debit Card', 'woo-gutenberg-products-block' ) }
		</strong>
	),
	content: <StripeCreditCard stripe={ stripePromise } />,
	edit: <EditPlaceHolder />,
	canMakePayment: stripePromise,
	ariaLabel: __(
		'Stripe Credit Card payment method',
		'woo-gutenberg-products-block'
	),
};

export default stripeCcPaymentMethod;
