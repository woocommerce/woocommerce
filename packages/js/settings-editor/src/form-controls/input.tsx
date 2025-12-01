/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { DataFormControlProps } from '@wordpress/dataviews';
import { __experimentalInputControl as InputControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { DataFormItem } from '../types';

type InputProps = DataFormControlProps< DataFormItem > & {
	type: React.HTMLInputTypeAttribute;
	help?: React.ReactNode;
};

export const Input = ( { field, onChange, data, help, type }: InputProps ) => {
	const { id, getValue, placeholder } = field;
	const value = getValue( { item: data } );

	// DataForm will automatically use the id as the label if no label is provided so we conditionally set the label to undefined if it matches the id to avoid displaying it.
	// We should contribute upstream to allow label to be optional.
	const label = field.label === id ? undefined : field.label;

	return (
		<InputControl
			id={ id }
			label={ label }
			type={ type }
			value={ value }
			help={ help }
			placeholder={ placeholder }
			onChange={ ( newValue ) => {
				onChange( {
					[ id ]: newValue,
				} );
			} }
		/>
	);
};
