import { AxiosRequestConfig } from 'axios';
import createHmac from 'create-hmac';
import OAuth from 'oauth-1.0a';
import { AxiosInterceptor } from './axios-interceptor';

/**
 * A utility class for managing the lifecycle of an authentication interceptor.
 */
export class AxiosOAuthInterceptor extends AxiosInterceptor {
	private oauth: OAuth;

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
		const url = ( request.baseURL || '' ) + ( request.url || '' );
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
