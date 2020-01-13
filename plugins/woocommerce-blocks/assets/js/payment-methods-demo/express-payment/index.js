/**
 * Internal dependencies
 */
import { applePayImage } from './apple-pay';
import { paypalImage } from './paypal';

export const ExpressApplePay = () => <img src={ applePayImage } alt="" />;

export const ExpressPaypal = () => <img src={ paypalImage } alt="" />;
