/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { error } from './diagnostics';
import type { SettingsUIField, SettingsValue } from './types';

type HiddenInput = {
	name: string;
	value: string;
};

const getFieldName = ( field: SettingsUIField ) => field.save?.name || field.id;

const getArrayFieldName = ( name: string ) =>
	name.endsWith( '[]' ) ? name : `${ name }[]`;

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
