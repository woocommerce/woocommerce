/**
 * External dependencies
 */
import { PaymentResult } from '@woocommerce/types';
/**
 * Internal dependencies
 */
import { STATUS } from './constants';
import type { emitterCallback } from '../../../event-emit';

export interface CheckoutResponseError {
	code: string;
	message: string;
	data: {
		status: number;
	};
}

export interface CheckoutResponseSuccess {
	// eslint-disable-next-line camelcase
	payment_result: {
		// eslint-disable-next-line camelcase
		payment_status: 'success' | 'failure' | 'pending' | 'error';
		// eslint-disable-next-line camelcase
		payment_details: Record< string, string > | Record< string, never >;
		// eslint-disable-next-line camelcase
		redirect_url: string;
	};
}

export type CheckoutResponse = CheckoutResponseSuccess | CheckoutResponseError;

type extensionDataNamespace = string;
type extensionDataItem = Record< string, unknown >;
export type extensionData = Record< extensionDataNamespace, extensionDataItem >;

export interface CheckoutStateContextState {
	redirectUrl: string;
	status: STATUS;
	hasError: boolean;
	calculatingCount: number;
	orderId: number;
	orderNotes: string;
	customerId: number;
	useShippingAsBilling: boolean;
	shouldCreateAccount: boolean;
	processingResponse: PaymentResult | null;
	extensionData: extensionData;
}

export type CheckoutStateDispatchActions = {
	resetCheckout: () => void;
	setRedirectUrl: ( url: string ) => void;
	setHasError: ( hasError: boolean ) => void;
	setAfterProcessing: ( response: CheckoutResponse ) => void;
	incrementCalculating: () => void;
	decrementCalculating: () => void;
	setCustomerId: ( id: number ) => void;
	setOrderId: ( id: number ) => void;
	setOrderNotes: ( orderNotes: string ) => void;
	setExtensionData: ( extensionData: extensionData ) => void;
};

export type CheckoutStateContextType = {
	// Submits the checkout and begins processing.
	onSubmit: () => void;
	// Used to register a callback that will fire after checkout has been processed and there are no errors.
	onCheckoutAfterProcessingWithSuccess: ReturnType< typeof emitterCallback >;
	// Used to register a callback that will fire when the checkout has been processed and has an error.
	onCheckoutAfterProcessingWithError: ReturnType< typeof emitterCallback >;
	// Deprecated in favour of onCheckoutValidationBeforeProcessing.
	onCheckoutBeforeProcessing: ReturnType< typeof emitterCallback >;
	// Used to register a callback that will fire when the checkout has been submitted before being sent off to the server.
	onCheckoutValidationBeforeProcessing: ReturnType< typeof emitterCallback >;
};
