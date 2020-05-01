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

const PaymentRequestPaymentMethod = {
	name: PAYMENT_METHOD_NAME,
	content: <PaymentRequestExpress stripe={ componentStripePromise } />,
	edit: <ApplePayPreview />,
	canMakePayment: ( cartData ) =>
		canPayStripePromise.then( ( stripe ) => {
			if ( stripe === null ) {
				return false;
			}
			// do a test payment request to check if payment request payment can be
			// done.
			const paymentRequest = stripe.paymentRequest( {
				total: {
					label: 'Test total',
					amount: 1000,
				},
				country: getSetting( 'baseLocation', {} )?.country,
				// eslint-disable-next-line camelcase
				currency: cartData?.cartTotals?.currency_code?.toLowerCase(),
			} );
			return paymentRequest
				.canMakePayment()
				.then( ( result ) => !! result );
		} ),
	paymentMethodId: 'stripe',
};

export default PaymentRequestPaymentMethod;
