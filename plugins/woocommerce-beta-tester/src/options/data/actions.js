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

export function setLoadingState( isLoading ) {
	return {
		type: TYPES.SET_IS_LOADING,
		isLoading,
	};
}

export function setOptionForEditing( editingOption ) {
	return {
		type: TYPES.SET_OPTION_FOR_EDITING,
		editingOption,
	};
}

export function setNotice( notice ) {
	return {
		type: TYPES.SET_NOTICE,
		notice,
	};
}

export function* deleteOption( optionName ) {
	yield apiFetch( {
		method: 'DELETE',
		path: `${ API_NAMESPACE }/options/${ optionName }`,
	} );
	yield {
		type: TYPES.DELETE_OPTION,
		optionName,
	};
}

export function* saveOption( optionName, newOptionValue ) {
	try {
		const payload = {};
		try {
			// If the option value is a JSON string, parse it.
			payload[ optionName ] = JSON.parse( newOptionValue );
		} catch ( error ) {
			// If it's not a JSON string, just use the value as is.
			payload[ optionName ] = newOptionValue;
		}
		yield apiFetch( {
			method: 'POST',
			path: '/wc-admin/options',
			headers: { 'content-type': 'application/json' },
			body: JSON.stringify( payload ),
		} );
		yield setNotice( {
			status: 'success',
			message: optionName + ' has been saved.',
		} );
	} catch ( error ) {
		yield setNotice( {
			status: 'error',
			message: 'Unable to save ' + optionName,
		} );
		throw error;
	}
}
