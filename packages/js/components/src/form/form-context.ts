/**
 * External dependencies
 */
import { ChangeEvent } from 'react';
import { createContext, useContext } from '@wordpress/element';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type FormContext< Values extends Record< string, any > > = {
	values: Values;
	errors: {
		[ P in keyof Values ]?: string;
	};
	isDirty: boolean;
	touched: { [ P in keyof Values ]?: boolean | undefined };
	changedFields: { [ P in keyof Values ]?: boolean | undefined };
	setTouched: React.Dispatch<
		React.SetStateAction< { [ P in keyof Values ]?: boolean | undefined } >
	>;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	setValue: ( name: string, value: any ) => void;
	setValues: ( valuesToSet: Values ) => void;
	handleSubmit: () => Promise< Values >;
	getInputProps< Value extends Values[ keyof Values ] >(
		name: string
	): {
		value: Value;
		checked: boolean;
		selected?: boolean;
		onChange: ( value: ChangeEvent< HTMLInputElement > | Value ) => void;
		onBlur: () => void;
		className: string | undefined;
		help: string | null | undefined;
	};
	isValidForm: boolean;
	resetForm: (
		initialValues: Values,
		changedFields?: { [ P in keyof Values ]?: boolean | undefined },
		touchedFields?: { [ P in keyof Values ]?: boolean | undefined },
		errors?: { [ P in keyof Values ]?: string }
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
