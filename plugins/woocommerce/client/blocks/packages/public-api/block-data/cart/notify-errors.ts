/**
 * External dependencies
 */
import { ApiErrorResponse, isApiErrorResponse } from '@woocommerce/types';
import { createNotice } from '@woocommerce/base-utils';
import { decodeEntities } from '@wordpress/html-entities';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getNoticeContextFromErrorResponse } from '../utils/process-error-response';

/**
 * This function is used to normalize errors into an array of valid ApiErrorResponse objects.
 */
const filterValidErrors = ( errors: ApiErrorResponse[] ) => {
	return errors.filter( isApiErrorResponse );
};

/**
 * This function is used to notify the user of errors/conflicts from an API error response object.
 */
const createNoticesFromErrors = ( errors: ApiErrorResponse[] ) => {
	errors.forEach( ( error ) => {
		createNotice( 'error', decodeEntities( error.message ), {
			id: error.code,
			context: error?.data?.context || 'wc/cart',
		} );
	} );
};

/**
 * This function will dismiss a notice, finding it by its id and context.
 */
const dismissNoticeByOptions = ( options: { id: string; context: string } ) => {
	dispatch( 'core/notices' ).removeNotice( options.id, options.context );
};

/**
 * This function will remove old error notices and create new error notices for the cart based on the
 * passed error responses.
 */
export const updateCartErrorNotices = (
	errors: ApiErrorResponse[] | null = null,
	oldErrors: ApiErrorResponse[] | null = null
) => {
	if ( oldErrors !== null ) {
		const oldCartErrorContexts = oldErrors.flatMap(
			( e: ApiErrorResponse ) => getNoticeContextFromErrorResponse( e )
		);

		oldCartErrorContexts.forEach( ( e ) => {
			dismissNoticeByOptions( e );
		} );
	}
	if ( errors !== null ) {
		createNoticesFromErrors( filterValidErrors( errors ) );
	}
};
