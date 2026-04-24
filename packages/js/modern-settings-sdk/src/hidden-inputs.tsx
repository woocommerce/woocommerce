/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import type { ModernSettingsField, SettingsValue } from './types';

type Values = Record< string, SettingsValue >;

type HiddenInput = {
	name: string;
	value: string;
};

const getFieldName = ( field: ModernSettingsField ) =>
	field.save?.name || field.id;

const getArrayFieldName = ( name: string ) =>
	name.endsWith( '[]' ) ? name : `${ name }[]`;

export const getHiddenInputs = (
	field: ModernSettingsField,
	value: SettingsValue
): HiddenInput[] => {
	const adapter = field.save?.adapter || 'form_post';

	if ( adapter === 'none' ) {
		return [];
	}

	if ( adapter !== 'form_post' ) {
		warn( `Save adapter "${ adapter }" is not supported.`, { field } );
		return [];
	}

	const name = getFieldName( field );

	if ( field.type === 'checkbox' ) {
		return [
			{
				name,
				value:
					value === true || value === 'yes' || value === '1'
						? 'yes'
						: 'no',
			},
		];
	}

	if ( field.type === 'array' ) {
		return ( Array.isArray( value ) ? value : [] ).map( ( item ) => ( {
			name: getArrayFieldName( name ),
			value: String( item ),
		} ) );
	}

	return [
		{
			name,
			value:
				value === null || typeof value === 'undefined'
					? ''
					: String( value ),
		},
	];
};

export const getHiddenInputsForField = (
	field: ModernSettingsField,
	values: Values
): HiddenInput[] => {
	let inputs: HiddenInput[] = [];

	if ( ! field.fields || field.fields.length === 0 ) {
		inputs = getHiddenInputs( field, values[ field.id ] );
	} else if ( field.save?.adapter === 'form_post' ) {
		inputs = getHiddenInputs( field, values[ field.id ] );
	}

	return [
		...inputs,
		...( field.fields || [] ).flatMap( ( childField ) =>
			getHiddenInputsForField( childField, values )
		),
	];
};

export const HiddenInputs = ( {
	field,
	value,
	values,
}: {
	field: ModernSettingsField;
	value: SettingsValue;
	values?: Values;
} ) => (
	<>
		{ ( values
			? getHiddenInputsForField( field, values )
			: getHiddenInputs( field, value )
		).map( ( input, index ) => (
			<input
				key={ `${ input.name }-${ index }` }
				type="hidden"
				name={ input.name }
				value={ input.value }
			/>
		) ) }
	</>
);
