/** @format */

/**
 * External dependencies
 */
const { apiFetch } = wp;
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { stringifyQuery } from 'lib/nav-utils';
import { NAMESPACE } from 'store/constants';

export default {
	async getReportRevenueStats( state, query ) {
		try {
			const report = await apiFetch( {
				path: NAMESPACE + 'reports/revenue/stats' + stringifyQuery( query ),
			} );
			dispatch( 'wc-admin' ).setReportRevenueStats( report, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setReportRevenueStatsError( query );
		}
	},
};
