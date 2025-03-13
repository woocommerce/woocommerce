/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';
import type { DataFormControlProps } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { DataFormItem } from '../../types';

export const getCheckboxEdit =
	( help?: React.ReactNode ) =>
	( { field, onChange, data }: DataFormControlProps< DataFormItem > ) => {
		const { id, getValue } = field;

		// DataForm will automatically use the id as the label if no label is provided so we conditionally set the label to undefined if it matches the id to avoid displaying it.
		// We should contribute upstream to allow label to be optional.
		const label = field.label === id ? undefined : field.label;
		const value = getValue( { item: data } );
		const hiddenValue = value === 'yes' ? '1' : '0';

		return (
			<>
				<CheckboxControl
					__nextHasNoMarginBottom={ true }
					label={ label }
					help={ help }
					checked={ value === 'yes' }
					onChange={ ( checked ) => {
						onChange( {
							[ id ]: checked ? 'yes' : 'no',
						} );
					} }
				/>
				<input type="hidden" name={ id } value={ hiddenValue } />
			</>
		);
	};
