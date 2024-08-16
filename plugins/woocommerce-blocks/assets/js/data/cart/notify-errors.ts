/**
 * External dependencies
 */
import { ApiErrorResponse, isApiErrorResponse } from '@woocommerce/types';
import { createNotice } from '@woocommerce/base-utils';
import { decodeEntities } from '@wordpress/html-entities';
import { dispatch } from '@wordpress/data';

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
 * This function is used to dismiss old errors from the store.
 */
const dismissNoticesFromErrors = ( errors: ApiErrorResponse[] ) => {
	errors.forEach( ( error ) => {
		dispatch( 'core/notices' ).removeNotice(
			error.code,
			error?.data?.context || 'wc/cart'
		);
	} );
};

/**
 * This function is used to notify the user of cart errors/conflicts.
 */
export const notifyCartErrors = (
	errors: ApiErrorResponse[] | null = null,
	oldErrors: ApiErrorResponse[] | null = null
) => {
	if ( oldErrors !== null ) {
		dismissNoticesFromErrors( filterValidErrors( oldErrors ) );
	}
	if ( errors !== null ) {
		createNoticesFromErrors( filterValidErrors( errors ) );
	}
};
