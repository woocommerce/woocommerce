/**
 * External dependencies
 */
import { deepSignal } from 'deepsignal';

const isObject = ( item ) =>
	item && typeof item === 'object' && ! Array.isArray( item );

export const deepMerge = ( target, source ) => {
	if ( isObject( target ) && isObject( source ) ) {
		for ( const key in source ) {
			if ( isObject( source[ key ] ) ) {
				if ( ! target[ key ] ) Object.assign( target, { [ key ]: {} } );
				deepMerge( target[ key ], source[ key ] );
			} else {
				Object.assign( target, { [ key ]: source[ key ] } );
			}
		}
	}
};

const rawState = {};
export const store = { state: deepSignal( rawState ) };

if ( typeof window !== 'undefined' ) window.wpx = store;

export const wpx = ( { state, ...block } ) => {
	deepMerge( store, block );
	deepMerge( rawState, state );
};
