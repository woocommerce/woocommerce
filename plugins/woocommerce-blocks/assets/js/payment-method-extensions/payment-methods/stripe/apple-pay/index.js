/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';
import { ApplePayExpress } from './apple-pay-express';
import { applePayImage } from './apple-pay-preview';
import { stripePromise } from '../stripe-utils';

const ApplePayPreview = () => <img src={ applePayImage } alt="" />;

const ApplePayPaymentMethod = {
	id: PAYMENT_METHOD_NAME,
	content: <ApplePayExpress />,
	edit: <ApplePayPreview />,
	canMakePayment: stripePromise.then( ( stripe ) => {
		if ( stripe === null ) {
			return false;
		}
		// do a test payment request to check if apple pay can be done.
		const paymentRequest = stripe.paymentRequest( {
			total: {
				label: 'Test total',
				amount: 1000,
			},
			country: 'US',
			currency: 'usd',
		} );
		return paymentRequest.canMakePayment().then( ( result ) => {
			if ( result && result.applePay ) {
				return true;
			}
			return false;
		} );
	} ),
};

export default ApplePayPaymentMethod;
