import { AxiosInstance, AxiosRequestConfig } from 'axios';
import createHmac from 'create-hmac';
import OAuth from 'oauth-1.0a';

/**
 * A utility class for managing the lifecycle of an authentication interceptor.
 */
export class APIAuthInterceptor {
	private readonly client: AxiosInstance;
	private interceptorID: number | null;
	private oauth: OAuth;

	public constructor(
		client: AxiosInstance,
		consumerKey: string,
		consumerSecret: string,
	) {
		this.client = client;
		this.interceptorID = null;
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
	 * Starts adding WooCommerce API authentication details to requests made using the contained client.
	 */
	public start(): void {
		if ( null === this.interceptorID ) {
			this.interceptorID = this.client.interceptors.request.use(
				( request ) => this.handleRequest( request ),
			);
		}
	}

	/**
	 * Stops adding WooCommerce API authentication details to requests made using the contained client.
	 */
	public stop(): void {
		if ( null !== this.interceptorID ) {
			this.client.interceptors.request.eject( this.interceptorID );
			this.interceptorID = null;
		}
	}

	/**
	 * Adds WooCommerce API authentication details to the outgoing request.
	 *
	 * @param {AxiosRequestConfig} request The request that was intercepted.
	 * @return {AxiosRequestConfig} The request with the additional authorization headers.
	 */
	private handleRequest( request: AxiosRequestConfig ): AxiosRequestConfig {
		const url = request.baseURL || '' + request.url || '';
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
