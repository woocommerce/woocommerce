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
	type?: string | undefined;
	// Class name to add to the input.
	className?: string | undefined;
	// aria-describedby attribute to add to the input.
	ariaDescribedBy?: string | undefined;
	// id to use for the error message. If not provided, an id will be generated.
	errorId?: string;
	// Feedback to display alongside the input. May be hidden when validation errors are displayed.
	feedback?: JSX.Element | null;
	// if true, the input will be focused on mount.
	focusOnMount?: boolean;
	// Callback to run on change which is passed the updated value.
	onChange: ( newValue: string ) => void;
	// Optional label for the field.
	label?: string | undefined;
	// Field value.
	value?: string | undefined;
	// If true, validation errors will be shown.
	showError?: boolean;
	// Error message to display alongside the field regardless of validation.
	errorMessage?: string | undefined;
	// Custom validation function that is run on change. Use setCustomValidity to set an error message.
	customValidation?:
		| ( ( inputObject: HTMLInputElement ) => boolean )
		| undefined;
	// Custom validation message to display when validity is false. Given the input element. Expected to use inputObject.validity.
	customValidityMessage?: ( validity: ValidityState ) => undefined | string;
	// Custom formatted to format values as they are typed.
	customFormatter?: ( value: string ) => string;
	// Whether validation should run when focused - only has an effect when focusOnMount is also true.
	validateOnMount?: boolean | undefined;
}
