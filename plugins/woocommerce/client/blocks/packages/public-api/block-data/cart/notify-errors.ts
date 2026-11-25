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

type CartErrorNotice = {
	message: string;
	options: { id: string; context: string };
};

/**
 * Gets a notice ID for each cart error. Errors often share a code (e.g. every wc_add_notice() error is
 * `woocommerce_rest_cart_item_error`), and notices with the same ID replace each other. So the first
 * error keeps the code as its ID, later ones with a different message get `code__2`, `code__3`, and
 * exact repeats reuse the earlier ID.
 */
const getCartErrorNotices = (
	errors: ApiErrorResponse[]
): CartErrorNotice[] => {
	const messagesByCode = new Map< string, string[] >();

	return errors.map( ( error ) => {
		const context = error?.data?.context || 'wc/cart';
		const message = decodeEntities( error.message );
		const key = `${ context }::${ error.code }`;
		const messages = messagesByCode.get( key ) || [];

		let index = messages.indexOf( message );
		if ( index === -1 ) {
			messages.push( message );
			messagesByCode.set( key, messages );
			index = messages.length - 1;
		}

		return {
			message,
			options: {
				id:
					index === 0
						? error.code
						: `${ error.code }__${ index + 1 }`,
				context,
			},
		};
	} );
};

/**
 * This function is used to notify the user of errors/conflicts from an API error response object.
 */
const createNoticesFromErrors = ( errors: ApiErrorResponse[] ) => {
	getCartErrorNotices( errors ).forEach( ( { message, options } ) => {
		createNotice( 'error', message, options );
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

		// Also dismiss the numbered notices created for errors that share a code.
		getCartErrorNotices( filterValidErrors( oldErrors ) ).forEach(
			( { options } ) => {
				dismissNoticeByOptions( options );
			}
		);
	}
	if ( errors !== null ) {
		createNoticesFromErrors( filterValidErrors( errors ) );
	}
};
