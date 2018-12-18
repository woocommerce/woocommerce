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
	// TODO: Use controls data plugin or fresh-data instead of async
	async getReportStats( ...args ) {
		// This is interim code to work with either 2.x or 3.x version of @wordpress/data
		// TODO: Change to just `getNotes( endpoint, query )`
		// after Gutenberg plugin uses @wordpress/data 3+
		const [ endpoint, query ] = args.length === 2 ? args : args.slice( 1, 3 );
		const statEndpoints = [ 'orders', 'revenue', 'products', 'taxes' ];

		let apiPath = endpoint + stringifyQuery( query );

		// TODO: Remove once swagger endpoints are phased out.
		const swaggerEndpoints = [ 'categories', 'coupons' ];
		if ( swaggerEndpoints.indexOf( endpoint ) >= 0 ) {
			apiPath = SWAGGERNAMESPACE + 'reports/' + endpoint + '/stats' + stringifyQuery( query );
			try {
				const response = await fetch( apiPath );

				const report = await response.json();
				dispatch( 'wc-admin' ).setReportStats( endpoint, report, query );
			} catch ( error ) {
				dispatch( 'wc-admin' ).setReportStatsError( endpoint, query );
			}
			return;
		}

		if ( statEndpoints.indexOf( endpoint ) >= 0 ) {
			apiPath = NAMESPACE + 'reports/' + endpoint + '/stats' + stringifyQuery( query );
		}

		try {
			const response = await apiFetch( {
				path: apiPath,
				parse: false,
			} );

			const report = await response.json();
			const totalResults = parseInt( response.headers.get( 'x-wp-total' ) );
			const totalPages = parseInt( response.headers.get( 'x-wp-totalpages' ) );
			dispatch( 'wc-admin' ).setReportStats( endpoint, report, query, totalResults, totalPages );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setReportStatsError( endpoint, query );
		}
	},
};
