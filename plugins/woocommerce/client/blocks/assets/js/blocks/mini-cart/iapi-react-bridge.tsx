/**
 * Bridge file to expose React components to iAPI blocks via window.wc.blocksCheckout
 * This is a separate entry point to avoid circular dependencies.
 */

/**
 * External dependencies
 */
import { ExpressPaymentMethods } from '../cart-checkout-shared/payment-methods';
import MiniCartExpressPaymentWrapper from './components/mini-cart-express-payment-wrapper';

/**
 * Expose components to window for iAPI blocks to use
 */
if ( typeof window !== 'undefined' ) {
	window.wc = window.wc || {};
	window.wc.blocksCheckout = window.wc.blocksCheckout || {};
	window.wc.blocksCheckout.ExpressPaymentMethods = ExpressPaymentMethods;
	window.wc.blocksCheckout.MiniCartExpressPaymentWrapper =
		MiniCartExpressPaymentWrapper;
}
