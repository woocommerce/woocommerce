/**
 * External dependencies
 */
import memoize from 'memoize-one';

/**
 * Internal dependencies
 */
import { ProductFieldState } from './types';

export function getProductField( state: ProductFieldState, name: string ) {
	return state.fields[ name ] || null;
}

export const getRegisteredProductFields = memoize(
	( state: ProductFieldState ) => Object.keys( state.fields ),
	( [ newState ], [ oldState ] ) => {
		return newState.fields === oldState.fields;
	}
);
