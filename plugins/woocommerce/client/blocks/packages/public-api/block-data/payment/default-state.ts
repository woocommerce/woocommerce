/**
 * External dependencies
 */
import type {
	EmptyObjectType,
	PaymentResult,
	GlobalPaymentMethod,
} from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';
import { shouldPreselectPaymentMethod } from '@woocommerce/block-settings';
import {
	PlainPaymentMethods,
	PlainExpressPaymentMethods,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { SavedPaymentMethod } from './types';
import { isEditor } from '../utils';
import { STATUS as PAYMENT_STATUS } from './constants';
import { checkoutData } from '../checkout/constants';

const globalPaymentMethods = getSetting< GlobalPaymentMethod[] >(
	'globalPaymentMethods'
);

const savedPaymentMethods = getSetting<
	Record< string, SavedPaymentMethod[] > | EmptyObjectType
>( 'customerPaymentMethods', {} );

// When the store does not preselect a payment method, the server only returns the shopper's
// own choice in checkoutData.payment_method, so only the editor preview needs the setting here.
const defaultPaymentMethod = isEditor()
	? ( shouldPreselectPaymentMethod() && globalPaymentMethods[ 0 ]?.id ) || ''
	: checkoutData?.payment_method;

function getDefaultPaymentMethod() {
	if ( ! defaultPaymentMethod ) {
		return '';
	}

	return defaultPaymentMethod as string;
}

/**
 * Set the default payment method data. This can be in two places,
 * Either as part of the `defaultPaymentMethod` object or
 * as a token stored in `wcSettings`.
 */
function getDefaultPaymentMethodData() {
	// The session remembers only the gateway, not the card the shopper picked, so don't pick one for them.
	if ( ! defaultPaymentMethod || ! shouldPreselectPaymentMethod() ) {
		return {};
	}

	// Check if default payment method exists in saved payment methods
	const flatSavedPaymentMethods = Object.keys( savedPaymentMethods ).flatMap(
		( type ) => savedPaymentMethods[ type ]
	);
	const gatewaySavedPaymentMethods = flatSavedPaymentMethods.filter(
		( method ) => method.method.gateway === defaultPaymentMethod
	);
	const savedPaymentMethod =
		gatewaySavedPaymentMethods.find( ( method ) => method.is_default ) ||
		gatewaySavedPaymentMethods[ 0 ];

	// If a saved payment method is found that matches the default payment method,
	// use it.
	if ( savedPaymentMethod ) {
		const token = savedPaymentMethod.tokenId.toString();
		const slug = savedPaymentMethod.method.gateway;
		const savedTokenKey = `wc-${ slug }-payment-token`;
		return { token, payment_method: slug, [ savedTokenKey ]: token };
	}

	return {};
}

export interface PaymentState {
	status: string;
	activePaymentMethod: string;
	// Available payment methods are payment methods which have been validated and can make payment.
	availablePaymentMethods: PlainPaymentMethods;
	availableExpressPaymentMethods: PlainExpressPaymentMethods;
	// Registered express payment methods are all express payment methods from the registry (before filtering).
	registeredExpressPaymentMethods: PlainExpressPaymentMethods;
	savedPaymentMethods:
		Record< string, SavedPaymentMethod[] > | EmptyObjectType;
	paymentMethodData: Record< string, unknown >;
	paymentResult: PaymentResult | null;
	paymentMethodsInitialized: boolean;
	expressPaymentMethodsInitialized: boolean;
	shouldSavePaymentMethod: boolean;
}

export const defaultPaymentState: PaymentState = {
	status: PAYMENT_STATUS.IDLE,
	activePaymentMethod: getDefaultPaymentMethod(),
	availablePaymentMethods: {},
	availableExpressPaymentMethods: {},
	registeredExpressPaymentMethods: {},
	savedPaymentMethods: getSetting<
		Record< string, SavedPaymentMethod[] > | EmptyObjectType
	>( 'customerPaymentMethods', {} ),
	paymentMethodData: getDefaultPaymentMethodData(),
	paymentResult: null,
	paymentMethodsInitialized: false,
	expressPaymentMethodsInitialized: false,
	shouldSavePaymentMethod: false,
};
