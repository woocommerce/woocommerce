/**
 * @typedef {import('@woocommerce/type-defs/payments').PaymentMethodRegistrationOptions} PaymentMethodRegistrationOptions
 * @typedef {import('@woocommerce/type-defs/payments').ExpressPaymentMethodRegistrationOptions} ExpressPaymentMethodRegistrationOptions
 */

/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { default as PaymentMethodConfig } from './payment-method-config';
import { default as ExpressPaymentMethodConfig } from './express-payment-method-config';
import { canMakePaymentExtensionsCallbacks } from './extensions-config';
const paymentMethods = {};
const expressPaymentMethods = {};

/**
 * Register a regular payment method.
 *
 * @param {PaymentMethodRegistrationOptions} options Configuration options for the payment method.
 */
export const registerPaymentMethod = ( options ) => {
	let paymentMethodConfig;
	if ( typeof options === 'function' ) {
		// Legacy fallback for previous API, where client passes a function:
		// registerPaymentMethod( ( Config ) => new Config( options ) );
		paymentMethodConfig = options( PaymentMethodConfig );
		deprecated( 'Passing a callback to registerPaymentMethod()', {
			alternative: 'a config options object',
			plugin: 'woocommerce-gutenberg-products-block',
			link:
				'https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3404',
		} );
	} else {
		paymentMethodConfig = new PaymentMethodConfig( options );
	}
	if ( paymentMethodConfig instanceof PaymentMethodConfig ) {
		paymentMethods[ paymentMethodConfig.name ] = paymentMethodConfig;
	}
};

/**
 * Register an express payment method.
 *
 * @param {ExpressPaymentMethodRegistrationOptions} options Configuration options for the payment method.
 */
export const registerExpressPaymentMethod = ( options ) => {
	let paymentMethodConfig;
	if ( typeof options === 'function' ) {
		// Legacy fallback for previous API, where client passes a function:
		// registerExpressPaymentMethod( ( Config ) => new Config( options ) );
		paymentMethodConfig = options( ExpressPaymentMethodConfig );
		deprecated( 'Passing a callback to registerExpressPaymentMethod()', {
			alternative: 'a config options object',
			plugin: 'woocommerce-gutenberg-products-block',
			link:
				'https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3404',
		} );
	} else {
		paymentMethodConfig = new ExpressPaymentMethodConfig( options );
	}
	if ( paymentMethodConfig instanceof ExpressPaymentMethodConfig ) {
		expressPaymentMethods[ paymentMethodConfig.name ] = paymentMethodConfig;
	}
};

/**
 * Allows extension to register callbacks for specific payment methods to determine if they can make payments
 *
 * @param {string} namespace A unique string to identify the extension registering payment method callbacks.
 * @param {Record<string, function():any>} callbacks Example {stripe: () => {}, cheque: => {}}
 */
export const registerPaymentMethodExtensionCallbacks = (
	namespace,
	callbacks
) => {
	if ( canMakePaymentExtensionsCallbacks[ namespace ] ) {
		// eslint-disable-next-line no-console
		console.error(
			`The namespace provided to registerPaymentMethodExtensionCallbacks must be unique. Callbacks have already been registered for the ${ namespace } namespace.`
		);
	} else {
		// Set namespace up as an empty object.
		canMakePaymentExtensionsCallbacks[ namespace ] = {};

		Object.entries( callbacks ).forEach(
			( [ paymentMethodName, callback ] ) => {
				if ( typeof callback === 'function' ) {
					canMakePaymentExtensionsCallbacks[ namespace ][
						paymentMethodName
					] = callback;
				} else {
					// eslint-disable-next-line no-console
					console.error(
						`All callbacks provided to registerPaymentMethodExtensionCallbacks must be functions. The callback for the ${ paymentMethodName } payment method in the ${ namespace } namespace was not a function.`
					);
				}
			}
		);
	}
};

export const __experimentalDeRegisterPaymentMethod = ( paymentMethodName ) => {
	delete paymentMethods[ paymentMethodName ];
};

export const __experimentalDeRegisterExpressPaymentMethod = (
	paymentMethodName
) => {
	delete expressPaymentMethods[ paymentMethodName ];
};

export const getPaymentMethods = () => {
	return paymentMethods;
};

export const getExpressPaymentMethods = () => {
	return expressPaymentMethods;
};
