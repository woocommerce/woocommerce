/**
 * External dependencies
 */
import { ComboboxControl } from '@wordpress/components';

export type CustomFieldNameControlProps = Omit<
	ComboboxControl.Props,
	'options' | 'onFilterValueChange'
> &
	Pick<
		React.DetailedHTMLProps<
			React.InputHTMLAttributes< HTMLInputElement >,
			HTMLInputElement
		>,
		'onBlur'
	>;
