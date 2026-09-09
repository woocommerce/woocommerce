/**
 * External dependencies
 */
import { date } from '@wordpress/date';

/**
 * Internal dependencies
 */
import type { SettingsValue } from './types';

const STORE_LOCAL_DATETIME_FORMAT = 'Y-m-d\\TH:i:s';

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

export const valueMatchesVisibilityRule = (
	value: SettingsValue,
	expected: SettingsValue | SettingsValue[] | undefined
) => {
	const expectedValues = Array.isArray( expected )
		? expected
		: [ expected === undefined ? true : expected ];

	return expectedValues.some( ( expectedValue ) =>
		areValuesEqual( value, expectedValue )
	);
};

export const toStoreLocalDateTime = ( value: SettingsValue ) => {
	if ( typeof value !== 'string' || value === '' ) {
		return '';
	}

	return date( STORE_LOCAL_DATETIME_FORMAT, value );
};
