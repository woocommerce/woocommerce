/** @format */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { NAMESPACE, SWAGGERNAMESPACE } from 'store/constants';

export default {
	async getReportItems( ...args ) {
		const [ endpoint, query ] = args.slice( -2 );

		const swaggerEndpoints = [ 'categories', 'coupons', 'taxes', 'variations' ];
		if ( swaggerEndpoints.indexOf( endpoint ) >= 0 ) {
			try {
				const response = await fetch(
					SWAGGERNAMESPACE + 'reports/' + endpoint + stringifyQuery( query )
				);
				const items = await response.json();

				dispatch( 'wc-admin' ).setReportItems( endpoint, items, query );
			} catch ( error ) {
				dispatch( 'wc-admin' ).setReportItemsError( endpoint, query );
			}

			return;
		}

		try {
			const items = await apiFetch( {
				path: NAMESPACE + 'reports/' + endpoint + stringifyQuery( query ),
			} );

			dispatch( 'wc-admin' ).setReportItems( endpoint, items, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setReportItemsError( endpoint, query );
		}
	},
};
