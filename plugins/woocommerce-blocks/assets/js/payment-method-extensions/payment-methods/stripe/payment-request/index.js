/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';
import { PaymentRequestExpress } from './payment-request-express';
import { applePayImage } from './apple-pay-preview';
import { loadStripe } from '../stripe-utils';

const ApplePayPreview = () => <img src={ applePayImage } alt="" />;

const canPayStripePromise = loadStripe();
const componentStripePromise = loadStripe();

let isStripeInitialized = false,
	canPay = false;

// Initialise stripe API client and determine if payment method can be used
// in current environment (e.g. geo + shopper has payment settings configured).
function paymentRequestAvailable( { currencyCode, totalPrice } ) {
	// Stripe only supports carts of greater value than 30 cents.
	if ( totalPrice < 30 ) {
		return false;
	}

	// If we've already initialised, return the cached results.
	if ( isStripeInitialized ) {
		return canPay;
	}

	return canPayStripePromise.then( ( stripe ) => {
		if ( stripe === null ) {
			isStripeInitialized = true;
			return canPay;
		}
		if ( stripe.error && stripe.error instanceof Error ) {
			throw stripe.error;
		}
		// Do a test payment to confirm if payment method is available.
		const paymentRequest = stripe.paymentRequest( {
			total: {
				label: 'Total',
				amount: totalPrice,
				pending: true,
			},
			country: getSetting( 'baseLocation', {} )?.country,
			currency: currencyCode,
		} );
		return paymentRequest.canMakePayment().then( ( result ) => {
			canPay = !! result;
			isStripeInitialized = true;
			return canPay;
		} );
	} );
}

const paymentRequestPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	content: <PaymentRequestExpress stripe={ componentStripePromise } />,
	edit: <ApplePayPreview />,
	canMakePayment: ( cartData ) =>
		paymentRequestAvailable( {
			currencyCode: cartData?.cartTotals?.currency_code?.toLowerCase(),
			totalPrice: parseInt( cartData?.cartTotals?.total_price || 0, 10 ),
		} ),
	paymentMethodId: 'stripe',
};

export default paymentRequestPaymentMethod;
