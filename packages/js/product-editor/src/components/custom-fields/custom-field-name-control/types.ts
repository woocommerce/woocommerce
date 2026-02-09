/**
 * External dependencies
 */
import { ComboboxControl } from '@wordpress/components';

export type CustomFieldNameControlProps = Omit<
	React.ComponentProps< typeof ComboboxControl >,
	'options' | 'onFilterValueChange'
> &
	Pick<
		React.DetailedHTMLProps<
			React.InputHTMLAttributes< HTMLInputElement >,
			HTMLInputElement
		>,
		'onBlur'
	>;

export type CustomFieldName = {
	value: string;
	label: string;
};
