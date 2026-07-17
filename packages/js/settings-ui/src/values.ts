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

// Mirrors wc_string_to_bool(): every value PHP treats as true must
// render checked, or an untouched save would flip the setting off.
export const isCheckedValue = ( value: SettingsValue | undefined ): boolean => {
	if ( typeof value === 'boolean' ) {
		return value;
	}

	if ( value === 1 ) {
		return true;
	}

	return (
		typeof value === 'string' &&
		[ 'yes', 'true', '1' ].includes( value.toLowerCase() )
	);
};
