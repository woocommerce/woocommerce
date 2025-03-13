/**
 * External dependencies
 */
import type { EmptyObjectType, PaymentResult } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';
import {
	PlainPaymentMethods,
	PlainExpressPaymentMethods,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { SavedPaymentMethod } from './types';
import { STATUS as PAYMENT_STATUS } from './constants';
import { checkoutData } from '../checkout/constants';

const defaultPaymentMethod = checkoutData?.payment_method as
	| SavedPaymentMethod
	| string;

function getDefaultPaymentMethod() {
	if ( ! defaultPaymentMethod ) {
		return '';
	}

	// defaultPaymentMethod is a string if a regular payment method is set.
	if ( typeof defaultPaymentMethod === 'string' ) {
		return defaultPaymentMethod;
	}

	// defaultPaymentMethod is a SavedPaymentMethod object if a saved payment method is returned.
	if (
		defaultPaymentMethod?.method?.gateway &&
		defaultPaymentMethod?.tokenId
	) {
		return defaultPaymentMethod.method.gateway;
	}

	return '';
}

/**
 * Set the default payment method data. This can be in two places,
 * Either as part of the `defaultPaymentMethod` object or
 * as a token stored in `wcSettings`.
 */
function getDefaultPaymentMethodData() {
	if ( ! defaultPaymentMethod || typeof defaultPaymentMethod === 'string' ) {
		return {};
	}

	const token = defaultPaymentMethod.tokenId.toString();
	const slug = defaultPaymentMethod.method.gateway;
	const savedTokenKey = `wc-${ slug }-payment-token`;
	return { token, payment_method: slug, [ savedTokenKey ]: token };
}

export interface PaymentState {
	status: string;
	activePaymentMethod: string;
	// Available payment methods are payment methods which have been validated and can make payment.
	availablePaymentMethods: PlainPaymentMethods;
	availableExpressPaymentMethods: PlainExpressPaymentMethods;
	savedPaymentMethods:
		| Record< string, SavedPaymentMethod[] >
		| EmptyObjectType;
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
	savedPaymentMethods: getSetting<
		Record< string, SavedPaymentMethod[] > | EmptyObjectType
	>( 'customerPaymentMethods', {} ),
	paymentMethodData: getDefaultPaymentMethodData(),
	paymentResult: null,
	paymentMethodsInitialized: false,
	expressPaymentMethodsInitialized: false,
	shouldSavePaymentMethod: false,
};
