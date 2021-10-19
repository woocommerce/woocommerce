/**
 * Internal dependencies
 */
import type { PaymentResultDataType, CheckoutStateContextState } from './types';

export enum ACTION {
	SET_IDLE = 'set_idle',
	SET_PRISTINE = 'set_pristine',
	SET_REDIRECT_URL = 'set_redirect_url',
	SET_COMPLETE = 'set_checkout_complete',
	SET_BEFORE_PROCESSING = 'set_before_processing',
	SET_AFTER_PROCESSING = 'set_after_processing',
	SET_PROCESSING_RESPONSE = 'set_processing_response',
	SET_PROCESSING = 'set_checkout_is_processing',
	SET_HAS_ERROR = 'set_checkout_has_error',
	SET_NO_ERROR = 'set_checkout_no_error',
	SET_CUSTOMER_ID = 'set_checkout_customer_id',
	SET_ORDER_ID = 'set_checkout_order_id',
	SET_ORDER_NOTES = 'set_checkout_order_notes',
	INCREMENT_CALCULATING = 'increment_calculating',
	DECREMENT_CALCULATING = 'decrement_calculating',
	SET_SHOULD_CREATE_ACCOUNT = 'set_should_create_account',
	SET_EXTENSION_DATA = 'set_extension_data',
}

export interface ActionType extends Partial< CheckoutStateContextState > {
	type: ACTION;
	data?:
		| Record< string, unknown >
		| Record< string, never >
		| PaymentResultDataType;
}

/**
 * All the actions that can be dispatched for the checkout.
 */
export const actions = {
	setPristine: () =>
		( {
			type: ACTION.SET_PRISTINE,
		} as const ),
	setIdle: () =>
		( {
			type: ACTION.SET_IDLE,
		} as const ),
	setProcessing: () =>
		( {
			type: ACTION.SET_PROCESSING,
		} as const ),
	setRedirectUrl: ( redirectUrl: string ) =>
		( {
			type: ACTION.SET_REDIRECT_URL,
			redirectUrl,
		} as const ),
	setProcessingResponse: ( data: PaymentResultDataType ) =>
		( {
			type: ACTION.SET_PROCESSING_RESPONSE,
			data,
		} as const ),
	setComplete: ( data: Record< string, unknown > = {} ) =>
		( {
			type: ACTION.SET_COMPLETE,
			data,
		} as const ),
	setBeforeProcessing: () =>
		( {
			type: ACTION.SET_BEFORE_PROCESSING,
		} as const ),
	setAfterProcessing: () =>
		( {
			type: ACTION.SET_AFTER_PROCESSING,
		} as const ),
	setHasError: ( hasError = true ) =>
		( {
			type: hasError ? ACTION.SET_HAS_ERROR : ACTION.SET_NO_ERROR,
		} as const ),
	incrementCalculating: () =>
		( {
			type: ACTION.INCREMENT_CALCULATING,
		} as const ),
	decrementCalculating: () =>
		( {
			type: ACTION.DECREMENT_CALCULATING,
		} as const ),
	setCustomerId: ( customerId: number ) =>
		( {
			type: ACTION.SET_CUSTOMER_ID,
			customerId,
		} as const ),
	setOrderId: ( orderId: number ) =>
		( {
			type: ACTION.SET_ORDER_ID,
			orderId,
		} as const ),
	setShouldCreateAccount: ( shouldCreateAccount: boolean ) =>
		( {
			type: ACTION.SET_SHOULD_CREATE_ACCOUNT,
			shouldCreateAccount,
		} as const ),
	setOrderNotes: ( orderNotes: string ) =>
		( {
			type: ACTION.SET_ORDER_NOTES,
			orderNotes,
		} as const ),
	setExtensionData: (
		extensionData: Record< string, Record< string, unknown > >
	) =>
		( {
			type: ACTION.SET_EXTENSION_DATA,
			extensionData,
		} as const ),
};
