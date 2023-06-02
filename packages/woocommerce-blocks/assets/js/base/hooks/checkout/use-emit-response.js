/**
 * @typedef {import('@woocommerce/type-defs/hooks').EmitResponseTypes} EmitResponseTypes
 * @typedef {import('@woocommerce/type-defs/hooks').NoticeContexts} NoticeContexts
 * @typedef {import('@woocommerce/type-defs/hooks').EmitResponseApi} EmitResponseApi
 */

const isResponseOf = ( response, type ) => {
	return !! response.type && response.type === type;
};

/**
 * @type {EmitResponseTypes}
 */
const responseTypes = {
	SUCCESS: 'success',
	FAIL: 'failure',
	ERROR: 'error',
};

/**
 * @type {NoticeContexts}
 */
const noticeContexts = {
	PAYMENTS: 'wc/payment-area',
	EXPRESS_PAYMENTS: 'wc/express-payment-area',
};

const isSuccessResponse = ( response ) => {
	return isResponseOf( response, responseTypes.SUCCESS );
};

const isErrorResponse = ( response ) => {
	return isResponseOf( response, responseTypes.ERROR );
};

const isFailResponse = ( response ) => {
	return isResponseOf( response, responseTypes.FAIL );
};

const shouldRetry = ( response ) => {
	return typeof response.retry === 'undefined' || response.retry === true;
};

/**
 * A custom hook exposing response utilities for emitters.
 *
 * @return {EmitResponseApi} Various interfaces for validating and implementing
 *                           emitter response properties.
 */
export const useEmitResponse = () => {
	return {
		responseTypes,
		noticeContexts,
		shouldRetry,
		isSuccessResponse,
		isErrorResponse,
		isFailResponse,
	};
};
