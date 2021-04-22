import { AxiosInterceptor } from './axios-interceptor';
import { AxiosRequestConfig } from 'axios';
import { buildURL } from './utils';

/**
 * An interceptor for transforming the request's path into a query parameter.
 */
export class AxiosURLToQueryInterceptor extends AxiosInterceptor {
	/**
	 * The query parameter we want to assign the path to.
	 *
	 * @type {string}
	 * @private
	 */
	private readonly queryParam: string;

	/**
	 * Constructs a new interceptor.
	 *
	 * @param {string} queryParam The query parameter we want to assign the path to.
	 */
	public constructor( queryParam: string ) {
		super();

		this.queryParam = queryParam;
	}

	/**
	 * Converts the outgoing path into a query parameter.
	 *
	 * @param {AxiosRequestConfig} config The axios config.
	 * @return {AxiosRequestConfig} The axios config.
	 */
	protected handleRequest( config: AxiosRequestConfig ): AxiosRequestConfig {
		const url = new URL( buildURL( config ) );

		// Store the path in the query string.
		if ( config.params instanceof URLSearchParams ) {
			config.params.set( this.queryParam, url.pathname );
		} else if ( config.params ) {
			config.params[ this.queryParam ] = url.pathname;
		} else {
			config.params = { [ this.queryParam ]: url.pathname };
		}

		// Store the URL without the path now that it's in the query string.
		url.pathname = '';
		config.url = url.toString();
		delete config.baseURL;

		return config;
	}
}
