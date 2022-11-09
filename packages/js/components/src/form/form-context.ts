/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	CheckboxProps,
	ConsumerInputProps,
	InputProps,
	SelectControlProps,
} from './form';

export type FormErrors< Values > = {
	[ P in keyof Values ]?: FormErrors< Values[ P ] > | string;
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type FormContext< Values extends Record< string, any > > = {
	values: Values;
	errors: FormErrors< Values >;
	isDirty: boolean;
	touched: { [ P in keyof Values ]?: boolean | undefined };
	setTouched: React.Dispatch<
		React.SetStateAction< { [ P in keyof Values ]?: boolean | undefined } >
	>;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	setValue: ( name: string, value: any ) => void;
	setValues: ( valuesToSet: Values ) => void;
	handleSubmit: () => Promise< Values >;
	getCheckboxControlProps< Value extends Values[ keyof Values ] >(
		name: string,
		inputProps?: ConsumerInputProps< Values >
	): CheckboxProps< Values, Value >;
	getSelectControlProps< Value extends Values[ keyof Values ] >(
		name: string,
		inputProps?: ConsumerInputProps< Values >
	): SelectControlProps< Values, Value >;
	getInputProps< Value extends Values[ keyof Values ] >(
		name: string,
		inputProps?: ConsumerInputProps< Values >
	): InputProps< Values, Value >;
	isValidForm: boolean;
	resetForm: (
		initialValues: Values,
		touchedFields?: { [ P in keyof Values ]?: boolean | undefined },
		errors?: FormErrors< Values >
	) => void;
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const FormContext = createContext< FormContext< any > >(
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	{} as FormContext< any >
);

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function useFormContext< Values extends Record< string, any > >() {
	const formContext = useContext< FormContext< Values > >( FormContext );

	return formContext;
}
