/**
 * External dependencies
 */
import {
	useForm,
	UseFormReturn,
	FieldErrors,
	FieldValues,
	UseFormReset,
	FormProvider,
	useFormContext,
} from 'react-hook-form';
import { createContext, createElement, useContext } from 'react';

const FormContext2 = createContext(
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	{} as FormCompatibilityLayer< any >
);

interface FormCompatibilityLayer< Values extends FieldValues >
	extends UseFormReturn {
	errors: FieldErrors< Values >;
	isDirty: boolean;
	isValidForm: boolean;
	touched?: Record< keyof Values, boolean >;
	resetForm: UseFormReset< Values >;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function NuForm( props: any ) {
	const methods = useForm( {
		defaultValues: props.initialValues,
	} );
	return <FormProvider { ...methods }>{ props.children }</FormProvider>;
}

export { NuForm, useFormContext as useFormContext2 };
