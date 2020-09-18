import { AxiosClient } from './axios-client';
import { AxiosOAuthInterceptor } from './axios-oauth-interceptor';

/**
 * Creates a new axios client instance prepared for basic auth.
 *
 * @param {string} apiURL
 * @param {string} username
 * @param {string} password
 * @return {AxiosClient} An Axios client configured for OAuth requests.
 */
export function withBasicAuth( apiURL: string, username: string, password: string ): AxiosClient {
	return new AxiosClient(
		{
			baseURL: apiURL,
			auth: { username, password },
		},
	);
}

/**
 * Creates a new axios client instance prepared for oauth.
 *
 * @param {string} apiURL
 * @param {string} consumerKey
 * @param {string} consumerSecret
 * @return {AxiosClient} An Axios client configured for OAuth requests.
 */
export function withOAuth( apiURL: string, consumerKey: string, consumerSecret: string ): AxiosClient {
	return new AxiosClient(
		{ baseURL: apiURL },
		[ new AxiosOAuthInterceptor( consumerKey, consumerSecret ) ],
	);
}
