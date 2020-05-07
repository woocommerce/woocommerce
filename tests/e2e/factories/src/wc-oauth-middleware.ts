import OAuth from 'oauth-1.0a';
import createHmac from 'create-hmac';

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
 * This type reflects the Request type that apiFetch expects us to use.
 * Since we're only using it in this middleware, I think its fine
 * keeping it local to this file.
 */
type Request = {
	url: string;
	method: string;
	headers: {
		[ key: string ]: string;
	};
};

/**
 * Applies a middleware that signs requests with using a WooCommerce OAuth key.
 *
 * @param {string} consumerKey
 * @param {string} consumerSecret
 */
export function createSignatureMiddleware(
	consumerKey: string,
	consumerSecret: string
) {
	function middleware(
		options: Request,
		next: ( options: Request ) => Promise< any >
	): Promise< any > {
		const { headers = {} } = options;

		let authHeader = '';
		if ( options.url.startsWith( 'https' ) ) {
			authHeader =
				'Basic ' +
				Buffer.from( consumerKey + ':' + consumerSecret ).toString(
					'base64'
				);
		} else {
			authHeader = middleware.oauth.toHeader(
				middleware.oauth.authorize( {
					url: options.url,
					method: options.method,
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
