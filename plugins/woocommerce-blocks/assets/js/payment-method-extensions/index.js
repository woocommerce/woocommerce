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
import { ApplePayConfig } from './express-payment';
import { stripeCcPaymentMethod } from './payment-methods';

registerExpressPaymentMethod( ( Config ) => new Config( ApplePayConfig ) );
registerPaymentMethod( ( Config ) => new Config( stripeCcPaymentMethod ) );
