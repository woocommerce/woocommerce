/**
 * External dependencies
 */
import type {
	EmptyObjectType,
	GlobalPaymentMethod,
	PaymentResult,
} from '@woocommerce/types';
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

export interface PaymentState {
	status: string;
	activePaymentMethod: string;
	activeSavedToken: string;
	// Avilable payment methods are payment methods which have been validated and can make payment
	availablePaymentMethods: PlainPaymentMethods;
	availableExpressPaymentMethods: PlainExpressPaymentMethods;
	savedPaymentMethods:
		| Record< string, SavedPaymentMethod[] >
		| EmptyObjectType;
	paymentMethodData: Record< string, unknown >;
	paymentResult: PaymentResult | null;
	incompatiblePaymentMethods: Record< string, string >;
	paymentMethodsInitialized: boolean;
	expressPaymentMethodsInitialized: boolean;
	shouldSavePaymentMethod: boolean;
}
const incompatiblePaymentMethods: Record< string, string > = {};

if ( getSetting( 'globalPaymentMethods' ) ) {
	getSetting< GlobalPaymentMethod[] >( 'globalPaymentMethods' ).forEach(
		( method ) => {
			incompatiblePaymentMethods[ method.id ] = method.title;
		}
	);
}

export const defaultPaymentState: PaymentState = {
	status: PAYMENT_STATUS.PRISTINE,
	activePaymentMethod: '',
	activeSavedToken: '',
	availablePaymentMethods: {},
	availableExpressPaymentMethods: {},
	savedPaymentMethods: getSetting<
		Record< string, SavedPaymentMethod[] > | EmptyObjectType
	>( 'customerPaymentMethods', {} ),
	paymentMethodData: {},
	incompatiblePaymentMethods,
	paymentResult: null,
	paymentMethodsInitialized: false,
	expressPaymentMethodsInitialized: false,
	shouldSavePaymentMethod: false,
};
