/**
 * External dependencies
 */
import { date, getDate } from '@wordpress/date';

/**
 * Internal dependencies
 */
import type { SettingsValue } from './types';

const STORE_LOCAL_DATETIME_FORMAT = 'Y-m-d\\TH:i:s';

export const areSettingsValuesEqual = (
	left: SettingsValue,
	right: SettingsValue
) => {
	if ( Array.isArray( left ) || Array.isArray( right ) ) {
		return (
			Array.isArray( left ) &&
			Array.isArray( right ) &&
			left.length === right.length &&
			left.every( ( value, index ) => value === right[ index ] )
		);
	}

	return left === right;
};

export const toCanonicalNumberValue = (
	value: string,
	integerOnly = false
): number | null => {
	if ( value.trim() === '' ) {
		return null;
	}

	const numberValue = Number( value );

	if ( ! Number.isFinite( numberValue ) ) {
		return null;
	}

	if (
		( Number.isInteger( numberValue ) &&
			! Number.isSafeInteger( numberValue ) ) ||
		( integerOnly && ! Number.isInteger( numberValue ) )
	) {
		return null;
	}

	return numberValue;
};

export const toStoreLocalDateTime = ( value: SettingsValue ) => {
	if ( typeof value !== 'string' || value === '' ) {
		return '';
	}

	return date( STORE_LOCAL_DATETIME_FORMAT, value );
};

export const toCanonicalDateTime = ( value: string ): string | null => {
	if ( value === '' ) {
		return null;
	}

	return getDate( value ).toISOString().replace( '.000Z', 'Z' );
};
