/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME, StripeCreditCard } from './stripe';
import { stripePromise } from '../stripe-utils';

const EditPlaceHolder = () => <div>TODO: Card edit preview soon...</div>;

export const stripeCcPaymentMethod = {
	id: PAYMENT_METHOD_NAME,
	label: (
		<strong>
			{ __( 'Credit/Debit Card', 'woo-gutenberg-products-block' ) }
		</strong>
	),
	content: <StripeCreditCard />,
	edit: <EditPlaceHolder />,
	canMakePayment: stripePromise,
	ariaLabel: __(
		'Stripe Credit Card payment method',
		'woo-gutenberg-products-block'
	),
};
