/**
 * External dependencies
 */
import { ComboboxControl } from '@wordpress/components';

export type CustomFieldNameControlProps = Omit<
	ComboboxControl.Props,
	'options' | 'onFilterValueChange'
> &
	Omit<
		React.DetailedHTMLProps<
			React.InputHTMLAttributes< HTMLInputElement >,
			HTMLInputElement
		>,
		'className' | 'value' | 'onChange'
	>;
