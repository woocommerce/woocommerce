/**
 * External dependencies
 */
import { ChangeEvent } from 'react';
import { createContext, useContext } from '@wordpress/element';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type FormContext< Values extends Record< string, any > > = {
	values: Values;
	errors: Record< string, string >;
	touched: { [ P in keyof Values ]?: boolean | undefined };
	setTouched: React.Dispatch<
		React.SetStateAction< { [ P in keyof Values ]?: boolean | undefined } >
	>;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	setValue: ( name: string, value: any ) => void;
	handleSubmit: () => Promise< Values >;
	getInputProps< Value = string >(
		name: string
	): {
		value: Value;
		checked: boolean;
		selected: Value;
		onChange: ( value: ChangeEvent< HTMLInputElement > ) => void;
		onBlur: () => void;
		className: string | undefined;
		help: string | null;
	};
	isValidForm: boolean;
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
