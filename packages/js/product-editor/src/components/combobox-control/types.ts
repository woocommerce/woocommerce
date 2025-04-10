/**
 * External dependencies
 */
import { ComboboxControl as Combobox } from '@wordpress/components';

export type ComboboxControlOption = {
	label: string;
	value: string;
};

export type ComboboxControlProps = React.ComponentProps< typeof Combobox > &
	Pick<
		React.DetailedHTMLProps<
			React.InputHTMLAttributes< HTMLInputElement >,
			HTMLInputElement
		>,
		'id' | 'name' | 'onBlur'
	>;
