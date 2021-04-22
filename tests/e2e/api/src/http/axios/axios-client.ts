import { HTTPClient, HTTPResponse } from '../http-client';
import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import { AxiosInterceptor } from './axios-interceptor';
import { AxiosResponseInterceptor } from './axios-response-interceptor';

/**
 * An HTTPClient implementation that uses Axios to make requests.
 */
export class AxiosClient implements HTTPClient {
	/**
	 * An instance of the Axios client for making HTTP requests.
	 *
	 * @type {AxiosInstance}
	 * @private
	 */
	private readonly client: AxiosInstance;

	/**
	 * An array of interceptors that should be applied to the client.
	 *
	 * @type {AxiosInterceptor[]}
	 * @private
	 */
	private readonly interceptors: AxiosInterceptor[];

	/**
	 * Creates a new axios client.
	 *
	 * @param {AxiosRequestConfig} config The request configuration.
	 * @param {AxiosInterceptor[]} extraInterceptors An array of additional interceptors to apply to the client.
	 */
	public constructor( config: AxiosRequestConfig, extraInterceptors: AxiosInterceptor[] = [] ) {
		this.client = axios.create( config );

		this.interceptors = extraInterceptors;

		// The response interceptor needs to be last to prevent the other interceptors from
		// receiving the transformed HTTPResponse type instead of an AxiosResponse.
		this.interceptors.push( new AxiosResponseInterceptor() );

		for ( const interceptor of this.interceptors ) {
			interceptor.start( this.client );
		}
	}

	/**
	 * Performs a GET request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {Object} params Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	public get< T = any >(
		path: string,
		params?: object,
	): Promise< HTTPResponse< T >> {
		return this.client.get( path, { params } );
	}

	/**
	 * Performs a POST request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {Object} data Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	public post< T = any >(
		path: string,
		data?: object,
	): Promise< HTTPResponse< T >> {
		return this.client.post( path, data );
	}

	/**
	 * Performs a PUT request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {Object} data Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	public put< T = any >(
		path: string,
		data?: object,
	): Promise< HTTPResponse< T >> {
		return this.client.put( path, data );
	}

	/**
	 * Performs a PATCH request.
	 *
	 * @param {string} path The path we should query.
	 * @param {*} data Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	public patch< T = any >(
		path: string,
		data?: object,
	): Promise< HTTPResponse< T >> {
		return this.client.patch( path, data );
	}

	/**
	 * Performs a DELETE request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*} data Any parameters that should be passed in the request.
	 * @return {Promise.<HTTPResponse>} The response from the API.
	 */
	public delete< T = any >(
		path: string,
		data?: object,
	): Promise< HTTPResponse< T >> {
		return this.client.delete( path, { data } );
	}
}
