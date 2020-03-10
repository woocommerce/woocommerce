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
import { ExpressApplePay, ExpressPaypal } from './express-payment';
import { paypalPaymentMethod, ccPaymentMethod } from './payment-methods';

registerExpressPaymentMethod(
	( Config ) =>
		new Config( {
			id: 'applepay',
			activeContent: <ExpressApplePay />,
			edit: <ExpressApplePay />,
			canMakePayment: Promise.resolve( true ),
		} )
);
registerExpressPaymentMethod(
	( Config ) =>
		new Config( {
			id: 'paypal',
			activeContent: <ExpressPaypal />,
			edit: <ExpressPaypal />,
			canMakePayment: Promise.resolve( true ),
		} )
);
registerPaymentMethod( ( Config ) => new Config( paypalPaymentMethod ) );
registerPaymentMethod( ( Config ) => new Config( ccPaymentMethod ) );
