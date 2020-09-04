import { HTTPClient, HTTPResponse } from '../http-client';
import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import { AxiosInterceptor } from './axios-interceptor';

/**
 * An HTTPClient implementation that uses Axios to make requests.
 */
export class AxiosClient implements HTTPClient {
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
	 * Performs a GET request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*}      params Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	public get< T >(
		path: string,
		params?: any,
	): Promise< HTTPResponse< T >> {
		return this.client.get( path, { params } );
	}

	/**
	 * Performs a POST request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	public post< T >(
		path: string,
		data?: any,
	): Promise< HTTPResponse< T >> {
		return this.client.post( path, data );
	}

	/**
	 * Performs a PUT request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	public put< T >(
		path: string,
		data?: any,
	): Promise< HTTPResponse< T >> {
		return this.client.put( path, data );
	}

	/**
	 * Performs a PATCH request.
	 *
	 * @param {string} path The path we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	public patch< T >(
		path: string,
		data?: any,
	): Promise< HTTPResponse< T >> {
		return this.client.patch( path, data );
	}

	/**
	 * Performs a DELETE request.
	 *
	 * @param {string} path The path we should send the request to.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an HTTPResponse.
	 */
	public delete< T >(
		path: string,
		data?: any,
	): Promise< HTTPResponse< T >> {
		return this.client.delete( path, { data } );
	}
}
