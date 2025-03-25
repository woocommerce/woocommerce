/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import type { DataFormControlProps } from '@wordpress/dataviews';
import { ColorPicker } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { DataFormItem } from '../../types';

export const getColorEdit = ( {
	field,
	onChange,
	data,
}: DataFormControlProps< DataFormItem > ) => {
	const { id, getValue, label } = field;
	const value = getValue( { item: data } );

	return (
		<Fragment>
			{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
			<label
				className="woocommerce-settings-color-picker__label"
				htmlFor={ id }
				dangerouslySetInnerHTML={ { __html: label } }
			/>
			<ColorPicker
				className="woocommerce-settings-color-picker"
				onChange={ ( newValue ) => {
					onChange( {
						[ id ]: newValue,
					} );
				} }
				color={ value }
			/>
			<input type="hidden" name={ id } value={ value } />
		</Fragment>
	);
};
