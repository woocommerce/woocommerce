/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { error } from './diagnostics';
import type { SettingsUIField, SettingsValue } from './types';
import { areSettingsValuesEqual, toStoreLocalDateTime } from './values';

type HiddenInput = {
	name: string;
	value: string;
};

const getFieldName = ( field: SettingsUIField ) => field.save?.name || field.id;

const getArrayFieldName = ( name: string ) =>
	name.endsWith( '[]' ) ? name : `${ name }[]`;

const isSupportedFieldName = ( name: string, isArray: boolean ) => {
	const baseName =
		isArray && name.endsWith( '[]' ) ? name.slice( 0, -2 ) : name;

	return /^[^\[\]]+(?:\[[^\[\]]+\])?$/.test( baseName );
};

const toRepeatedInputs = ( name: string, values: string[] ): HiddenInput[] =>
	values.map( ( item ) => ( {
		name: getArrayFieldName( name ),
		value: item,
	} ) );

const serializeCanonicalValue = (
	field: SettingsUIField,
	name: string,
	value: SettingsValue
): HiddenInput[] => {
	if ( field.type === 'checkbox' ) {
		return [ { name, value: value === true ? 'yes' : 'no' } ];
	}

	if ( field.type === 'array' ) {
		return toRepeatedInputs( name, Array.isArray( value ) ? value : [] );
	}

	let serializedValue = '';

	if ( field.type === 'datetime-local' ) {
		serializedValue = toStoreLocalDateTime( value );
	} else if ( value !== null && typeof value !== 'undefined' ) {
		serializedValue = String( value );
	}

	return [
		{
			name,
			value: serializedValue,
		},
	];
};

const serializeOriginalFormValue = (
	name: string,
	value: string | string[]
): HiddenInput[] =>
	Array.isArray( value )
		? toRepeatedInputs( name, value )
		: [ { name, value } ];

export const getHiddenInputs = (
	field: SettingsUIField,
	value: SettingsValue,
	initialCanonicalValue: SettingsValue = value
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

	if ( ! isSupportedFieldName( name, field.type === 'array' ) ) {
		error( `Form-post field name "${ name }" is not supported.`, {
			field,
		} );
		return [];
	}

	if (
		field.save &&
		Object.prototype.hasOwnProperty.call( field.save, 'initialValue' ) &&
		areSettingsValuesEqual( value, initialCanonicalValue )
	) {
		return serializeOriginalFormValue(
			name,
			field.save.initialValue as string | string[]
		);
	}

	return serializeCanonicalValue( field, name, value );
};

export const HiddenInputs = ( {
	field,
	value,
	initialCanonicalValue,
}: {
	field: SettingsUIField;
	value: SettingsValue;
	initialCanonicalValue?: SettingsValue;
} ) => (
	<>
		{ getHiddenInputs( field, value, initialCanonicalValue ).map(
			( input, index ) => (
				<input
					key={ `${ input.name }-${ index }` }
					type="hidden"
					name={ input.name }
					value={ input.value }
				/>
			)
		) }
	</>
);
