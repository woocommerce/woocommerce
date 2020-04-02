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
import ApplePayPaymentMethod from './apple-pay';

registerPaymentMethod( ( Config ) => new Config( stripeCcPaymentMethod ) );
registerExpressPaymentMethod(
	( Config ) => new Config( ApplePayPaymentMethod )
);
