/** @format */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { stringifyQuery } from 'lib/nav-utils';
import { NAMESPACE } from 'store/constants';

export default {
	async getReportStats( state, endpoint, query ) {
		const statEndpoints = [ 'orders', 'revenue', 'products' ];
		let apiPath = endpoint + stringifyQuery( query );

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
