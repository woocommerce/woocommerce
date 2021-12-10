/**
 * External dependencies
 */
import {
	PaymentMethodConfiguration,
	PaymentMethods,
	ExpressPaymentMethods,
} from '@woocommerce/type-defs/payments';
import type {
	EmptyObjectType,
	ObjectType,
} from '@woocommerce/type-defs/objects';
/**
 * Internal dependencies
 */
import type { emitterCallback } from '../../../event-emit';
import { STATUS } from './constants';

export interface CustomerPaymentMethod {
	method: PaymentMethodConfiguration;
	expires: string;
	is_default: boolean;
	tokenId: number;
	actions: ObjectType;
}
export type CustomerPaymentMethods =
	| Record< string, CustomerPaymentMethod >
	| EmptyObjectType;

export interface PaymentMethodDispatchers {
	setRegisteredPaymentMethods: ( paymentMethods: PaymentMethods ) => void;
	setRegisteredExpressPaymentMethods: (
		paymentMethods: ExpressPaymentMethods
	) => void;
	setShouldSavePayment: ( shouldSave: boolean ) => void;
	setActivePaymentMethod: (
		paymentMethod: string,
		paymentMethodData?: ObjectType | EmptyObjectType
	) => void;
}

export interface PaymentStatusDispatchers {
	pristine: () => void;
	started: () => void;
	processing: () => void;
	completed: () => void;
	error: ( error: string ) => void;
	failed: (
		error?: string,
		paymentMethodData?: ObjectType | EmptyObjectType,
		billingData?: ObjectType | EmptyObjectType
	) => void;
	success: (
		paymentMethodData?: ObjectType | EmptyObjectType,
		billingData?: ObjectType | EmptyObjectType,
		shippingData?: ObjectType | EmptyObjectType
	) => void;
}

export interface PaymentMethodDataContextState {
	currentStatus: STATUS;
	shouldSavePaymentMethod: boolean;
	activePaymentMethod: string;
	paymentMethodData: ObjectType | EmptyObjectType;
	errorMessage: string;
	paymentMethods: PaymentMethods;
	expressPaymentMethods: ExpressPaymentMethods;
}

export type PaymentMethodCurrentStatusType = {
	// If true then the payment method state in checkout is pristine.
	isPristine: boolean;
	// If true then the payment method has been initialized and has started.
	isStarted: boolean;
	// If true then the payment method is processing payment.
	isProcessing: boolean;
	// If true then the payment method is in a finished state (which may mean it's status is either error, failed, or success).
	isFinished: boolean;
	// If true then the payment method is in an error state.
	hasError: boolean;
	// If true then the payment method has failed (usually indicates a problem with the payment method used, not logic error).
	hasFailed: boolean;
	// If true then the payment method has completed it's processing successfully.
	isSuccessful: boolean;
	// If true, an express payment is in progress.
	isDoingExpressPayment: boolean;
};

export type PaymentMethodDataContextType = {
	// Sets the payment status for the payment method.
	setPaymentStatus: () => PaymentStatusDispatchers;
	// The current payment status.
	currentStatus: PaymentMethodCurrentStatusType;
	// An object of payment status constants.
	paymentStatuses: ObjectType;
	// Arbitrary data to be passed along for processing by the payment method on the server.
	paymentMethodData: ObjectType | EmptyObjectType;
	// An error message provided by the payment method if there is an error.
	errorMessage: string;
	// The active payment method slug.
	activePaymentMethod: string;
	// Current active token.
	activeSavedToken: string;
	// A function for setting the active payment method.
	setActivePaymentMethod: PaymentMethodDispatchers[ 'setActivePaymentMethod' ];
	// Returns the customer payment for the customer if it exists.
	customerPaymentMethods:
		| Record< string, CustomerPaymentMethod >
		| EmptyObjectType;
	// Registered payment methods.
	paymentMethods: PaymentMethods;
	// Registered express payment methods.
	expressPaymentMethods: ExpressPaymentMethods;
	// True when all registered payment methods have been initialized.
	paymentMethodsInitialized: boolean;
	// True when all registered express payment methods have been initialized.
	expressPaymentMethodsInitialized: boolean;
	// Event registration callback for registering observers for the payment processing event.
	onPaymentProcessing: ReturnType< typeof emitterCallback >;
	// A function used by express payment methods to indicate an error for checkout to handle. It receives an error message string. Does not change payment status.
	setExpressPaymentError: ( error: string ) => void;
	// True if an express payment method is active.
	isExpressPaymentMethodActive: boolean;
	// A function used to set the shouldSavePayment value.
	setShouldSavePayment: PaymentMethodDispatchers[ 'setShouldSavePayment' ];
	// True means that the configured payment method option is saved for the customer.
	shouldSavePayment: boolean;
};

export type PaymentMethodsDispatcherType = (
	paymentMethods: PaymentMethods
) => undefined;
