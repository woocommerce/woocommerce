/**
 * External dependencies
 */
import {
	useForm,
	UseFormReturn,
	FieldErrors,
	FieldValues,
	UseFormReset,
} from 'react-hook-form';
import { createContext, createElement, useContext } from 'react';

/**
 * Internal dependencies
 */
const FormContext2 = createContext(
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	{} as FormCompatibilityLayer< any >
);

interface FormCompatibilityLayer< Values extends FieldValues >
	extends UseFormReturn {
	values: Values;
	errors: FieldErrors< Values >;
	isDirty: boolean;
	isValidForm: boolean;
	touched: boolean;
	resetForm: UseFormReset< Values >;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function useFormContext< Values extends FieldValues >() {
	const formContext =
		useContext< FormCompatibilityLayer< Values > >( FormContext2 );
	return formContext;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function NuForm( props: any ) {
	const methods = useForm( {
		defaultValues: props.initialValues,
	} );
	return (
		<FormContext2.Provider
			value={ {
				...{
					get values() {
						return methods.getValues();
					},
					get errors() {
						return methods.formState.errors;
					},
					get touched() {
						return (
							Object.values( methods.formState.touchedFields )
								.length > 0
						);
					},
					get isValidForm() {
						return methods.formState.isValid;
					},
					get isDirty() {
						return methods.formState.isDirty;
					},
					getCheckboxControlProps: () => {},
					getInputProps: () => {},
					getSelectControlProps: () => {},
					resetForm: ( values ) => {
						methods.reset( values );
					},
				},
				...methods,
			} }
		>
			{ props.children }
		</FormContext2.Provider>
	);
}

export { NuForm, useFormContext };
