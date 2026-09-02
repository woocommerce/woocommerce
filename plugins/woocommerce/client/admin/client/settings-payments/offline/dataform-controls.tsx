/**
 * External dependencies
 */
import {
	CheckboxControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import type { DataFormControlProps } from '@wordpress/dataviews';

/**
 * The shape of the form values used by the offline payment method settings forms.
 */
export type OfflineFormValues = Record< string, string | boolean | string[] >;

/**
 * A DataForm edit control that renders a `CheckboxControl`, preserving the
 * markup of the previous hand-rolled offline payment method forms.
 */
export const CheckboxEdit = ( {
	data,
	field,
	onChange,
}: DataFormControlProps< OfflineFormValues > ) => (
	<CheckboxControl
		label={ field.label }
		help={ field.description }
		checked={ Boolean( field.getValue( { item: data } ) ) }
		onChange={ ( checked ) => onChange( { [ field.id ]: checked } ) }
	/>
);

/**
 * A DataForm edit control that renders a `TextControl`, preserving the
 * markup of the previous hand-rolled offline payment method forms.
 */
export const TextEdit = ( {
	data,
	field,
	onChange,
}: DataFormControlProps< OfflineFormValues > ) => (
	<TextControl
		label={ field.label }
		help={ field.description }
		placeholder={ field.placeholder }
		value={ String( field.getValue( { item: data } ) ?? '' ) }
		onChange={ ( value ) => onChange( { [ field.id ]: value } ) }
	/>
);

/**
 * A DataForm edit control that renders a `TextareaControl`, preserving the
 * markup of the previous hand-rolled offline payment method forms.
 */
export const TextareaEdit = ( {
	data,
	field,
	onChange,
}: DataFormControlProps< OfflineFormValues > ) => (
	<TextareaControl
		label={ field.label }
		help={ field.description }
		value={ String( field.getValue( { item: data } ) ?? '' ) }
		onChange={ ( value ) => onChange( { [ field.id ]: value } ) }
	/>
);
