/**
 * External dependencies
 */
import {
	PlainPaymentMethods,
	PlainExpressPaymentMethods,
} from '@woocommerce/type-defs/payments';
import type {
	EmptyObjectType,
	ObjectType,
} from '@woocommerce/type-defs/objects';
import { DataRegistry } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { EventObserversType } from '../../base/context/event-emit';
import type { DispatchFromMap } from '../mapped-types';
import * as actions from './actions';
import { FieldValidationStatus } from '../types';

export interface CustomerPaymentMethodConfiguration {
	gateway: string;
	brand: string;
	last4: string;
}
export interface SavedPaymentMethod {
	method: CustomerPaymentMethodConfiguration;
	expires: string;
	is_default: boolean;
	tokenId: number;
	actions: ObjectType;
}
export type SavedPaymentMethods =
	| Record< string, SavedPaymentMethod[] >
	| EmptyObjectType;

export interface PaymentMethodDispatchers {
	setRegisteredPaymentMethods: (
		paymentMethods: PlainPaymentMethods
	) => void;
	setRegisteredExpressPaymentMethods: (
		paymentMethods: PlainExpressPaymentMethods
	) => void;
	setActivePaymentMethod: (
		paymentMethod: string,
		paymentMethodData?: ObjectType | EmptyObjectType
	) => void;
}

export interface PaymentStatusDispatchers {
	pristine: () => void;
	started: () => void;
	processing: () => void;
	error: ( error: string ) => void;
	failed: (
		error?: string,
		paymentMethodData?: ObjectType | EmptyObjectType,
		billingAddress?: ObjectType | EmptyObjectType
	) => void;
	success: (
		paymentMethodData?: ObjectType | EmptyObjectType,
		billingAddress?: ObjectType | EmptyObjectType,
		shippingData?: ObjectType | EmptyObjectType
	) => void;
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

export type PaymentMethodsDispatcherType = (
	paymentMethods: PlainPaymentMethods
) => undefined | void;

/**
 * Type for emitProcessingEventType() thunk
 */
export type emitProcessingEventType = (
	observers: EventObserversType,
	setValidationErrors: (
		errors: Record< string, FieldValidationStatus >
	) => void
) => ( {
	dispatch,
	registry,
}: {
	dispatch: DispatchFromMap< typeof actions >;
	registry: DataRegistry;
} ) => void;

export interface PaymentStatus {
	isPristine?: boolean;
	isStarted?: boolean;
	isProcessing?: boolean;
	isFinished?: boolean;
	hasError?: boolean;
	hasFailed?: boolean;
	isSuccessful?: boolean;
	isDoingExpressPayment?: boolean;
}
