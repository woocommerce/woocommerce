/**
 * External dependencies
 */
import type { InputHTMLAttributes } from 'react';

export interface ValidatedTextInputProps
	extends Omit<
		InputHTMLAttributes< HTMLInputElement >,
		'onChange' | 'onBlur'
	> {
	// id to use for the input. If not provided, an id will be generated.
	id?: string;
	// Unique instance ID. id will be used instead if provided.
	instanceId?: string | undefined;
	// Type of input, defaults to text.
	type?: string;
	// Class name to add to the input.
	className?: string | undefined;
	// aria-describedby attribute to add to the input.
	ariaDescribedBy?: string | undefined;
	// id to use for the error message. If not provided, an id will be generated.
	errorId?: string;
	// if true, the input will be focused on mount.
	focusOnMount?: boolean;
	// Callback to run on change which is passed the updated value.
	onChange: ( newValue: string ) => void;
	// Optional label for the field.
	label?: string | undefined;
	// Field value.
	value: string;
	// If true, validation errors will be shown.
	showError?: boolean;
	// Error message to display alongside the field regardless of validation.
	errorMessage?: string | undefined;
	// Custom validation function that is run on change. Use setCustomValidity to set an error message.
	customValidation?:
		| ( ( inputObject: HTMLInputElement ) => boolean )
		| undefined;
	// Custom formatted to format values as they are typed.
	customFormatter?: ( value: string ) => string;
	// Whether validation should run when focused - only has an effect when focusOnMount is also true.
	validateOnMount?: boolean | undefined;
}
