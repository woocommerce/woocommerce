/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useSettingsUIContext } from './settings-ui-context';
import type {
	SettingsEditControl,
	SettingsFieldComponent,
	SettingsUIField,
	SettingsValue,
} from './types';

const toLegacyValue = ( value: SettingsValue | undefined ): SettingsValue =>
	typeof value === 'undefined' ? '' : value;

/** Adapt a 10.9 field component to the modern edit-control boundary. */
export const createLegacyEditControl = (
	LegacyComponent: SettingsFieldComponent,
	settingsField: SettingsUIField
): SettingsEditControl => {
	return function LegacySettingsEditControl( { data, field, onChange } ) {
		const { context, initialValues } = useSettingsUIContext();
		const value = toLegacyValue( field.getValue( { item: data } ) );

		return (
			<LegacyComponent
				field={ settingsField }
				value={ value }
				onChange={ ( nextValue ) =>
					onChange( { [ settingsField.id ]: nextValue } )
				}
				values={ data }
				initialValues={ initialValues }
				setValue={ ( fieldId, nextValue ) =>
					onChange( { [ fieldId ]: nextValue } )
				}
				setValues={ ( nextValues ) => onChange( nextValues ) }
				context={ context }
			/>
		);
	};
};
