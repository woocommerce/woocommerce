/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	RadioControl,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import {
	Form,
	DateTimePickerControl,
	NuForm,
	useFormContext2,
} from '@woocommerce/components';
import {
	useWatch,
	useController,
	useForm,
	FormProvider,
} from 'react-hook-form';
import moment from 'moment';

const validate = ( values ) => {
	const errors = {};
	if ( ! values.firstName ) {
		errors.firstName = 'First name is required';
	}
	if ( values.lastName.length < 3 ) {
		errors.lastName = 'Last name must be at least 3 characters';
	}
	if ( ! moment( values.date, moment.ISO_8601, true ).isValid() ) {
		errors.date = 'Invalid date';
	}
	return errors;
};

// eslint-disable-next-line no-console
const onSubmit = ( values ) => console.log( values );
const initialValues = {
	firstName: '',
	lastName: '',
	address: '',
	select: '3',
	date: '2014-10-24T13:02',
	checkbox: true,
	radio: 'one',
};

export const Basic = () => {
	const [ onChangeValues, setOnChangeValues ] = useState( {} );

	const handleChange = ( change, newValues ) => {
		setOnChangeValues( newValues );
	};

	return (
		<div>
			<Form
				validate={ validate }
				onSubmit={ onSubmit }
				onChange={ handleChange }
				initialValues={ initialValues }
			>
				{ ( { getInputProps, values, errors, handleSubmit } ) => {
					const radioInputProps = getInputProps( 'radio' );
					return (
						<div>
							<TextControl
								label={ 'First Name' }
								{ ...getInputProps( 'firstName' ) }
							/>
							<TextControl
								label={ 'Last Name' }
								{ ...getInputProps( 'lastName' ) }
							/>
							<SelectControl
								label="Select"
								options={ [
									{ label: 'Option 1', value: '1' },
									{ label: 'Option 2', value: '2' },
									{ label: 'Option 3', value: '3' },
								] }
								{ ...getInputProps( 'select' ) }
							/>
							<DateTimePickerControl
								label="Date"
								dateTimeFormat="YYYY-MM-DD HH:mm"
								placeholder="Enter a date"
								currentDate={ values.date }
								{ ...getInputProps( 'date' ) }
							/>
							<CheckboxControl
								label="Checkbox"
								{ ...getInputProps( 'checkbox' ) }
							/>
							<RadioControl
								label="Radio"
								onChange={ radioInputProps.onChange }
								selected={ radioInputProps.value }
								options={ [
									{ label: 'Option 1', value: 'one' },
									{ label: 'Option 2', value: 'two' },
									{ label: 'Option 3', value: 'three' },
								] }
							/>
							<Button
								isPrimary
								onClick={ handleSubmit }
								disabled={ Object.keys( errors ).length }
							>
								Submit
							</Button>
							<br />
							<br />
							<h3>Return data:</h3>
							<pre>
								Values: { JSON.stringify( values ) }
								<br />
								Errors: { JSON.stringify( errors ) }
							</pre>
						</div>
					);
				} }
			</Form>
			<div>
				<pre>onChange values: { JSON.stringify( onChangeValues ) }</pre>
			</div>
		</div>
	);
};

const NuField = ( { name, control } ) => {
	const { field } = useController( {
		name,
		control,
	} );
	return <TextControl label={ name } { ...field } />;
};

const NuRadioField = ( { name, control } ) => {
	const { field } = useController( {
		name,
		control,
	} );
	useWatch( { name: 'firstName' } );
	return (
		<RadioControl
			label="Radio"
			onChange={ field.onChange }
			selected={ field.value }
			options={ [
				{ label: 'Option 1', value: 'one' },
				{ label: 'Option 2', value: 'two' },
				{ label: 'Option 3', value: 'three' },
			] }
		/>
	);
};

const NuOutput = ( { watch } ) => {
	const form = useWatch();

	return (
		<div>
			<pre>onChange values: { JSON.stringify( form ) }</pre>
		</div>
	);
};

export const BasicWithNuForm = () => {
	const methods = useForm( {
		defaultValues: initialValues,
	} );
	const { control } = methods;
	return (
		<FormProvider { ...methods }>
			<div>
				<NuField name="firstName" control={ control } />
				<NuField name="lastName" control={ control } />
				<NuField name="address" control={ control } />
				<NuRadioField name="radio" control={ control } />
			</div>
			<NuOutput />
		</FormProvider>
	);
};

export default {
	title: 'WooCommerce Admin/components/Form',
	component: Form,
};
