import { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

type ActiveInterceptor = {
	client: AxiosInstance;
	requestInterceptorID: number;
	responseInterceptorID: number;
}

/**
 * A base class for encapsulating the start and stop functionality required by all axios interceptors.
 */
export abstract class AxiosInterceptor {
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
	 * @param {AxiosRequestConfig} config The axios request options.
	 */
	protected handleRequest( config: AxiosRequestConfig ): AxiosRequestConfig {
		return config;
	}

	/**
	 * An interceptor method for handling successful responses.
	 *
	 * @param {AxiosResponse} response The response from the axios client.
	 */
	protected onResponseSuccess( response: AxiosResponse ): any {
		return response;
	}

	/**
	 * An interceptor method for handling response failures.
	 *
	 * @param {*} error The error that occurred.
	 */
	protected onResponseRejected( error: any ): any {
		return error;
	}
}
