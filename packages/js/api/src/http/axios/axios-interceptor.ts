import { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

/**
 * An object containing the IDs for an interceptor currently applied to a client.
 *
 * @typedef ActiveInterceptor
 * @property {AxiosInstance} client The client the interceptor is tied to.
 * @property {number} requestInterceptorID The ID of the request interceptor callbacks.
 * @property {number} responseInterceptorID The ID of the response interceptor callbacks.
 */
type ActiveInterceptor = {
	client: AxiosInstance;
	requestInterceptorID: number;
	responseInterceptorID: number;
}

/**
 * A base class for encapsulating the start and stop functionality required by all Axios interceptors.
 */
export abstract class AxiosInterceptor {
	/**
	 * An array of the active interceptor records for all of the clients this interceptor is attached to.
	 *
	 * @type {ActiveInterceptor[]}
	 * @private
	 */
	private readonly activeInterceptors: ActiveInterceptor[] = [];

	/**
	 * Starts intercepting requests and responses.
	 *
	 * @param {AxiosInstance} client The client to start intercepting the requests/responses of.
	 */
	public start( client: AxiosInstance ): void {
		const requestInterceptorID = client.interceptors.request.use(
			( response ) => this.handleRequest( response ),
		);
		const responseInterceptorID = client.interceptors.response.use(
			( response ) => this.onResponseSuccess( response ),
			( error ) => this.onResponseRejected( error ),
		);
		this.activeInterceptors.push( { client, requestInterceptorID, responseInterceptorID } );
	}

	/**
	 * Stops intercepting requests and responses.
	 *
	 * @param {AxiosInstance} client The client to stop intercepting the requests/responses of.
	 */
	public stop( client: AxiosInstance ): void {
		for ( let i = this.activeInterceptors.length - 1; i >= 0; --i ) {
			const active = this.activeInterceptors[ i ];
			if ( client === active.client ) {
				client.interceptors.request.eject( active.requestInterceptorID );
				client.interceptors.response.eject( active.responseInterceptorID );
				this.activeInterceptors.splice( i, 1 );
			}
		}
	}

	/**
	 * An interceptor method for handling requests before they are made to the server.
	 *
	 * @param {AxiosRequestConfig} config The Axios request options.
	 */
	protected handleRequest( config: AxiosRequestConfig ): AxiosRequestConfig {
		return config;
	}

	/**
	 * An interceptor method for handling successful responses.
	 *
	 * @param {*} response The response from the Axios client.
	 */
	protected onResponseSuccess( response: AxiosResponse ): any {
		return response;
	}

	/**
	 * An interceptor method for handling response failures.
	 *
	 * @param {Promise} error The error that occurred.
	 */
	protected onResponseRejected( error: any ): any {
		throw error;
	}
}
