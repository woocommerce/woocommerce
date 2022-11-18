/**
 * External dependencies
 */
import { objectHasProp } from '@woocommerce/types';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { PaymentState } from './default-state';
import { filterActiveSavedPaymentMethods } from './utils/filter-active-saved-payment-methods';
import { STATUS as PAYMENT_STATUS } from './constants';

export const isPaymentPristine = ( state: PaymentState ) =>
	state.status === PAYMENT_STATUS.PRISTINE;

export const isPaymentStarted = ( state: PaymentState ) =>
	state.status === PAYMENT_STATUS.STARTED;

export const isPaymentProcessing = ( state: PaymentState ) =>
	state.status === PAYMENT_STATUS.PROCESSING;

export const isPaymentSuccess = ( state: PaymentState ) =>
	state.status === PAYMENT_STATUS.SUCCESS;

export const hasPaymentError = ( state: PaymentState ) =>
	state.status === PAYMENT_STATUS.ERROR;

export const isPaymentFailed = ( state: PaymentState ) =>
	state.status === PAYMENT_STATUS.FAILED;

export const isPaymentFinished = ( state: PaymentState ) => {
	return (
		state.status === PAYMENT_STATUS.SUCCESS ||
		state.status === PAYMENT_STATUS.ERROR ||
		state.status === PAYMENT_STATUS.FAILED
	);
};

export const isExpressPaymentMethodActive = ( state: PaymentState ) => {
	return Object.keys( state.availableExpressPaymentMethods ).includes(
		state.activePaymentMethod
	);
};

export const getActiveSavedToken = ( state: PaymentState ) => {
	return typeof state.paymentMethodData === 'object' &&
		objectHasProp( state.paymentMethodData, 'token' )
		? state.paymentMethodData.token + ''
		: '';
};

export const getActivePaymentMethod = ( state: PaymentState ) => {
	return state.activePaymentMethod;
};

export const getAvailablePaymentMethods = ( state: PaymentState ) => {
	return state.availablePaymentMethods;
};

export const getAvailableExpressPaymentMethods = ( state: PaymentState ) => {
	return state.availableExpressPaymentMethods;
};

export const getPaymentMethodData = ( state: PaymentState ) => {
	return state.paymentMethodData;
};

export const getSavedPaymentMethods = ( state: PaymentState ) => {
	return state.savedPaymentMethods;
};

/**
 * Filters the list of saved payment methods and returns only the ones which
 * are active and supported by the payment gateway
 */
export const getActiveSavedPaymentMethods = ( state: PaymentState ) => {
	const availablePaymentMethodKeys = Object.keys(
		state.availablePaymentMethods
	);

	return filterActiveSavedPaymentMethods(
		availablePaymentMethodKeys,
		state.savedPaymentMethods
	);
};

export const paymentMethodsInitialized = ( state: PaymentState ) => {
	return state.paymentMethodsInitialized;
};

export const expressPaymentMethodsInitialized = ( state: PaymentState ) => {
	return state.expressPaymentMethodsInitialized;
};

/**
 * @deprecated - use these selectors instead: isPaymentPristine, isPaymentStarted, isPaymentProcessing,
 * isPaymentFinished, hasPaymentError, isPaymentSuccess, isPaymentFailed
 */
export const getCurrentStatus = ( state: PaymentState ) => {
	deprecated( 'getCurrentStatus', {
		since: '8.9.0',
		alternative:
			'isPaymentPristine, isPaymentStarted, isPaymentProcessing, isPaymentFinished, hasPaymentError, isPaymentSuccess, isPaymentFailed',
		plugin: 'WooCommerce Blocks',
		link: 'https://github.com/woocommerce/woocommerce-blocks/pull/7666',
	} );

	return {
		isPristine: isPaymentPristine( state ),
		isStarted: isPaymentStarted( state ),
		isProcessing: isPaymentProcessing( state ),
		isFinished: isPaymentFinished( state ),
		hasError: hasPaymentError( state ),
		hasFailed: isPaymentFailed( state ),
		isSuccessful: isPaymentSuccess( state ),
		isDoingExpressPayment: isExpressPaymentMethodActive( state ),
	};
};

export const getShouldSavePaymentMethod = ( state: PaymentState ) => {
	return state.shouldSavePaymentMethod;
};

export const getPaymentResult = ( state: PaymentState ) => {
	return state.paymentResult;
};

export const getState = ( state: PaymentState ) => {
	return state;
};
