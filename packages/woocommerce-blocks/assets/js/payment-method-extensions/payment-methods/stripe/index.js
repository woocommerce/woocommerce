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
import PaymentRequestPaymentMethod from './payment-request';

registerPaymentMethod( ( Config ) => new Config( stripeCcPaymentMethod ) );
registerExpressPaymentMethod(
	( Config ) => new Config( PaymentRequestPaymentMethod )
);
