/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

export function acceptWcsTos() {
	const path = '/wc/v1/connect/tos';
	return apiFetch( {
		path,
		method: 'POST',
		data: { accepted: true },
	} );
}

export function getWcsAssets() {
	const path = '/wc/v1/connect/assets';
	return apiFetch( {
		path,
		method: 'GET',
	} );
}
