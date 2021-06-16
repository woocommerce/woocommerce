/**
 * External dependencies
 */
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import type { emitterCallback } from '../../../event-emit';
import { STATUS } from './constants';

type ObjectType = Record< string, unknown >;
type EmptyObjectType = Record< string, never >;

export interface PaymentMethodConfig {
	// A unique string to identify the payment method client side.
	name: string;
	// A react node for your payment method UI.
	content: ReactNode;
	// A react node to display a preview of your payment method in the editor.
	edit: ReactNode;
	// A callback to determine whether the payment method should be shown in the checkout.
	canMakePayment: (
		cartData: ObjectType
	) => Promise< boolean | { error: { message: string } } >;
	// A unique string to represent the payment method server side. If not provided, defaults to name.
	paymentMethodId?: string;
	// Object that describes various features provided by the payment method.
	supports: ObjectType;
	// Array of card types (brands) supported by the payment method. (See stripe/credit-card for example.)
	icons: ObjectType;
	// A react node that will be used as a label for the payment method in the checkout.
	label: ReactNode;
	// An accessibility label. Screen readers will output this label when the payment method is selected.
	ariaLabel: string;
	// Optionally customize the label text for the checkout submit (`Place Order`) button.
	placeOrderButtonLabel?: string;
}

export type ExpressPaymentMethodConfig = Omit<
	PaymentMethodConfig,
	'icons' | 'label' | 'ariaLabel' | 'placeOrderButtonLabel'
>;

export type PaymentMethods =
	| Record< string, PaymentMethodConfig >
	| EmptyObjectType;

export type ExpressPaymentMethods =
	| Record< string, ExpressPaymentMethodConfig >
	| EmptyObjectType;

export interface CustomerPaymentMethod {
	method: PaymentMethodConfig;
	expires: string;
	// eslint-disable-next-line camelcase
	is_default: boolean;
	tokenId: number;
	actions: ObjectType;
}
export type CustomerPaymentMethods =
	| Record< string, CustomerPaymentMethod >
	| EmptyObjectType;

export type PaymentMethodDispatchers = {
	setRegisteredPaymentMethods: ( paymentMethods: PaymentMethods ) => void;
	setRegisteredExpressPaymentMethods: (
		paymentMethods: ExpressPaymentMethods
	) => void;
	setShouldSavePayment: ( shouldSave: boolean ) => void;
};

export interface PaymentStatusDispatchers {
	pristine: () => void;
	started: ( paymentMethodData?: ObjectType | EmptyObjectType ) => void;
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
	paymentMethodData: ObjectType | EmptyObjectType;
	hasSavedToken: boolean;
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
	// A function for setting the active payment method.
	setActivePaymentMethod: ( paymentMethod: string ) => void;
	// Current active token.
	activeSavedToken: string;
	// A function for setting the active payment method token.
	setActiveSavedToken: ( activeSavedToken: string ) => void;
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
	setShouldSavePayment: ( shouldSavePayment: boolean ) => void;
	// True means that the configured payment method option is saved for the customer.
	shouldSavePayment: boolean;
};
