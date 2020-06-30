import axios, { AxiosInstance } from 'axios';
import { APIAuthInterceptor } from './api-auth-interceptor';
import { APIResponse, APIService } from '../api-service';
import { APIResponseInterceptor } from './api-response-interceptor';
import { Agent } from 'https';

/**
 * An API service implementation that uses Axios to make requests to the WordPress API.
 */
export class AxiosAPIService implements APIService {
	private readonly client: AxiosInstance;
	private authInterceptor: APIAuthInterceptor;
	private responseInterceptor: APIResponseInterceptor;

	public constructor(
		baseAPIURL: string,
		consumerKey: string,
		consumerSecret: string,
	) {
		this.client = axios.create( {
			baseURL: baseAPIURL,
			httpsAgent: new Agent( {
				rejectUnauthorized: false,
			} ),
		} );
		this.authInterceptor = new APIAuthInterceptor(
			this.client,
			consumerKey,
			consumerSecret,
		);
		this.authInterceptor.start();
		this.responseInterceptor = new APIResponseInterceptor( this.client );
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
		return this.client.post( endpoint, { data } );
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
		return this.client.put( endpoint, { data } );
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
		return this.client.patch( endpoint, { data } );
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
