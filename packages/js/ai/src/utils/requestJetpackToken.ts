/**
 * External dependencies
 */
import debugFactory from 'debug';
import { requestJwt } from '@automattic/jetpack-ai-client';

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
	const { token, blogId, expire } = await requestJwt();
	if ( ! token ) {
		throw createExtendedError(
			'Error fetching new token',
			'token_fetch_error'
		);
	}
	return { token, blogId, expire };
}
