/**
 * Internal dependencies
 */
import { assertValidPaymentMethodCreator } from './assertions';
import { default as PaymentMethodConfig } from './payment-method-config';
import { default as ExpressPaymentMethodConfig } from './express-payment-method-config';

const paymentMethods = {};
const expressPaymentMethods = {};

export const registerPaymentMethod = ( paymentMethodCreator ) => {
	assertValidPaymentMethodCreator(
		paymentMethodCreator,
		'PaymentMethodConfig'
	);
	const paymentMethodConfig = paymentMethodCreator( PaymentMethodConfig );
	if ( paymentMethodConfig instanceof PaymentMethodConfig ) {
		paymentMethods[ paymentMethodConfig.name ] = paymentMethodConfig;
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
		expressPaymentMethods[ paymentMethodConfig.name ] = paymentMethodConfig;
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
