/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { fromLocalDatetime } from './hidden-inputs';
import { useSettingsUIContext } from './settings-ui-context';
import type {
	SettingsEditControl,
	SettingsFieldComponent,
	SettingsUIField,
	SettingsUISchema,
	SettingsValue,
	SettingsValues,
} from './types';

const toLegacyValue = ( value: SettingsValue | undefined ): SettingsValue =>
	typeof value === 'undefined' ? '' : value;

const findField = ( schema: SettingsUISchema, fieldId: string ) => {
	for ( const group of Object.values( schema.groups ) ) {
		const field = group.fields.find(
			( candidate ) => candidate.id === fieldId
		);
		if ( field ) {
			return field;
		}
	}

	return undefined;
};

const toCanonicalValue = (
	value: SettingsValue,
	field?: SettingsUIField
): SettingsValue => {
	if ( ! field ) {
		return value;
	}

	if ( field.type === 'checkbox' && typeof value === 'string' ) {
		const normalized = value.toLowerCase();
		if ( [ 'yes', 'true', '1' ].includes( normalized ) ) {
			return true;
		}
		if ( [ '', 'no', 'false', '0' ].includes( normalized ) ) {
			return false;
		}
	}

	if ( field.type === 'number' || field.type === 'integer' ) {
		if ( value === '' || value === null ) {
			return null;
		}
		if ( typeof value === 'string' ) {
			const number = Number( value );
			return Number.isFinite( number ) ? number : value;
		}
	}

	if ( field.type === 'datetime-local' ) {
		if ( value === '' || value === null ) {
			return null;
		}
		if (
			typeof value === 'string' &&
			/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?$/.test( value )
		) {
			try {
				return fromLocalDatetime( value );
			} catch {
				return value;
			}
		}
	}

	return value;
};

const toCanonicalValues = (
	values: Partial< SettingsValues >,
	schema: SettingsUISchema
): Partial< SettingsValues > =>
	Object.fromEntries(
		Object.entries( values ).map( ( [ fieldId, value ] ) => [
			fieldId,
			typeof value === 'undefined'
				? value
				: toCanonicalValue( value, findField( schema, fieldId ) ),
		] )
	);

/** Adapt a 10.9 field component to the modern edit-control boundary. */
export const createLegacyEditControl = (
	LegacyComponent: SettingsFieldComponent,
	settingsField: SettingsUIField
): SettingsEditControl => {
	return function LegacySettingsEditControl( { data, field, onChange } ) {
		const { context, initialValues, schema } = useSettingsUIContext();
		const value = toLegacyValue( field.getValue( { item: data } ) );

		return (
			<LegacyComponent
				field={ settingsField }
				value={ value }
				onChange={ ( nextValue ) =>
					onChange( {
						[ settingsField.id ]: toCanonicalValue(
							nextValue,
							settingsField
						),
					} )
				}
				values={ data }
				initialValues={ initialValues }
				setValue={ ( fieldId, nextValue ) =>
					onChange( {
						[ fieldId ]: toCanonicalValue(
							nextValue,
							findField( schema, fieldId )
						),
					} )
				}
				setValues={ ( nextValues ) =>
					onChange( toCanonicalValues( nextValues, schema ) )
				}
				context={ context }
			/>
		);
	};
};
