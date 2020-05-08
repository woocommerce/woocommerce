/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

// @ts-ignore wcStoreApiNonce is window global
// Cache for the initial nonce initialized from hydration.
let nonce = wcStoreApiNonce || '';

/**
 * Returns whether or not this is a non GET wc/store API request.
 *
 * @param {Object} options Fetch options.
 *
 * @return {boolean} Returns true if this is a store request.
 */
const isStoreApiGetRequest = ( options ) => {
	const url = options.url || options.path;
	if ( ! url || ! options.method || options.method === 'GET' ) {
		return false;
	}
	return /wc\/store\//.exec( url ) !== null;
};

/**
 * Set the current nonce from a header object.
 *
 * @param {Object} headers Headers object.
 */
const setNonce = ( headers ) => {
	const newNonce = headers?.get( 'X-WC-Store-API-Nonce' );
	if ( newNonce ) {
		nonce = newNonce;
	}
};

/**
 * Nonce middleware which updates the nonce after a request, if given.
 *
 * @param {Object}   options Fetch options.
 * @param {Function} next    The next middleware or fetchHandler to call.
 *
 * @return {*} The evaluated result of the remaining middleware chain.
 */
const storeNonceMiddleware = ( options, next ) => {
	if ( isStoreApiGetRequest( options ) ) {
		const existingHeaders = options.headers || {};
		options.headers = {
			...existingHeaders,
			'X-WC-Store-API-Nonce': nonce,
		};
	}
	return next( options, next );
};

apiFetch.use( storeNonceMiddleware );
apiFetch.setNonce = setNonce;
