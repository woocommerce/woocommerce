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
import { Form } from '@woocommerce/components';

const validate = ( values ) => {
	const errors = {};
	if ( ! values.firstName ) {
		errors.firstName = 'First name is required';
	}
	if ( values.lastName.length < 3 ) {
		errors.lastName = 'Last name must be at least 3 characters';
	}
	return errors;
};

// eslint-disable-next-line no-console
const onSubmitCallback = ( values ) => console.log( values );
const initialValues = {
	firstName: '',
	lastName: '',
	select: '3',
	checkbox: true,
	radio: '2',
};

export default () => (
	<Form
		validate={ validate }
		onSubmitCallback={ onSubmitCallback }
		initialValues={ initialValues }
	>
		{ ( { getInputProps, values, errors, handleSubmit } ) => (
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
				<CheckboxControl
					label="Checkbox"
					{ ...getInputProps( 'checkbox' ) }
				/>
				<RadioControl
					label="Radio"
					options={ [
						{ label: 'Option 1', value: '1' },
						{ label: 'Option 2', value: '2' },
						{ label: 'Option 3', value: '3' },
					] }
					{ ...getInputProps( 'radio' ) }
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
		) }
	</Form>
);
