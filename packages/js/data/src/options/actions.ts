/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { WC_ADMIN_NAMESPACE } from '../constants';
import { Options } from './types';

export function receiveOptions( options: Options ) {
	return {
		type: TYPES.RECEIVE_OPTIONS,
		options,
	};
}

export function setRequestingError( error: unknown, name: string ) {
	return {
		type: TYPES.SET_REQUESTING_ERROR,
		error,
		name,
	};
}

export function setUpdatingError( error: unknown ) {
	return {
		type: TYPES.SET_UPDATING_ERROR,
		error,
	};
}

export function setIsUpdating( isUpdating: boolean ) {
	return {
		type: TYPES.SET_IS_UPDATING,
		isUpdating,
	};
}

export function* updateOptions( data: Options ) {
	yield setIsUpdating( true );
	yield receiveOptions( data );

	try {
		const results: unknown = yield apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/options',
			method: 'POST',
			data,
		} );

		yield setIsUpdating( false );

		if ( typeof results !== 'object' ) {
			throw new Error(
				`Invalid update options response from server: ${ results }`
			);
		}
		return { success: true, ...results };
	} catch ( error ) {
		yield setUpdatingError( error );
		if ( typeof error !== 'object' ) {
			throw new Error( `Unexpected error: ${ error }` );
		}
		return { success: false, ...error };
	}
}

export type Action = ReturnType<
	| typeof receiveOptions
	| typeof setRequestingError
	| typeof setUpdatingError
	| typeof setIsUpdating
>;
