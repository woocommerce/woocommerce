/**
 * External dependencies
 */
import { ChangeEvent } from 'react';
import { createContext, useContext } from '@wordpress/element';

export type FormErrors< Values > = {
	[ P in keyof Values ]?: FormErrors< Values[ P ] > | string;
};

export type InputProps< Value > = {
	value: Value;
	checked: boolean;
	selected?: boolean;
	onChange: ( value: ChangeEvent< HTMLInputElement > | Value ) => void;
	onBlur: () => void;
	className: string | undefined;
	help: string | null | undefined;
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
	getInputProps< Value extends Values[ keyof Values ] >(
		name: string
	): InputProps< Value >;
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
	const formik = useContext< FormContext< Values > >( FormContext );

	return formik;
}
