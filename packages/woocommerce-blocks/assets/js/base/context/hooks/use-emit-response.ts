/**
 * External dependencies
 */
import { isObject } from '@woocommerce/types';

export enum responseTypes {
	SUCCESS = 'success',
	FAIL = 'failure',
	ERROR = 'error',
}

export enum noticeContexts {
	PAYMENTS = 'wc/payment-area',
	EXPRESS_PAYMENTS = 'wc/express-payment-area',
}

export interface ResponseType extends Record< string, unknown > {
	type: responseTypes;
	retry?: boolean;
}

const isResponseOf = (
	response: unknown,
	type: string
): response is ResponseType => {
	return isObject( response ) && 'type' in response && response.type === type;
};

export const isSuccessResponse = (
	response: unknown
): response is ResponseType => {
	return isResponseOf( response, responseTypes.SUCCESS );
};

export const isErrorResponse = (
	response: unknown
): response is ResponseType => {
	return isResponseOf( response, responseTypes.ERROR );
};

export const isFailResponse = (
	response: unknown
): response is ResponseType => {
	return isResponseOf( response, responseTypes.FAIL );
};

export const shouldRetry = ( response: unknown ): boolean => {
	return (
		! isObject( response ) ||
		typeof response.retry === 'undefined' ||
		response.retry === true
	);
};

/**
 * A custom hook exposing response utilities for emitters.
 */
export const useEmitResponse = () =>
	( {
		responseTypes,
		noticeContexts,
		shouldRetry,
		isSuccessResponse,
		isErrorResponse,
		isFailResponse,
	} as const );
