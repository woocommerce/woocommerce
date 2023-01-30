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
 * @param {Array} filters
 */
export function setFilters( filters ) {
	return {
		type: TYPES.SET_FILTERS,
		filters,
	};
}

export function setLoadingState( isLoading ) {
	return {
		type: TYPES.SET_IS_LOADING,
		isLoading,
	};
}

export function* toggleFilter( index ) {
	try {
		yield apiFetch( {
			method: 'POST',
			path: `${ API_NAMESPACE }/rest-api-filters/${ index }/toggle`,
			headers: { 'content-type': 'application/json' },
		} );
		yield {
			type: TYPES.TOGGLE_FILTER,
			index,
		};
	} catch {
		throw new Error();
	}
}

export function* deleteFilter( index ) {
	try {
		yield apiFetch( {
			method: 'DELETE',
			path: `${ API_NAMESPACE }/rest-api-filters/`,
			headers: { 'content-type': 'application/json' },
			body: JSON.stringify( {
				index,
			} ),
		} );

		yield {
			type: TYPES.DELETE_FILTER,
			index,
		};
	} catch {
		throw new Error();
	}
}

export function* saveFilter( endpoint, dotNotation, replacement ) {
	try {
		yield apiFetch( {
			method: 'POST',
			path: API_NAMESPACE + '/rest-api-filters',
			headers: { 'content-type': 'application/json' },
			body: JSON.stringify( {
				endpoint,
				dot_notation: dotNotation,
				replacement,
			} ),
		} );

		yield {
			type: TYPES.SAVE_FILTER,
			filter: {
				endpoint,
				dot_notation: dotNotation,
				replacement,
				enabled: true,
			},
		};
	} catch {
		throw new Error();
	}
}
