import { APIResponse, APIError, APIService } from '../api-service';
import { APIAuthInterceptor } from './api-auth-interceptor';
import axios, {
	AxiosInstance,
	AxiosTransformer,
	AxiosResponse,
	AxiosError,
	Method,
	AxiosRequestConfig,
} from 'axios';

/**
 * An API service implementation that uses Axios to make requests to the WordPress API.
 */
export class AxiosAPIService implements APIService {
	private client: AxiosInstance;
	private authInterceptor: APIAuthInterceptor;

	public constructor(
		baseAPIURL: string,
		consumerKey: string,
		consumerSecret: string
	) {
		this.client = axios.create( {
			baseURL: baseAPIURL,
		} );
		this.authInterceptor = new APIAuthInterceptor(
			this.client,
			consumerKey,
			consumerSecret
		);
		this.authInterceptor.start();
	}

	/**
	 * Performs a GET request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      params Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and rejects an APIError.
	 */
	public get< T >(
		endpoint: string,
		params?: any
	): Promise< APIResponse< T > | APIError< T > > {
		return this.request( 'GET', endpoint, params );
	}

	/**
	 * Performs a POST request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIError.
	 */
	public post< T >(
		endpoint: string,
		data?: any
	): Promise< APIResponse< T > | APIError< T > > {
		return this.request( 'POST', endpoint, data );
	}

	/**
	 * Performs a PUT request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIError.
	 */
	public put< T >(
		endpoint: string,
		data?: any
	): Promise< APIResponse< T > | APIError< T > > {
		return this.request( 'PUT', endpoint, data );
	}

	/**
	 * Performs a PATCH request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIError.
	 */
	public patch< T >(
		endpoint: string,
		data?: any
	): Promise< APIResponse< T > | APIError< T > > {
		return this.request( 'PATCH', endpoint, data );
	}

	/**
	 * Performs a DELETE request against the WordPress API.
	 *
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIError.
	 */
	public delete< T >(
		endpoint: string,
		data?: any
	): Promise< APIResponse< T > | APIError< T > > {
		return this.request( 'DELETE', endpoint, data );
	}

	/**
	 * Performs an HTTP request against the WordPress API.
	 *
	 * @param {string} method The HTTP method we should use in the request.
	 * @param {string} endpoint The API endpoint we should query.
	 * @param {*}      data Any parameters that should be passed in the request.
	 * @return {Promise} Resolves to an APIResponse and throws an APIError.
	 */
	public request< T >(
		method: Method,
		endpoint: string,
		data?: any
	): Promise< APIResponse< T > | APIError< T > > {
		const config: AxiosRequestConfig = {
			method,
			url: endpoint,
		};
		if ( data ) {
			if ( 'GET' === method ) {
				config.params = data;
			} else {
				config.data = data;
			}
		}

		return this.client.request( config ).then(
			( response ) => this.onFulfilled< T >( response ),
			( error ) => this.onRejected< T >( error )
		);
	}

	/**
	 * Transforms the Axios response into our API response to be consumed in a consistent manner.
	 *
	 * @param {AxiosResponse} response The respons ethat we need to transform.
	 * @return {Promise} A promise containing the APIResponse.
	 */
	private onFulfilled< T >(
		response: AxiosResponse
	): Promise< APIResponse< T > > {
		return Promise.resolve< APIResponse< T > >(
			new APIResponse< T >(
				response.status,
				response.headers,
				response.data
			)
		);
	}

	/**
	 * Transforms the Axios error into our API error to be consumed in a consistent manner.
	 *
	 * @param {*} error The error
	 * @return {Promise} A promise containing the APIError.
	 */
	private onRejected< T >( error: any ): Promise< APIError< T > > {
		let apiResponse: APIResponse< T > | null = null;
		let message: string | null = null;
		if ( error.response ) {
			apiResponse = new APIResponse< T >(
				error.response.status,
				error.response.headers,
				error.response.data
			);
		} else if ( error.request ) {
			message = 'No response was received from the server.';
		} else {
			// Keep bubbling up the errors that we aren't handling.
			throw error;
		}

		return Promise.reject( new APIError< T >( apiResponse, message ) );
	}
}
