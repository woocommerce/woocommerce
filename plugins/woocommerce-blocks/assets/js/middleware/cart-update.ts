/**
 * External dependencies
 */
import apiFetch, { APIFetchOptions } from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { LAST_CART_UPDATE_TIMESTAMP_KEY } from '../data/cart/constants';

/**
 * Checks if this request is a POST to the wc/store/cart endpoint.
 */
const isCartUpdatePostRequest = ( options: APIFetchOptions ) => {
	const url = options.url || options.path || '';
	const method = options.method || 'GET';

	// Return false if there is no endpoint provided, or the request is not a POST request.
	if ( ! url || method !== 'POST' ) {
		return false;
	}

	const cartRegExp = /wc\/store\/cart\//;
	const batchRegExp = /wc\/store\/batch/;

	const isCart = cartRegExp.exec( url ) !== null;
	const isBatch = batchRegExp.exec( url ) !== null;

	if ( isCart ) {
		return true;
	}

	if ( isBatch ) {
		const requests = options?.data?.requests || [];

		return requests.some( ( request: { path: string } ) => {
			const requestUrl = request.path || '';

			return cartRegExp.exec( requestUrl ) !== null;
		} );
	}

	return false;
};

/**
 * Middleware which saves the time that the cart was last modified in
 * the browser's Local Storage
 *
 * @param {Object}   options Fetch options.
 * @param {Function} next    The next middleware or fetchHandler to call.
 *
 * @return {*} The evaluated result of the remaining middleware chain.
 */
const cartUpdateMiddleware = (
	options: APIFetchOptions,
	/* eslint-disable @typescript-eslint/no-explicit-any */
	next: ( arg0: APIFetchOptions, arg1: any ) => any
) => {
	if ( isCartUpdatePostRequest( options ) ) {
		window.localStorage.setItem(
			LAST_CART_UPDATE_TIMESTAMP_KEY,
			( Date.now() / 1000 ).toString()
		);
	}
	return next( options, next );
};

apiFetch.use( cartUpdateMiddleware );
