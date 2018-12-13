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
				.then( user => {
					const userData = mapValues( user.woocommerce_meta, JSON.parse );
					return { [ 'current-user-data' ]: { data: userData } };
				} )
				.catch( error => {
					return { [ 'current-user-data' ]: { error } };
				} ),
		];
	}
	return [];
}

function updateCurrentUserData( resourceNames, data, fetch ) {
	const resourceName = 'current-user-data';
	const userDataFields = [ 'revenue_report_columns' ];

	if ( resourceNames.includes( resourceName ) ) {
		const url = '/wp/v2/users/me';
		const userData = pick( data[ resourceName ], userDataFields );
		const meta = mapValues( userData, JSON.stringify );
		const user = { woocommerce_meta: meta };

		return [
			fetch( { path: url, method: 'POST', data: user } )
				.then( updatedUserData => {
					return { [ resourceName ]: { data: updatedUserData.woocommerce_meta } };
				} )
				.catch( error => {
					return { [ resourceName ]: { error } };
				} ),
		];
	}
	return [];
}

export default {
	read,
	update,
};
