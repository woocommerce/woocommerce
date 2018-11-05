/** @format */
/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { NAMESPACE } from 'store/constants';

export default {
	async getNotes( state, query ) {
		try {
			const notes = await apiFetch( { path: NAMESPACE + 'admin/notes' + stringifyQuery( query ) } );
			dispatch( 'wc-admin' ).setNotes( notes, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setNotesError( query );
		}
	},
};
