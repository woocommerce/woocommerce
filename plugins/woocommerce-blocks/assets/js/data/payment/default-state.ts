/**
 * External dependencies
 */
import type { EmptyObjectType } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';
import {
	PlainPaymentMethods,
	PlainExpressPaymentMethods,
} from '@woocommerce/type-defs/payments';

/**
 * Internal dependencies
 */
import { SavedPaymentMethod } from './types';

export interface PaymentMethodDataState {
	currentStatus: {
		isPristine: boolean;
		isStarted: boolean;
		isProcessing: boolean;
		isFinished: boolean;
		hasError: boolean;
		hasFailed: boolean;
		isSuccessful: boolean;
	};
	activePaymentMethod: string;
	activeSavedToken: string;
	// Avilable payment methods are payment methods which have been validated and can make payment
	availablePaymentMethods: PlainPaymentMethods;
	availableExpressPaymentMethods: PlainExpressPaymentMethods;
	savedPaymentMethods:
		| Record< string, SavedPaymentMethod[] >
		| EmptyObjectType;
	paymentMethodData: Record< string, unknown >;
	paymentMethodsInitialized: boolean;
	expressPaymentMethodsInitialized: boolean;
	shouldSavePaymentMethod: boolean;
}
export const defaultPaymentMethodDataState: PaymentMethodDataState = {
	currentStatus: {
		isPristine: true,
		isStarted: false,
		isProcessing: false,
		isFinished: false,
		hasError: false,
		hasFailed: false,
		isSuccessful: false,
	},
	activePaymentMethod: '',
	activeSavedToken: '',
	availablePaymentMethods: {},
	availableExpressPaymentMethods: {},
	savedPaymentMethods: getSetting<
		Record< string, SavedPaymentMethod[] > | EmptyObjectType
	>( 'customerPaymentMethods', {} ),
	paymentMethodData: {},
	paymentMethodsInitialized: false,
	expressPaymentMethodsInitialized: false,
	shouldSavePaymentMethod: false,
};
