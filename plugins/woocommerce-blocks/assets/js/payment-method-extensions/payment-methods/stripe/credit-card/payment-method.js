/**
 * External dependencies
 */
import { Elements, useStripe } from '@stripe/react-stripe-js';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getStripeServerData } from '../stripe-utils';
import { useCheckoutSubscriptions } from './use-checkout-subscriptions';
import { InlineCard, CardElements } from './elements';

/**
 * @typedef {import('../stripe-utils/type-defs').Stripe} Stripe
 * @typedef {import('../stripe-utils/type-defs').StripePaymentRequest} StripePaymentRequest
 * @typedef {import('@woocommerce/type-defs/payment-method-interface').PaymentMethodInterface} RegisteredPaymentMethodProps
 */

export const getStripeCreditCardIcons = () => {
	return Object.entries( getStripeServerData().icons ).map(
		( [ id, { src, alt } ] ) => {
			return {
				id,
				src,
				alt,
			};
		}
	);
};

/**
 * Stripe Credit Card component
 *
 * @param {RegisteredPaymentMethodProps} props Incoming props
 */
const CreditCardComponent = ( {
	billing,
	eventRegistration,
	emitResponse,
	components,
} ) => {
	const { ValidationInputError, PaymentMethodIcons } = components;
	const [ sourceId, setSourceId ] = useState( '' );
	const stripe = useStripe();
	const onStripeError = useCheckoutSubscriptions(
		eventRegistration,
		billing,
		sourceId,
		setSourceId,
		emitResponse,
		stripe
	);
	const onChange = ( paymentEvent ) => {
		if ( paymentEvent.error ) {
			onStripeError( paymentEvent );
		}
		setSourceId( '0' );
	};
	const cardIcons = getStripeCreditCardIcons();

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
	return (
		<>
			{ renderedCardElement }
			{ PaymentMethodIcons && cardIcons.length && (
				<PaymentMethodIcons icons={ cardIcons } align="left" />
			) }
		</>
	);
};

export const StripeCreditCard = ( props ) => {
	const { locale } = getStripeServerData().button;
	const { stripe } = props;

	return (
		<Elements stripe={ stripe } locale={ locale }>
			<CreditCardComponent { ...props } />
		</Elements>
	);
};
