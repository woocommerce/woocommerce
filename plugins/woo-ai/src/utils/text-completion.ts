/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import debugFactory from 'debug';

/**
 * Internal dependencies
 */
import { WOO_AI_PLUGIN_FEATURE_NAME } from '../constants';

const debugToken = debugFactory( 'jetpack-ai-assistant:token' );

const JWT_TOKEN_ID = 'jetpack-ai-jwt-token';
const JWT_TOKEN_EXPIRATION_TIME = 2 * 60 * 1000;

declare global {
	interface Window {
		JP_CONNECTION_INITIAL_STATE: {
			apiNonce: string;
			siteSuffix: string;
		};
	}
}

/**
 * Request a token from the Jetpack site to use with the API
 *
 * @return {Promise<{token: string, blogId: string}>} The token and the blogId
 */
async function requestJetpackToken() {
	const token = localStorage.getItem( JWT_TOKEN_ID );
	let tokenData;

	if ( token ) {
		try {
			tokenData = JSON.parse( token );
		} catch ( err ) {
			debugToken( 'Error parsing token', err );
			throw new Error( 'Error parsing cached token' );
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
			 * Let's expire the token in 5 minutes
			 */
			expire: Date.now() + JWT_TOKEN_EXPIRATION_TIME,
		};

		debugToken( 'Storing new token' );
		localStorage.setItem( JWT_TOKEN_ID, JSON.stringify( newTokenData ) );

		return newTokenData;
	} catch ( e ) {
		throw new Error( 'Error fetching new token' );
	}
}

/**
 * Leaving this here to make it easier to debug the streaming API calls for now
 *
 * @param {string} question - The query to send to the API
 * @param {number} postId   - The post where this completion is being requested, if available
 */
export async function getCompletion( question: string ) {
	const { token } = await requestJetpackToken();

	const url = new URL(
		'https://public-api.wordpress.com/wpcom/v2/text-completion/stream'
	);

	url.searchParams.append( 'prompt', question );
	url.searchParams.append( 'token', token );
	url.searchParams.append( 'feature', WOO_AI_PLUGIN_FEATURE_NAME );

	return new EventSource( url.toString() );
}
