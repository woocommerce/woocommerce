/**
 * External dependencies
 */
import {
	createNotice,
	createNoticeIfVisible,
	DEFAULT_ERROR_MESSAGE,
} from '@woocommerce/base-utils';
import { decodeEntities } from '@wordpress/html-entities';
import { isObject, objectHasProp, ApiErrorResponse } from '@woocommerce/types';
import { noticeContexts } from '@woocommerce/base-context/event-emit/utils';

type ApiParamError = {
	param: string;
	id: string;
	code: string;
	message: string;
};

const isApiResponse = ( response: unknown ): response is ApiErrorResponse => {
	return (
		isObject( response ) &&
		objectHasProp( response, 'code' ) &&
		objectHasProp( response, 'message' ) &&
		objectHasProp( response, 'data' )
	);
};

/**
 * Flattens error details which are returned from the API when multiple params are not valid.
 *
 * - Codes will be prefixed with the param. For example, `invalid_email` becomes `billing_address_invalid_email`.
 * - Additional error messages will be flattened alongside the main error message.
 * - Supports 1 level of nesting.
 * - Decodes HTML entities in error messages.
 */
const getErrorDetails = ( response: ApiErrorResponse ): ApiParamError[] => {
	const errorDetails = objectHasProp( response.data, 'details' )
		? Object.entries( response.data.details )
		: null;

	if ( ! errorDetails ) {
		return [];
	}

	return errorDetails.reduce(
		(
			acc,
			[
				param,
				{ code, message, additional_errors: additionalErrors = [] },
			]
		) => {
			return [
				...acc,
				{
					param,
					id: `${ param }_${ code }`,
					code,
					message: decodeEntities( message ),
				},
				...( Array.isArray( additionalErrors )
					? additionalErrors.flatMap( ( additionalError ) => {
							if (
								! objectHasProp( additionalError, 'code' ) ||
								! objectHasProp( additionalError, 'message' )
							) {
								return [];
							}
							return [
								{
									param,
									id: `${ param }_${ additionalError.code }`,
									code: additionalError.code,
									message: decodeEntities(
										additionalError.message
									),
								},
							];
					  } )
					: [] ),
			];
		},
		[] as ApiParamError[]
	);
};

/**
 * Processes the response for an invalid param error, with response code rest_invalid_param.
 */
const processInvalidParamResponse = ( response: ApiErrorResponse ) => {
	const errorDetails = getErrorDetails( response );

	errorDetails.forEach( ( { code, message, id, param } ) => {
		switch ( code ) {
			case 'invalid_email':
				createNotice( 'error', message, {
					id,
					context: noticeContexts.CONTACT_INFORMATION,
				} );
				return;
		}
		switch ( param ) {
			case 'billing_address':
				createNoticeIfVisible( 'error', message, {
					id,
					context: noticeContexts.BILLING_ADDRESS,
				} );
				break;
			case 'shipping_address':
				createNoticeIfVisible( 'error', message, {
					id,
					context: noticeContexts.SHIPPING_ADDRESS,
				} );
				break;
		}
	} );
};

/**
 * Takes an API response object and creates error notices to display to the customer.
 *
 * This is where we can handle specific error codes and display notices in specific contexts.
 */
const processErrorResponse = ( response: ApiErrorResponse ) => {
	if ( ! isApiResponse( response ) ) {
		return;
	}
	switch ( response.code ) {
		case 'woocommerce_rest_missing_email_address':
		case 'woocommerce_rest_invalid_email_address':
			createNotice( 'error', response.message, {
				id: response.code,
				context: noticeContexts.CONTACT_INFORMATION,
			} );
			break;
		case 'rest_invalid_param':
			processInvalidParamResponse( response );
			break;
		default:
			createNotice( 'error', response.message || DEFAULT_ERROR_MESSAGE, {
				id: response.code,
				context: noticeContexts.CHECKOUT,
			} );
	}
};

export default processErrorResponse;
