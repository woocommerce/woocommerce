import type { AxiosRequestConfig } from 'axios';
import * as createHmac from 'create-hmac';
import * as OAuth from 'oauth-1.0a';
import { AxiosInterceptor } from './axios-interceptor';
import { buildURLWithParams } from './utils';

/**
 * An interceptor for adding OAuth 1.0a signatures to HTTP requests.
 */
export class AxiosOAuthInterceptor extends AxiosInterceptor {
	/**
	 * The OAuth class for signing the request.
	 *
	 * @type {Object}
	 * @private
	 */
	private readonly oauth: OAuth;

	/**
	 * Creates a new interceptor.
	 *
	 * @param {string} consumerKey The consumer key of the API key.
	 * @param {string} consumerSecret The consumer secret of the API key.
	 */
	public constructor( consumerKey: string, consumerSecret: string ) {
		super();

		this.oauth = new OAuth( {
			consumer: {
				key: consumerKey,
				secret: consumerSecret,
			},
			signature_method: 'HMAC-SHA256',
			hash_function: ( base: any, key: any ) => {
				return createHmac( 'sha256', key ).update( base ).digest( 'base64' );
			},
		} );
	}

	/**
	 * Adds WooCommerce API authentication details to the outgoing request.
	 *
	 * @param {AxiosRequestConfig} request The request that was intercepted.
	 * @return {AxiosRequestConfig} The request with the additional authorization headers.
	 */
	protected handleRequest( request: AxiosRequestConfig ): AxiosRequestConfig {
		const url = buildURLWithParams( request );
		if ( url.startsWith( 'https' ) ) {
			request.auth = {
				username: this.oauth.consumer.key,
				password: this.oauth.consumer.secret,
			};
		} else {
			request.headers.Authorization = this.oauth.toHeader(
				this.oauth.authorize( {
					url,
					method: request.method!,
				} ),
			).Authorization;
		}

		return request;
	}
}
