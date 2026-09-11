/**
 * External dependencies
 */
import { date } from '@wordpress/date';

/**
 * Internal dependencies
 */
import type { SettingsRelativeDateValue, SettingsValue } from './types';

const STORE_LOCAL_DATETIME_FORMAT = 'Y-m-d\\TH:i:s';

export const isRelativeDateValue = (
	value: SettingsValue
): value is SettingsRelativeDateValue =>
	typeof value === 'object' &&
	value !== null &&
	! Array.isArray( value ) &&
	Object.prototype.hasOwnProperty.call( value, 'number' ) &&
	Object.prototype.hasOwnProperty.call( value, 'unit' );

export const areValuesEqual = ( a: SettingsValue, b: SettingsValue ) => {
	if ( Array.isArray( a ) || Array.isArray( b ) ) {
		return (
			Array.isArray( a ) &&
			Array.isArray( b ) &&
			a.length === b.length &&
			a.every( ( value, index ) => value === b[ index ] )
		);
	}

	if ( isRelativeDateValue( a ) || isRelativeDateValue( b ) ) {
		return (
			isRelativeDateValue( a ) &&
			isRelativeDateValue( b ) &&
			a.number === b.number &&
			a.unit === b.unit
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
