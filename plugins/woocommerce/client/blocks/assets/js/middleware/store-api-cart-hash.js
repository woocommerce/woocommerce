/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { isStoreApiRequest } from './store-api-nonce';

// Stores the current hash for the middleware.
let currentCartHash = window.localStorage.getItem( 'storeApiCartHash' );

/**
 * Updates the stored CartHash within localStorage so it is persisted between page loads.
 *
 * @param {string} cartHash Incoming hash string
 */
const updateCartHash = ( cartHash ) => {
	// If the "new" CartHash matches the current CartHash, we don't need to update.
	if ( cartHash === currentCartHash ) {
		return;
	}
	currentCartHash = cartHash;
	window.localStorage.setItem( 'storeApiCartHash', currentCartHash );
};

/**
 * Set the current CartHash from a header object.
 *
 * @param {Object} headers Headers object.
 */
const setCartHash = ( headers ) => {
	const cartHash =
		typeof headers?.get === 'function'
			? headers.get( 'Cart-Hash' )
			: headers[ 'Cart-Hash' ];

	if ( cartHash ) {
		updateCartHash( cartHash );
	}
};

/**
 * Appends the last-seen cart hash to a request so the server can detect if the
 * cart changed server-side (e.g. price, quantity, coupon or shipping) since the
 * customer last saw it. The checkout POST rejects the order on a mismatch.
 *
 * @param {Object} request Fetch options.
 * @return {Object} The request with the Cart-Hash header appended.
 */
const appendCartHashHeader = ( request ) => {
	if ( ! currentCartHash ) {
		return request;
	}
	// Preserve the original headers type so existing headers (e.g. the nonce) are
	// not dropped when it is a Headers instance rather than a plain object.
	if ( request.headers instanceof Headers ) {
		request.headers.set( 'Cart-Hash', currentCartHash );
	} else {
		request.headers = {
			...( request.headers || {} ),
			'Cart-Hash': currentCartHash,
		};
	}
	return request;
};

/**
 * Middleware which appends the current cart hash to store API requests.
 *
 * @param {Object}   options Fetch options.
 * @param {Function} next    The next middleware or fetchHandler to call.
 * @return {*} The evaluated result of the remaining middleware chain.
 */
const storeCartHashMiddleware = ( options, next ) => {
	if ( isStoreApiRequest( options ) ) {
		options = appendCartHashHeader( options );
	}
	return next( options, next );
};

apiFetch.use( storeCartHashMiddleware );
apiFetch.setCartHash = setCartHash;
