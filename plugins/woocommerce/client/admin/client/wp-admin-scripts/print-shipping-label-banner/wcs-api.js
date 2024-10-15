/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

export function acceptWcsTos() {
	const path = '/wcshipping/v1/tos';
	return apiFetch( {
		path,
		method: 'POST',
		data: { accepted: true },
	} );
}

export function getWcsAssets() {
	const path = '/wcshipping/v1/assets';
	return apiFetch( {
		path,
		method: 'GET',
	} );
}

export function getWcsLabelPurchaseConfigs( orderId ) {
	const path = `wcshipping/v1/config/label-purchase/${ orderId }`;
	return apiFetch( {
		path,
		method: 'GET',
	} );
}
