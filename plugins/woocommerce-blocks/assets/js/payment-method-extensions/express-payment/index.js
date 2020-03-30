/**
 * Internal dependencies
 */
import {
	PAYMENT_METHOD_NAME,
	ApplePayExpress,
	applePayImage,
} from './apple-pay';
import { stripePromise } from '../stripe-utils';

const ApplePayPreview = () => <img src={ applePayImage } alt="" />;

export const ApplePayConfig = {
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
