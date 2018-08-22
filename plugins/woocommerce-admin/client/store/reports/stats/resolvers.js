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
		let apiPath = endpoint;

		if ( statEndpoints.indexOf( endpoint ) >= 0 ) {
			apiPath = NAMESPACE + 'reports/' + endpoint + '/stats' + stringifyQuery( query );
		}

		try {
			const report = await apiFetch( {
				path: apiPath,
			} );
			dispatch( 'wc-admin' ).setReportStats( endpoint, report, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setReportStatsError( endpoint, query );
		}
	},
};
