/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';

export function createNoticesFromResponse( response ) {
	const { createNotice } = dispatch( 'core/notices' );

	if (
		response.error_data &&
		response.errors &&
		Object.keys( response.errors ).length
	) {
		// Loop over multi-error responses.
		Object.keys( response.errors ).forEach( ( errorKey ) => {
			createNotice( 'error', response.errors[ errorKey ].join( ' ' ) );
		} );
	} else if ( response.message ) {
		// Handle generic messages.
		createNotice( response.code ? 'error' : 'success', response.message );
	}
}
