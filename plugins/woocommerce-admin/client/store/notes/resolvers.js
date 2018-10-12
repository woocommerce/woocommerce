/** @format */
/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

export default {
	async getNotes() {
		try {
			const notes = await apiFetch( { path: '/wc/v3/admin/notes' } );
			dispatch( 'wc-admin' ).setNotes( notes );
		} catch ( error ) {
			if ( error && error.responseJSON ) {
				alert( error.responseJSON.message );
			} else {
				alert( error );
			}
		}
	},
};
