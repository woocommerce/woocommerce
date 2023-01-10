/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { ProductFieldState } from './types';

export function getProductField( state: ProductFieldState, name: string ) {
	return state.fields[ name ] || null;
}

export const getRegisteredProductFields = createSelector(
	( state: ProductFieldState ) => Object.keys( state.fields ),
	( state ) => [ state.fields ]
);
