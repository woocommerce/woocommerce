import { HTTPClient } from './http-client';
import { AxiosClient, AxiosOAuthInterceptor } from './axios';

/**
 * A class for generating HTTPClient instances with desired configurations.
 */
export class HTTPClientFactory {
	/**
	 * Creates a new client instance prepared for basic auth.
	 *
	 * @param {string} apiURL
	 * @param {string} username
	 * @param {string} password
	 * @return {HTTPClient} An HTTP client configured for OAuth requests.
	 */
	public static withBasicAuth( apiURL: string, username: string, password: string ): HTTPClient {
		return new AxiosClient(
			{
				baseURL: apiURL,
				auth: { username, password },
			},
		);
	}

	/**
	 * Creates a new client instance prepared for oauth.
	 *
	 * @param {string} apiURL
	 * @param {string} consumerKey
	 * @param {string} consumerSecret
	 * @return {HTTPClient} An HTTP client configured for OAuth requests.
	 */
	public static withOAuth( apiURL: string, consumerKey: string, consumerSecret: string ): HTTPClient {
		return new AxiosClient(
			{ baseURL: apiURL },
			[ new AxiosOAuthInterceptor( consumerKey, consumerSecret ) ],
		);
	}
}
