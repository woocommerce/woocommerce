/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { DataFormControlProps } from '@wordpress/dataviews';
import { TextareaControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { DataFormItem } from '../../types';

export const getTextareaEdit =
	( help?: React.ReactNode ) =>
	( { field, onChange, data }: DataFormControlProps< DataFormItem > ) => {
		const { id, getValue, placeholder } = field;
		const label = field.label === id ? undefined : field.label;
		const value = getValue( { item: data } );

		return (
			<TextareaControl
				__nextHasNoMarginBottom
				help={ help }
				label={ label }
				placeholder={ placeholder }
				onChange={ ( newValue ) => {
					onChange( {
						[ id ]: newValue,
					} );
				} }
				value={ value }
				id={ id }
			/>
		);
	};
