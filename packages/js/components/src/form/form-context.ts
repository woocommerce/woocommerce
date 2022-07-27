/**
 * External dependencies
 */
import { ChangeEvent } from 'react';
import { createContext, useContext } from '@wordpress/element';

export type FormContext< Values > = {
	values: Values;
	errors: Record< string, string >;
	touched: { [ P in keyof Values ]?: boolean | undefined };
	setTouched: React.Dispatch<
		React.SetStateAction< { [ P in keyof Values ]?: boolean | undefined } >
	>;
	setValue: ( name: string, value: unknown ) => void;
	handleSubmit: () => Promise< Values >;
	getInputProps: ( name: string ) => {
		value: string;
		checked: boolean;
		selected: string;
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

export function useFormContext< Values >() {
	const formik = useContext< FormContext< Values > >( FormContext );

	return formik;
}
