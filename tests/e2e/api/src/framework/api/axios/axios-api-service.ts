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

	/**
	 * Performs a GET request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      params Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	public get<T>(
		endpoint: string,
		params?: any,
	): Promise<APIResponse<T>> {
		return this.client.get( endpoint, { params } );
	}

	/**
	 * Performs a POST request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	public post<T>(
		endpoint: string,
		data?: any,
	): Promise<APIResponse<T>> {
		return this.client.post( endpoint, data );
	}

	/**
	 * Performs a PUT request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	public put<T>(
		endpoint: string,
		data?: any,
	): Promise<APIResponse<T>> {
		return this.client.put( endpoint, data );
	}

	/**
	 * Performs a PATCH request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	public patch<T>(
		endpoint: string,
		data?: any,
	): Promise<APIResponse<T>> {
		return this.client.patch( endpoint, data );
	}

	/**
	 * Performs a DELETE request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIResponse containing an APIError.
	 */
	public delete<T>(
		endpoint: string,
		data?: any,
	): Promise<APIResponse<T>> {
		return this.client.delete( endpoint, { data } );
	}
}
