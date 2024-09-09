/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs, hasQueryArg } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { isStoreApiRequest } from './store-api-nonce';

/**
 * Middleware to remove the '_locale=user' query parameter from API requests.
 *
 * @param {Object}   options Fetch options.
 * @param {Function} next    The next middleware or fetchHandler to call.
 * @return {*} The evaluated result of the remaining middleware chain.
 */
const removeUserLocaleMiddleware = ( options, next ) => {
	if (
		typeof options.url === 'string' &&
		! hasQueryArg( options.url, '_locale' ) &&
		isStoreApiRequest( options )
	) {
		options.url = addQueryArgs( options.url, { _locale: 'site' } );
	}

	if (
		typeof options.path === 'string' &&
		! hasQueryArg( options.path, '_locale' ) &&
		isStoreApiRequest( options )
	) {
		options.path = addQueryArgs( options.path, { _locale: 'site' } );
	}

	return next( options );
};

apiFetch.use( removeUserLocaleMiddleware );
