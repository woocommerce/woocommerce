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
 * @param {Array} options
 */
export function setOptions( options ) {
	return {
		type: TYPES.SET_OPTIONS,
		options,
	};
}

export function* deleteOptionById( optionId ) {
	try {
		yield apiFetch( {
			method: 'DELETE',
			path: `${ API_NAMESPACE }/options/${ optionId }`,
		} );
		yield {
			type: TYPES.DELETE_OPTION_BY_ID,
			optionId,
		};
	} catch {
		throw new Error();
	}
}
