/**
 * External dependencies
 */
import { RadioControl } from '@wordpress/components';

export type RadioFieldProps = Omit<
	typeof RadioControl,
	'label' | 'value' | 'options'
> & {
	title: string;
	description?: string;
	disabled?: boolean;
	className?: string;
	onChange( value: string ): void;
	selected?: string;
	options: {
		label: string;
		value: string;
	}[];
};
