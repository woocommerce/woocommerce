/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import type { DataFormControlProps } from '@wordpress/dataviews';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { DataFormItem } from '../../types';

export const getRadioEdit =
	( help?: React.ReactNode ) =>
	( { field, onChange, data }: DataFormControlProps< DataFormItem > ) => {
		const { id, getValue, elements } = field;
		const value = getValue( { item: data } );
		const label = field.label === id ? undefined : field.label;

		return (
			<>
				<RadioControl
					help={ help }
					label={ label }
					onChange={ ( newValue ) => {
						onChange( {
							[ id ]: newValue,
						} );
					} }
					options={ elements }
					selected={ value }
				/>
				<input type="hidden" name={ id } value={ value } />
			</>
		);
	};
