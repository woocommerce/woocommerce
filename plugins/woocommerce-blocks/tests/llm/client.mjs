import fetch, { Headers } from 'node-fetch';

export const WOOCOMMERCE_STORE_URL = '';
// WooCommerce REST API credentials https://woocommerce.github.io/woocommerce-rest-api-docs/#rest-api-keys
export const WOOCOMMERCE_AUTH_TOKEN = '';
// WordPress.com application credentials https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/#basic-authentication-with-application-passwords
export const WORDPRESS_APPLICATION_AUTH = '';

/**
 * Makes an asynchronous HTTP request to a specified endpoint.
 *
 * @param {string}                  endpoint - The endpoint to make the request to.
 * @param {'GET' | 'POST'}          method   - The HTTP method for the request (GET or POST).
 * @param {Record<string, unknown>} body     - The request body for POST requests.
 * @return {Promise<Response>} A promise that resolves to the HTTP response.
 * @throws {Error} Throws an error if the response status is not in the success range (>= 300).
 */
export const request = async (
	endpoint,
	method,
	body,
	isWCEndpoint = true
) => {
	const authorizationHeader = isWCEndpoint
		? {
				Authorization: 'Basic ' + btoa( WOOCOMMERCE_AUTH_TOKEN ),
		  }
		: {
				Authorization: 'Basic ' + btoa( WORDPRESS_APPLICATION_AUTH ),
		  };

	const response = await fetch( WOOCOMMERCE_STORE_URL + endpoint, {
		headers: new Headers( {
			...authorizationHeader,
			'content-type': 'application/json',
		} ),
		method,
		...( method === 'POST' && {
			body: JSON.stringify( body ),
		} ),
	} );

	if ( response.status < 300 ) {
		return response;
	}
	throw new Error( await response.text() );
};
