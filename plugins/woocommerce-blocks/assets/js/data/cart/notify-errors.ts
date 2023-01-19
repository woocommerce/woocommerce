/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ApiErrorResponse, isApiErrorResponse } from '@woocommerce/types';
import { createNotice, DEFAULT_ERROR_MESSAGE } from '@woocommerce/base-utils';
import { decodeEntities } from '@wordpress/html-entities';
import { dispatch } from '@wordpress/data';

/**
 * This function is used to notify the user of cart errors.
 */
export const notifyErrors = ( error: ApiErrorResponse | null = null ) => {
	if ( error === null || ! isApiErrorResponse( error ) ) {
		return;
	}

	let errorMessage = error.message || DEFAULT_ERROR_MESSAGE;

	// Replace the generic invalid JSON message with something more user friendly.
	if ( error.code === 'invalid_json' ) {
		errorMessage = __(
			'Something went wrong. Please contact us for assistance.',
			'woo-gutenberg-products-block'
		);
	}

	// Create a new notice with a consistent error ID.
	createNotice( 'error', errorMessage, {
		id: 'woocommerce_cart_data_request_error',
		context: 'wc/cart',
		isDismissible: true,
	} );
};

/**
 * This function is used to notify the user of cart item errors/conflicts
 */
export const notifyCartErrors = (
	errors: ApiErrorResponse[] | null = null,
	oldErrors: ApiErrorResponse[] | null = null
) => {
	if ( oldErrors ) {
		oldErrors.forEach( ( error ) => {
			dispatch( 'core/notices' ).removeNotice( error.code, 'wc/cart' );
		} );
	}

	if ( errors !== null ) {
		errors.forEach( ( error ) => {
			if ( isApiErrorResponse( error ) ) {
				createNotice( 'error', decodeEntities( error.message ), {
					id: error.code,
					context: 'wc/cart',
					isDismissible: true,
				} );
			}
		} );
	}
};
