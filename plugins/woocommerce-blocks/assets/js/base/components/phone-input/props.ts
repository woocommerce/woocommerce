/**
 * External dependencies
 */
import type { InputHTMLAttributes } from 'react';

export interface PhoneInputProps
	extends Omit<
		InputHTMLAttributes< HTMLInputElement >,
		'onChange' | 'onBlur'
	> {
	country: string;
	id: string;
	value: string;
	ariaLabel?: string;
	label?: string | undefined;
	ariaDescribedBy?: string | undefined;
	screenReaderLabel?: string | undefined;
	help?: string;
	feedback?: JSX.Element | null;
	autoComplete?: string | undefined;
	onChange: ( newValue: string ) => void;
	onBlur?: ( newValue: string ) => void;
}
