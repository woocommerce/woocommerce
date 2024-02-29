/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';

export function* validate( jsonString ) {
	try {
		const response = yield apiFetch( {
			method: 'POST',
			path: `${ API_NAMESPACE }/remote-spec-validator/validate`,
			headers: { 'content-type': 'application/json' },
			data: {
				spec: JSON.stringify( JSON.parse( jsonString ) ),
			},
		} );
		if ( response.valid ) {
			yield {
				type: TYPES.SET_MESSAGE,
				message: {
					type: 'notice notice-success',
					text: 'Validation passed',
				},
			};
		} else {
			yield {
				type: TYPES.SET_MESSAGE,
				message: {
					type: 'error',
					text: 'Validation failed',
				},
			};
		}
	} catch {
		throw new Error();
	}
}

export function setMessage( type, text ) {
	return {
		type: TYPES.SET_MESSAGE,
		message: {
			type,
			text,
		},
	};
}
