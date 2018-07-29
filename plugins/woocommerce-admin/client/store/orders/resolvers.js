/** @format */
/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import apiRequest from '@wordpress/api-request';

export default {
	async getOrders() {
		try {
			const orders = await apiRequest( { path: '/wc/v3/orders' } );
			dispatch( 'wc-admin' ).setOrders( orders );
		} catch ( error ) {
			if ( error && error.responseJSON ) {
				alert( error.responseJSON.message );
			} else {
				alert( error );
			}
		}
	},
};
