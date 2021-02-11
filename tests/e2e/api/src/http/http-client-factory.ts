import { HTTPClient } from './http-client';
import { AxiosClient, AxiosOAuthInterceptor } from './axios';
import { AxiosRequestConfig } from 'axios';
import { AxiosInterceptor } from './axios/axios-interceptor';
import { AxiosURLToQueryInterceptor } from './axios/axios-url-to-query-interceptor';

/**
 * These types describe the shape of the different auth methods our factory supports.
 */
type OAuthMethod = {
	type: 'oauth',
	key: string,
	secret: string,
};
type BasicAuthMethod = {
	type: 'basic',
	username: string,
	password: string,
}

/**
 * An interface for describing the shape of a client to create using the factory.
 */
interface BuildParams {
	wpURL: string,
	useIndexPermalinks?: boolean,
	auth?: OAuthMethod | BasicAuthMethod,
}

/**
 * A factory for generating an HTTPClient with a desired configuration.
 */
export class HTTPClientFactory {
	/**
	 * The configuration object describing the client we're trying to create.
	 *
	 * @private
	 */
	private clientConfig: BuildParams;

	private constructor( wpURL: string ) {
		this.clientConfig = { wpURL };
	}

	/**
	 * Creates a new factory that can be used to build clients.
	 *
	 * @param {string} wpURL The root URL of the WordPress installation we're querying.
	 * @return {HTTPClientFactory} The new factory instance.
	 */
	public static build( wpURL: string ): HTTPClientFactory {
		return new HTTPClientFactory( wpURL );
	}

	/**
	 * Configures the client to utilize OAuth.
	 *
	 * @param {string} key The OAuth consumer key to use.
	 * @param {string} secret The OAuth consumer secret to use.
	 * @return {HTTPClientFactory} This factory.
	 */
	public withOAuth( key: string, secret: string ): this {
		this.clientConfig.auth = { type: 'oauth', key, secret };
		return this;
	}

	/**
	 * Configures the client to utilize basic auth.
	 *
	 * @param {string} username The WordPress username to use.
	 * @param {string} password The password for the WordPress user.
	 * @return {HTTPClientFactory} This factory.
	 */
	public withBasicAuth( username: string, password: string ): this {
		this.clientConfig.auth = { type: 'basic', username, password };
		return this;
	}

	/**
	 * Configures the client to use index permalinks.
	 *
	 * @return {HTTPClientFactory} This factory.
	 */
	public withIndexPermalinks(): this {
		this.clientConfig.useIndexPermalinks = true;
		return this;
	}

	/**
	 * Configures the client to use query permalinks.
	 *
	 * @return {HTTPClientFactory} This factory.
	 */
	public withoutIndexPermalinks(): this {
		this.clientConfig.useIndexPermalinks = false;
		return this;
	}

	/**
	 * Creates a client instance using the configuration stored within.
	 *
	 * @return {HTTPClient} The created client.
	 */
	public create(): HTTPClient {
		const axiosConfig: AxiosRequestConfig = {};
		const interceptors: AxiosInterceptor[] = [];

		axiosConfig.baseURL = this.clientConfig.wpURL;
		if ( ! axiosConfig.baseURL.endsWith( '/' ) ) {
			axiosConfig.baseURL += '/';
		}

		if ( this.clientConfig.useIndexPermalinks ) {
			axiosConfig.baseURL += 'wp-json/';
		} else {
			interceptors.push( new AxiosURLToQueryInterceptor( 'rest_route' ) );
		}

		if ( this.clientConfig.auth ) {
			switch ( this.clientConfig.auth.type ) {
				case 'basic':
					axiosConfig.auth = {
						username: this.clientConfig.auth.username,
						password: this.clientConfig.auth.password,
					};
					break;

				case 'oauth':
					interceptors.push(
						new AxiosOAuthInterceptor(
							this.clientConfig.auth.key,
							this.clientConfig.auth.secret,
						),
					);
					break;
			}
		}

		return new AxiosClient( axiosConfig, interceptors );
	}
}
