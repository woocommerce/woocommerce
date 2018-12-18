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

		const swaggerEndpoints = [ 'categories', 'coupons' ];
		if ( swaggerEndpoints.indexOf( endpoint ) >= 0 ) {
			try {
				const response = await fetch(
					SWAGGERNAMESPACE + 'reports/' + endpoint + stringifyQuery( query )
				);
				const itemsData = await response.json();

				dispatch( 'wc-admin' ).setReportItems( endpoint, query, itemsData );
			} catch ( error ) {
				dispatch( 'wc-admin' ).setReportItemsError( endpoint, query );
			}

			return;
		}

		try {
			const response = await apiFetch( {
				parse: false,
				path: NAMESPACE + 'reports/' + endpoint + stringifyQuery( query ),
			} );

			const itemsData = await response.json();
			const totalCount = parseInt( response.headers.get( 'x-wp-total' ) );
			dispatch( 'wc-admin' ).setReportItems( endpoint, query, itemsData, totalCount );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setReportItemsError( endpoint, query );
		}
	},
};
