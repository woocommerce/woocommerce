/**
 * External dependencies
 */
import {
	registerExpressPaymentMethod,
	registerPaymentMethod,
} from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import { expressApplePay, expressPaypal } from './express-payment';
import { paypalPaymentMethod, ccPaymentMethod } from './payment-methods';

registerExpressPaymentMethod(
	( Config ) =>
		new Config( {
			id: 'applepay',
			activeContent: expressApplePay,
			canMakePayment: Promise.resolve( true ),
		} )
);
registerExpressPaymentMethod(
	( Config ) =>
		new Config( {
			id: 'paypal',
			activeContent: expressPaypal,
			canMakePayment: Promise.resolve( true ),
		} )
);
registerPaymentMethod( ( Config ) => new Config( paypalPaymentMethod ) );
registerPaymentMethod( ( Config ) => new Config( ccPaymentMethod ) );
