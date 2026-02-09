/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { isStoreApiRequest } from './store-api-nonce';

/**
 * Middleware to add the '_locale=site' query parameter from API requests.
 *
 * TODO: Remove once https://github.com/WordPress/gutenberg/issues/16805 is fixed and replace by removing userLocaleMiddleware middleware.
 *
 * @param {Object}   options      Fetch options.
 * @param {Object}   options.url  The URL of the request.
 * @param {Object}   options.path The path of the request.
 * @param {Function} next         The next middleware or fetchHandler to call.
 * @return {*} The evaluated result of the remaining middleware chain.
 */
const removeUserLocaleMiddleware = (
	options: { url?: string; path?: string },
	next: ( options: { url?: string; path?: string } ) => Promise< unknown >
): Promise< unknown > => {
	if ( typeof options.url === 'string' && isStoreApiRequest( options ) ) {
		options.url = addQueryArgs( options.url, { _locale: 'site' } );
	}

	if ( typeof options.path === 'string' && isStoreApiRequest( options ) ) {
		options.path = addQueryArgs( options.path, { _locale: 'site' } );
	}

	return next( options );
};

apiFetch.use( removeUserLocaleMiddleware );
