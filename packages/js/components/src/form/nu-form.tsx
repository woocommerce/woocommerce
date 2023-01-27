/**
 * External dependencies
 */
import { useForm, UseFormReturn } from 'react-hook-form';
import { createContext, createElement, useContext } from 'react';

/**
 * Internal dependencies
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const FormContext2 = createContext(
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	{} as UseFormReturn< any, any >
);

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function useFormContext() {
	const formContext = useContext( FormContext2 );
	return formContext;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function NuForm( props: any ) {
	const methods = useForm( {
		defaultValues: props.initialValues,
	} );
	const compatibilityLayer = {
		get values() {
			return methods.getValues();
		},
		errors: {},
		touched: false,
		isDirty: false,
		setTouched: () => {},
		setValue: () => {},
		setValues: () => {},
		handleSubmit: () => {},
		getCheckboxControlProps: () => {},
		getInputProps: () => {},
		getSelectControlProps: () => {},
		isValidForm: true,
		resetForm: () => {},
	};
	return (
		<FormContext2.Provider value={ { ...compatibilityLayer, ...methods } }>
			{ props.children }
		</FormContext2.Provider>
	);
}

export { NuForm, useFormContext };
