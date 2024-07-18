/**
 * External dependencies
 */
import { ComboboxControl as Combobox } from '@wordpress/components';

export type ComboboxControlProps = Combobox.Props &
	Pick<
		React.DetailedHTMLProps<
			React.InputHTMLAttributes< HTMLInputElement >,
			HTMLInputElement
		>,
		'id' | 'name' | 'onBlur'
	>;
