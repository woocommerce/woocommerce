/** @format */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { mapValues, pick } from 'lodash';

function read( resourceNames, fetch = apiFetch ) {
	return [ ...readCurrentUserData( resourceNames, fetch ) ];
}

function update( resourceNames, data, fetch = apiFetch ) {
	return [ ...updateCurrentUserData( resourceNames, data, fetch ) ];
}

function readCurrentUserData( resourceNames, fetch ) {
	if ( resourceNames.includes( 'current-user-data' ) ) {
		const url = '/wp/v2/users/me?context=edit';

		return [
			fetch( { path: url } )
				.then( userToUserDataResource )
				.catch( error => {
					return { [ 'current-user-data' ]: { error: String( error.message ) } };
				} ),
		];
	}
	return [];
}

function updateCurrentUserData( resourceNames, data, fetch ) {
	const resourceName = 'current-user-data';
	const userDataFields = [
		'categories_report_columns',
		'coupons_report_columns',
		'customers_report_columns',
		'orders_report_columns',
		'products_report_columns',
		'revenue_report_columns',
		'taxes_report_columns',
		'variations_report_columns',
		'dashboard_performance_indicators',
		'dashboard_charts',
		'dashboard_chart_type',
		'dashboard_chart_interval',
		'dashboard_leaderboards',
		'dashboard_leaderboard_rows',
		'activity_panel_inbox_last_read',
	];

	if ( resourceNames.includes( resourceName ) ) {
		const url = '/wp/v2/users/me';
		const userData = pick( data[ resourceName ], userDataFields );
		const meta = mapValues( userData, JSON.stringify );
		const user = { woocommerce_meta: meta };

		return [
			fetch( { path: url, method: 'POST', data: user } )
				.then( userToUserDataResource )
				.catch( error => {
					return { [ resourceName ]: { error } };
				} ),
		];
	}
	return [];
}

function userToUserDataResource( user ) {
	const userData = mapValues( user.woocommerce_meta, data => {
		if ( ! data || 0 === data.length ) {
			return '';
		}
		return JSON.parse( data );
	} );
	return { [ 'current-user-data' ]: { data: userData } };
}

export default {
	read,
	update,
};
