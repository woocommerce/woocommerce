/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { error } from './diagnostics';
import type { SettingsUIField, SettingsValue } from './types';
import { areValuesEqual, toStoreLocalDateTime } from './values';

type HiddenInput = {
	name: string;
	value: string;
};

const getFieldName = ( field: SettingsUIField ) => field.save?.name ?? field.id;

const getArrayFieldName = ( name: string ) =>
	name.endsWith( '[]' ) ? name : `${ name }[]`;

const isSupportedFieldName = ( name: string, isArray: boolean ) => {
	const baseName =
		isArray && name.endsWith( '[]' ) ? name.slice( 0, -2 ) : name;

	// Accept a flat name or one bracketed segment. Array fields can also use a
	// trailing []. Keep this in sync with
	// SettingsUISchema::is_supported_form_post_name().
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
	value: SettingsValue,
	serializeDateTimeAsStoreLocal: boolean
): HiddenInput[] => {
	if ( field.type === 'checkbox' ) {
		// Accept the canonical boolean as well as the legacy truthy-string
		// forms ('yes'/'1') that the exported getHiddenInputs()/HiddenInputs()
		// API accepted before value canonicalization, so external callers
		// passing a classic checkbox value do not get it silently flipped off.
		const isChecked = value === true || value === 'yes' || value === '1';
		return [ { name, value: isChecked ? 'yes' : 'no' } ];
	}

	if ( field.type === 'array' ) {
		return toRepeatedInputs( name, Array.isArray( value ) ? value : [] );
	}

	let serializedValue = '';

	if ( field.type === 'datetime-local' && serializeDateTimeAsStoreLocal ) {
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

const handleUnsupportedField = (
	message: string,
	field: SettingsUIField,
	strict: boolean
): HiddenInput[] => {
	if ( strict ) {
		throw new Error( message );
	}

	error( message, { field } );
	return [];
};

export const getHiddenInputs = (
	field: SettingsUIField,
	value: SettingsValue,
	{
		initialCanonicalValue,
		serializeDateTimeAsStoreLocal = false,
		strict = false,
	}: {
		initialCanonicalValue?: SettingsValue;
		serializeDateTimeAsStoreLocal?: boolean;
		strict?: boolean;
	} = {}
): HiddenInput[] => {
	const adapter = field.save?.adapter || 'form_post';

	if ( adapter === 'none' ) {
		return [];
	}

	if ( adapter !== 'form_post' ) {
		return handleUnsupportedField(
			`Save adapter "${ adapter }" is not supported.`,
			field,
			strict
		);
	}

	const name = getFieldName( field );

	if ( strict && ! isSupportedFieldName( name, field.type === 'array' ) ) {
		return handleUnsupportedField(
			`Form-post field name "${ name }" is not supported.`,
			field,
			strict
		);
	}

	if (
		field.save &&
		Object.prototype.hasOwnProperty.call( field.save, 'initialValue' ) &&
		Array.isArray( field.save.initialValue ) &&
		field.type !== 'array'
	) {
		return handleUnsupportedField(
			`Field "${ field.id }" has a list initialValue but is not an array field.`,
			field,
			strict
		);
	}

	if (
		field.save &&
		Object.prototype.hasOwnProperty.call( field.save, 'initialValue' ) &&
		typeof initialCanonicalValue !== 'undefined' &&
		areValuesEqual( value, initialCanonicalValue )
	) {
		return serializeOriginalFormValue(
			name,
			field.save.initialValue as string | string[]
		);
	}

	return serializeCanonicalValue(
		field,
		name,
		value,
		serializeDateTimeAsStoreLocal
	);
};

export const HiddenInputs = ( {
	field,
	value,
	initialCanonicalValue,
	serializeDateTimeAsStoreLocal = false,
	strict = false,
}: {
	field: SettingsUIField;
	value: SettingsValue;
	initialCanonicalValue?: SettingsValue;
	serializeDateTimeAsStoreLocal?: boolean;
	strict?: boolean;
} ) => (
	<>
		{ getHiddenInputs( field, value, {
			initialCanonicalValue,
			serializeDateTimeAsStoreLocal,
			strict,
		} ).map( ( input, index ) => (
			<input
				key={ `${ input.name }-${ index }` }
				type="hidden"
				name={ input.name }
				value={ input.value }
			/>
		) ) }
	</>
);
