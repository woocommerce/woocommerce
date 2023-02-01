/**
 * External dependencies
 */
import { useForm, FormProvider, useFormContext } from 'react-hook-form';
import { createElement } from 'react';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function NuForm( props: any ) {
	const methods = useForm( {
		defaultValues: props.initialValues,
	} );
	return <FormProvider { ...methods }>{ props.children }</FormProvider>;
}

export { NuForm, useFormContext as useFormContext2 };
