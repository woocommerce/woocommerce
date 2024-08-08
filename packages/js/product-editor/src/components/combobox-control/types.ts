/**
 * External dependencies
 */
import { ComboboxControl as Combobox } from '@wordpress/components';

export type ComboboxControlOption = {
	label: string;
	value: string;
};

export type ComboboxControlProps = Combobox.Props &
	Pick<
		React.DetailedHTMLProps<
			React.InputHTMLAttributes< HTMLInputElement >,
			HTMLInputElement
		>,
		'id' | 'name' | 'onBlur'
	> & {
		__experimentalRenderItem?: ( args: {
			item: ComboboxControlOption;
		} ) => string | JSX.Element;
	};
