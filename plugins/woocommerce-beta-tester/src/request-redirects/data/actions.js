/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';

/**
 * Initialize the state
 *
 * @param {Array} redirectors
 */
export function setRedirectors( redirectors ) {
	return {
		type: TYPES.SET_REDIRECTORS,
		redirectors,
	};
}

export function setLoadingState( isLoading ) {
	return {
		type: TYPES.SET_IS_LOADING,
		isLoading,
	};
}

export function* toggleRedirector( index ) {
	try {
		yield apiFetch( {
			method: 'POST',
			path: `${ API_NAMESPACE }/request-redirects/${ index }/toggle`,
			headers: { 'content-type': 'application/json' },
		} );
		yield {
			type: TYPES.TOGGLE_REDIRECTOR,
			index,
		};
	} catch {
		throw new Error();
	}
}

export function* deleteRedirector( index ) {
	try {
		yield apiFetch( {
			method: 'DELETE',
			path: `${ API_NAMESPACE }/request-redirects/`,
			headers: { 'content-type': 'application/json' },
			body: JSON.stringify( {
				index,
			} ),
		} );

		yield {
			type: TYPES.DELETE_REDIRECTOR,
			index,
		};
	} catch {
		throw new Error();
	}
}

export function* saveRedirector(
	originalEndpoint,
	newEndpoint,
	username = '',
	password = '',
	enabled = true,
	index = null
) {
	try {
		const redirector = {
			original_endpoint: originalEndpoint,
			new_endpoint: newEndpoint,
			username,
			password,
		};

		if ( index !== null ) {
			redirector.index = parseInt( index, 10 );
		}

		yield apiFetch( {
			method: 'POST',
			path: API_NAMESPACE + '/request-redirects',
			headers: { 'content-type': 'application/json' },
			body: JSON.stringify( redirector ),
		} );

		yield {
			type: TYPES.SAVE_REDIRECTOR,
			redirector,
		};
	} catch {
		throw new Error();
	}
}
