/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';
import { setOptions } from './actions';

export function* getOptions( search ) {
	let path = `${ API_NAMESPACE }/options?`;
	if ( search ) {
		path += `search=${ search }`;
	}

	try {
		const response = yield apiFetch( {
			path,
		} );
		yield setOptions( response );
	} catch ( error ) {
		throw new Error();
	}
}
