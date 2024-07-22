/**
 * External dependencies
 */
import { ApiErrorResponse, isApiErrorResponse } from '@woocommerce/types';
import { createNotice } from '@woocommerce/base-utils';
import { decodeEntities } from '@wordpress/html-entities';
import { dispatch } from '@wordpress/data';

/**
 * This function is used to notify the user of errors/conflicts from an API error response object.
 */
const createNotices = ( errors: ApiErrorResponse[] ) => {
	errors.forEach( ( error ) => {
		createNotice( 'error', decodeEntities( error.message ), {
			id: error.code,
			context: error?.data?.context || 'wc/cart',
		} );
	} );
};

/**
 * This function is used to dismiss old errors from the store.
 */
const dismissNotices = ( oldErrors: ApiErrorResponse[] ) => {
	oldErrors.forEach( ( error ) => {
		dispatch( 'core/notices' ).removeNotice(
			error.code,
			error?.data?.context || 'wc/cart'
		);
	} );
};

/**
 * This function is used to normalize errors into an array of ApiErrorResponse objects.
 */
const normalizeErrors = ( errors: ApiErrorResponse | ApiErrorResponse[] ) => {
	return isApiErrorResponse( errors )
		? [ errors ]
		: errors.filter( isApiErrorResponse );
};

/**
 * This function is used to notify the user of cart errors/conflicts.
 */
export const notifyErrors = (
	errors: ApiErrorResponse | ApiErrorResponse[] | null = null,
	oldErrors: ApiErrorResponse | ApiErrorResponse[] | null = null
) => {
	if ( oldErrors !== null ) {
		dismissNotices( normalizeErrors( oldErrors ) );
	}
	if ( errors !== null ) {
		createNotices( normalizeErrors( errors ) );
	}
};
