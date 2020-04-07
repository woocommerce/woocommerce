/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';
import { getStripeServerData, stripePromise } from '../stripe-utils';
import { ccSvg } from './cc';
import { useCheckoutSubscriptions } from './use-checkout-subscriptions';
import { InlineCard, CardElements } from './elements';

/**
 * External dependencies
 */
import { Elements, useElements, useStripe } from '@stripe/react-stripe-js';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * @typedef {import('../stripe-utils/type-defs').Stripe} Stripe
 * @typedef {import('../stripe-utils/type-defs').StripePaymentRequest} StripePaymentRequest
 * @typedef {import('@woocommerce/type-defs/registered-payment-method-props').RegisteredPaymentMethodProps} RegisteredPaymentMethodProps
 */

/**
 * Stripe Credit Card component
 *
 * @param {RegisteredPaymentMethodProps} props Incoming props
 */
const CreditCardComponent = ( { billing, eventRegistration, components } ) => {
	const { ValidationInputError, CheckboxControl } = components;
	const { customerId } = billing;
	const [ sourceId, setSourceId ] = useState( 0 );
	const stripe = useStripe();
	const [ shouldSavePayment, setShouldSavePayment ] = useState(
		customerId ? true : false
	);
	const elements = useElements();
	const onStripeError = useCheckoutSubscriptions(
		eventRegistration,
		billing,
		sourceId,
		setSourceId,
		shouldSavePayment,
		stripe,
		elements
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
	return (
		<>
			{ renderedCardElement }
			{ customerId > 0 && (
				<CheckboxControl
					className="wc-block-checkout__save-card-info"
					label={ __(
						'Save payment information to my account for future purchases.',
						'woo-gutenberg-products-block'
					) }
					checked={ shouldSavePayment }
					onChange={ () =>
						setShouldSavePayment( ! shouldSavePayment )
					}
				/>
			) }
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
