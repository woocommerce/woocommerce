/**
 * Internal dependencies
 */
import type { SettingsValue } from './types';

export const toStringValue = ( value: SettingsValue | undefined ) =>
	value === null || typeof value === 'undefined' ? '' : String( value );

export const areValuesEqual = ( a: SettingsValue, b: SettingsValue ) => {
	if ( Array.isArray( a ) || Array.isArray( b ) ) {
		return (
			Array.isArray( a ) &&
			Array.isArray( b ) &&
			a.length === b.length &&
			a.every( ( value, index ) => value === b[ index ] )
		);
	}

	return a === b;
};
