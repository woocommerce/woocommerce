import OAuth from 'oauth-1.0a';
import createHmac from 'create-hmac';
import { APIFetchOptions, Middleware } from '@wordpress/api-fetch';

/**
 * Creates the OAuth object that can be used to sign the request.
 *
 * @param {string} consumerKey
 * @param {string} consumerSecret
 */
function createOAuth( consumerKey: string, consumerSecret: string ): OAuth {
	return new OAuth( {
		consumer: {
			key: consumerKey,
			secret: consumerSecret,
		},
		signature_method: 'HMAC-SHA256',
		hash_function: ( base, key ) => {
			return createHmac( 'sha256', key )
				.update( base )
				.digest( 'base64' );
		},
	} );
}

/**
 * Applies a middleware that signs requests with using a WooCommerce OAuth key.
 *
 * @param {string} consumerKey
 * @param {string} consumerSecret
 */
export function createSignatureMiddleware(
	consumerKey: string,
	consumerSecret: string
): Middleware {
	function middleware(
		options: APIFetchOptions,
		next: ( options: APIFetchOptions ) => Promise< any >
	): Promise< any > {
		const { headers = {} } = options;

		let authHeader = '';
		if ( options.url!.startsWith( 'https' ) ) {
			authHeader =
				'Basic ' +
				Buffer.from( consumerKey + ':' + consumerSecret ).toString(
					'base64'
				);
		} else {
			authHeader = middleware.oauth.toHeader(
				middleware.oauth.authorize( {
					url: options.url!,
					method: options.method!,
				} )
			).Authorization;
		}

		return next( {
			...options,
			headers: {
				...headers,
				Authorization: authHeader,
			},
		} );
	}

	middleware.oauth = createOAuth( consumerKey, consumerSecret );

	return middleware;
}
