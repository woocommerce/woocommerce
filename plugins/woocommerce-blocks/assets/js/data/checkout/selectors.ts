/**
 * Internal dependencies
 */
import { STATUS } from './constants';
import { CheckoutState } from './default-state';

export const getCustomerId = ( state: CheckoutState ) => {
	return state.customerId;
};

export const getOrderId = ( state: CheckoutState ) => {
	return state.orderId;
};

export const getOrderNotes = ( state: CheckoutState ) => {
	return state.orderNotes;
};

export const getRedirectUrl = ( state: CheckoutState ) => {
	return state.redirectUrl;
};

export const getUseShippingAsBilling = ( state: CheckoutState ) => {
	return state.useShippingAsBilling;
};

export const getExtensionData = ( state: CheckoutState ) => {
	return state.extensionData;
};

export const getShouldCreateAccount = ( state: CheckoutState ) => {
	return state.shouldCreateAccount;
};

export const getCheckoutStatus = ( state: CheckoutState ) => {
	return state.status;
};

export const hasError = ( state: CheckoutState ) => {
	return state.hasError;
};

export const hasOrder = ( state: CheckoutState ) => {
	return !! state.orderId;
};

export const isComplete = ( state: CheckoutState ) => {
	return state.status === STATUS.COMPLETE;
};

export const isIdle = ( state: CheckoutState ) => {
	return state.status === STATUS.IDLE;
};

export const isBeforeProcessing = ( state: CheckoutState ) => {
	return state.status === STATUS.BEFORE_PROCESSING;
};

export const isAfterProcessing = ( state: CheckoutState ) => {
	return state.status === STATUS.AFTER_PROCESSING;
};

export const isProcessing = ( state: CheckoutState ) => {
	return state.status === STATUS.PROCESSING;
};

export const isCalculating = ( state: CheckoutState ) => {
	return state.calculatingCount > 0;
};
