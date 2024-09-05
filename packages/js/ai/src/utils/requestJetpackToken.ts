/**
 * External dependencies
 */
import debugFactory from 'debug';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */

import { createExtendedError } from './create-extended-error';
export const debugToken = debugFactory( 'jetpack-ai-assistant:token' );

export const JWT_TOKEN_ID = 'jetpack-ai-jwt-token';
export const JWT_TOKEN_EXPIRATION_TIME = 2 * 60 * 1000;

declare global {
	interface Window {
		JP_CONNECTION_INITIAL_STATE: {
			apiNonce: string;
			siteSuffix: string;
			connectionStatus: { isActive: boolean };
		};
	}
}

/**
 * Request a token from the Jetpack site to use with the API
 *
 * @return {Promise<{token: string, blogId: string}>} The token and the blogId
 */

export async function requestJetpackToken() {
	const token = localStorage.getItem( JWT_TOKEN_ID );
	let tokenData;

	if ( token ) {
		try {
			tokenData = JSON.parse( token );
		} catch ( err ) {
			debugToken( 'Error parsing token', err );
			throw createExtendedError(
				'Error parsing cached token',
				'token_parse_error'
			);
		}
	}

	if ( tokenData && tokenData?.expire > Date.now() ) {
		debugToken( 'Using cached token' );
		return tokenData;
	}

	const apiNonce = window.JP_CONNECTION_INITIAL_STATE?.apiNonce;
	const siteSuffix = window.JP_CONNECTION_INITIAL_STATE?.siteSuffix;

	try {
		const data: { token: string; blog_id: string } = await apiFetch( {
			path: '/jetpack/v4/jetpack-ai-jwt?_cacheBuster=' + Date.now(),
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': apiNonce,
			},
			method: 'POST',
		} );

		const newTokenData = {
			token: data.token,
			blogId: siteSuffix,

			/**
			 * Let's expire the token in 2 minutes
			 */
			expire: Date.now() + JWT_TOKEN_EXPIRATION_TIME,
		};

		debugToken( 'Storing new token' );
		localStorage.setItem( JWT_TOKEN_ID, JSON.stringify( newTokenData ) );

		return newTokenData;
	} catch ( e ) {
		throw createExtendedError(
			'Error fetching new token',
			'token_fetch_error'
		);
	}
}
