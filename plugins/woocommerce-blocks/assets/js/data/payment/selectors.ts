/**
 * External dependencies
 */
import { objectHasProp } from '@woocommerce/types';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { PaymentMethodDataState } from './default-state';
import { filterActiveSavedPaymentMethods } from './utils';
import { STATUS as PAYMENT_STATUS } from './constants';

export const isPaymentPristine = ( state: PaymentMethodDataState ) =>
	state.status === PAYMENT_STATUS.PRISTINE;

export const isPaymentStarted = ( state: PaymentMethodDataState ) =>
	state.status === PAYMENT_STATUS.STARTED;

export const isPaymentProcessing = ( state: PaymentMethodDataState ) =>
	state.status === PAYMENT_STATUS.PROCESSING;

export const isPaymentSuccess = ( state: PaymentMethodDataState ) =>
	state.status === PAYMENT_STATUS.SUCCESS;

export const hasPaymentError = ( state: PaymentMethodDataState ) =>
	state.status === PAYMENT_STATUS.ERROR;

export const isPaymentFailed = ( state: PaymentMethodDataState ) =>
	state.status === PAYMENT_STATUS.FAILED;

export const isPaymentFinished = ( state: PaymentMethodDataState ) => {
	return (
		state.status === PAYMENT_STATUS.SUCCESS ||
		state.status === PAYMENT_STATUS.ERROR ||
		state.status === PAYMENT_STATUS.FAILED
	);
};

export const isExpressPaymentMethodActive = (
	state: PaymentMethodDataState
) => {
	return Object.keys( state.availableExpressPaymentMethods ).includes(
		state.activePaymentMethod
	);
};

export const getActiveSavedToken = ( state: PaymentMethodDataState ) => {
	return typeof state.paymentMethodData === 'object' &&
		objectHasProp( state.paymentMethodData, 'token' )
		? state.paymentMethodData.token + ''
		: '';
};

export const getActivePaymentMethod = ( state: PaymentMethodDataState ) => {
	return state.activePaymentMethod;
};

export const getAvailablePaymentMethods = ( state: PaymentMethodDataState ) => {
	return state.availablePaymentMethods;
};

export const getAvailableExpressPaymentMethods = (
	state: PaymentMethodDataState
) => {
	return state.availableExpressPaymentMethods;
};

export const getPaymentMethodData = ( state: PaymentMethodDataState ) => {
	return state.paymentMethodData;
};

export const getSavedPaymentMethods = ( state: PaymentMethodDataState ) => {
	return state.savedPaymentMethods;
};

/**
 * Filters the list of saved payment methods and returns only the ones which
 * are active and supported by the payment gateway
 */
export const getActiveSavedPaymentMethods = (
	state: PaymentMethodDataState
) => {
	const availablePaymentMethodKeys = Object.keys(
		state.availablePaymentMethods
	);

	return filterActiveSavedPaymentMethods(
		availablePaymentMethodKeys,
		state.savedPaymentMethods
	);
};

export const paymentMethodsInitialized = ( state: PaymentMethodDataState ) => {
	return state.paymentMethodsInitialized;
};

export const expressPaymentMethodsInitialized = (
	state: PaymentMethodDataState
) => {
	return state.expressPaymentMethodsInitialized;
};

/**
 * @deprecated - use these selectors instead: isPaymentPristine, isPaymentStarted, isPaymentProcessing,
 * isPaymentFinished, hasPaymentError, isPaymentSuccess, isPaymentFailed
 */
export const getCurrentStatus = ( state: PaymentMethodDataState ) => {
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

export const getShouldSavePaymentMethod = ( state: PaymentMethodDataState ) => {
	return state.shouldSavePaymentMethod;
};

export const getState = ( state: PaymentMethodDataState ) => {
	return state;
};
