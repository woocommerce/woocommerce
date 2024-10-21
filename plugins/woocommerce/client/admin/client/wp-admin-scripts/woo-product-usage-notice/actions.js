/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { getSetting } from '@woocommerce/settings';
import apiFetch from '@wordpress/api-fetch';

const request = ( { action, productId, nonce }, callback ) => {
	const url = addQueryArgs(
		new URL( 'admin-ajax.php', getSetting( 'adminUrl' ) ).toString(),
		{
			action,
			product_id: productId,
			_ajax_nonce: nonce,
		}
	);

	const headers = {
		'Content-Type': 'application/json',
	};

	apiFetch( {
		url,
		method: 'GET',
		headers,
	} ).then( ( response ) => {
		if ( callback ) {
			callback( response );
		}
	} );
};

export const dismissRequest = (
	{ dismissAction, productId, dismissNonce },
	callback
) => {
	const args = {
		action: dismissAction,
		productId,
		nonce: dismissNonce,
	};
	request( args, callback );
};

export const remindLaterRequest = (
	{ remindLaterAction, productId, remindLaterNonce },
	callback
) => {
	const args = {
		action: remindLaterAction,
		productId,
		nonce: remindLaterNonce,
	};
	request( args, callback );
};
