import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import { APIResponse, APIService } from '../api-service';
import { AxiosOAuthInterceptor } from './axios-oauth-interceptor';
import { AxiosInterceptor } from './axios-interceptor';
import { AxiosResponseInterceptor } from './axios-response-interceptor';

/**
 * An API service implementation that uses Axios to make requests to the WordPress API.
 */
export class AxiosAPIService implements APIService {
	private readonly client: AxiosInstance;
	private readonly interceptors: AxiosInterceptor[];

	public constructor( config: AxiosRequestConfig, interceptors: AxiosInterceptor[] = [] ) {
		this.client = axios.create( config );
		this.interceptors = interceptors;
		for ( const interceptor of this.interceptors ) {
			interceptor.start( this.client );
		}
	}

	/**
	 * Creates a new Axios API Service using OAuth 1.0a one-legged authentication.
	 *
	 * @param {string} apiURL The base URL for the API requests to be sent.
	 * @param {string} consumerKey The OAuth consumer key.
	 * @param {string} consumerSecret The OAuth consumer secret.
	 * @return {AxiosAPIService} The created service.
	 */
	public static createUsingOAuth( apiURL: string, consumerKey: string, consumerSecret: string ): AxiosAPIService {
		return new AxiosAPIService(
			{ baseURL: apiURL },
			[
				new AxiosOAuthInterceptor( consumerKey, consumerSecret ),
				new AxiosResponseInterceptor(),
			],
		);
	}

	/**
	 * Creates a new Axios API Service using basic authentication.
	 *
	 * @param {string} apiURL The base URL for the API requests to be sent.
	 * @param {string} username The username for authentication.
	 * @param {string} password The password for authentication.
	 * @return {AxiosAPIService} The created service.
	 */
	public static createUsingBasicAuth( apiURL: string, username: string, password: string ): AxiosAPIService {
		return new AxiosAPIService(
			{
				baseURL: apiURL,
				auth: { username, password },
			},
			[ new AxiosResponseInterceptor() ],
		);
	}
}
