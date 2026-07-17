/**
 * External dependencies
 */
import { dateI18n, getDate } from '@wordpress/date';
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { error } from './diagnostics';
import type { SettingsUIField, SettingsValue } from './types';
import { areValuesEqual, isCheckedValue, toStringValue } from './values';

type HiddenInput = {
	name: string;
	value: string;
};

const getFieldName = ( field: SettingsUIField ) => field.save?.name || field.id;

const getArrayFieldName = ( name: string ) =>
	name.endsWith( '[]' ) ? name : `${ name }[]`;

const getArrayInputs = ( name: string, values: string[] ): HiddenInput[] =>
	values.map( ( value ) => ( {
		name: getArrayFieldName( name ),
		value,
	} ) );

const getInitialInputs = (
	name: string,
	initialValue: string | string[]
): HiddenInput[] =>
	Array.isArray( initialValue )
		? getArrayInputs( name, initialValue )
		: [ { name, value: initialValue } ];

const toLocalDatetime = ( value: SettingsValue ) => {
	if ( typeof value !== 'string' || ! value ) {
		return '';
	}

	const date = getDate( value );
	const seconds = dateI18n( 's', date );
	return dateI18n( seconds === '00' ? 'Y-m-d\\TH:i' : 'Y-m-d\\TH:i:s', date );
};

export const getHiddenInputs = (
	field: SettingsUIField,
	value: SettingsValue
): HiddenInput[] => {
	const adapter = field.save?.adapter || 'form_post';

	if ( adapter === 'none' ) {
		return [];
	}

	if ( adapter !== 'form_post' ) {
		error( `Save adapter "${ adapter }" is not supported.`, { field } );
		return [];
	}

	const name = getFieldName( field );
	const initialValue = field.save?.initialValue;

	if (
		typeof initialValue !== 'undefined' &&
		typeof field.value !== 'undefined' &&
		areValuesEqual( value, field.value )
	) {
		return getInitialInputs( name, initialValue );
	}

	if ( field.type === 'checkbox' ) {
		return [
			{
				name,
				value: isCheckedValue( value ) ? 'yes' : 'no',
			},
		];
	}

	if ( field.type === 'array' ) {
		return getArrayInputs(
			name,
			( Array.isArray( value ) ? value : [] ).map( String )
		);
	}

	return [
		{
			name,
			value:
				field.type === 'datetime-local'
					? toLocalDatetime( value )
					: toStringValue( value ),
		},
	];
};

export const HiddenInputs = ( {
	field,
	value,
}: {
	field: SettingsUIField;
	value: SettingsValue;
} ) => (
	<>
		{ getHiddenInputs( field, value ).map( ( input, index ) => (
			<input
				key={ `${ input.name }-${ index }` }
				type="hidden"
				name={ input.name }
				value={ input.value }
			/>
		) ) }
	</>
);
