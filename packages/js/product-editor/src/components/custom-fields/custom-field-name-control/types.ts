/**
 * External dependencies
 */
import { ComboboxControl } from '@wordpress/components';

export type CustomFieldNameControlProps = ComboboxControl.Props &
	Omit<
		React.DetailedHTMLProps<
			React.InputHTMLAttributes< HTMLInputElement >,
			HTMLInputElement
		>,
		'className' | 'value' | 'onChange'
	>;
