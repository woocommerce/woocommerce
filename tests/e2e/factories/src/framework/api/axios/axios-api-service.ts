import axios, { AxiosInstance } from 'axios';
import { APIResponse, APIService } from '../api-service';
import { AxiosAuthInterceptor } from './axios-auth-interceptor';
import { AxiosResponseInterceptor } from './axios-response-interceptor';

/**
 * An API service implementation that uses Axios to make requests to the WordPress API.
 */
export class AxiosAPIService implements APIService {
	private readonly client: AxiosInstance;
	private authInterceptor: AxiosAuthInterceptor;
	private responseInterceptor: AxiosResponseInterceptor;

	public constructor(
		baseAPIURL: string,
		consumerKey: string,
		consumerSecret: string,
	) {
		this.client = axios.create( {
			baseURL: baseAPIURL,
		} );
		this.authInterceptor = new AxiosAuthInterceptor(
			this.client,
			consumerKey,
			consumerSecret,
		);
		this.authInterceptor.start();
		this.responseInterceptor = new AxiosResponseInterceptor( this.client );
		this.responseInterceptor.start();
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
