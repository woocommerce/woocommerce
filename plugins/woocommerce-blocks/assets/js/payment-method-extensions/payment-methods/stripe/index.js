/**
 * External dependencies
 */
import {
	registerPaymentMethod,
	registerExpressPaymentMethod,
} from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import stripeCcPaymentMethod from './credit-card';
import paymentRequestPaymentMethod from './payment-request';
import { getStripeServerData } from './stripe-utils';

// Register Stripe Credit Card.
registerPaymentMethod( stripeCcPaymentMethod );

// Register Stripe Payment Request (Apple/Chrome Pay) if enabled.
if ( getStripeServerData().allowPaymentRequest ) {
	registerExpressPaymentMethod( paymentRequestPaymentMethod );
}
