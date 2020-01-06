/**
 * Internal dependencies
 */
import { assertValidPaymentMethodCreator } from './assertions';
import { default as PaymentMethodConfig } from './payment-method-config';
import { default as ExpressPaymentMethodConfig } from './express-payment-method-config';

// currently much leeway is given to the payment method for the shape of their components. We should investigate payment methods
// using a component creator that is fed a configuration object so that the built component for the payment method is tightly
// controlled (fitting the ui/ux requirements of the checkout/cart).  Once we know the pattern most payment methods will follow
// (i.e. fields, event callbacks, validation, etc) this is likely more feasible.

const paymentMethods = {};
const expressPaymentMethods = {};

export const registerPaymentMethod = ( paymentMethodCreator ) => {
	assertValidPaymentMethodCreator(
		paymentMethodCreator,
		'PaymentMethodConfig'
	);
	const paymentMethodConfig = paymentMethodCreator( PaymentMethodConfig );
	if ( paymentMethodConfig instanceof PaymentMethodConfig ) {
		paymentMethods[ paymentMethodConfig.id ] = paymentMethodConfig;
	}
};

export const registerExpressPaymentMethod = ( expressPaymentMethodCreator ) => {
	assertValidPaymentMethodCreator(
		expressPaymentMethodCreator,
		'ExpressPaymentMethodConfig'
	);
	const paymentMethodConfig = expressPaymentMethodCreator(
		ExpressPaymentMethodConfig
	);
	if ( paymentMethodConfig instanceof ExpressPaymentMethodConfig ) {
		expressPaymentMethods[ paymentMethodConfig.id ] = paymentMethodConfig;
	}
};

export const getPaymentMethods = () => {
	return paymentMethods;
};

export const getExpressPaymentMethods = () => {
	return expressPaymentMethods;
};
