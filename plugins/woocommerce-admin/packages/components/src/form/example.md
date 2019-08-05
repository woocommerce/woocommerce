```jsx
import { Button TextControl } from '@wordpress/components';
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

const onSubmitCallback = ( values ) => console.log( values );
const initialValues = { firstName: '', lastName: '' };

const MyForm = () => (
	<Form validate={ validate } onSubmitCallback={ onSubmitCallback } initialValues={ initialValues }>
		{ ( {
			getInputProps,
			values,
			errors,
			setValue,
			handleSubmit,
		} ) => (
			<div>
				<TextControl
					label={ 'First Name' }
					{ ...getInputProps( 'firstName' ) }
				/>
				<TextControl
					label={ 'Last Name' }
					{ ...getInputProps( 'lastName' ) }
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
				Values: { JSON.stringify( values ) }<br />
				Errors: { JSON.stringify( errors ) }<br />
			</div>
		) }
	</Form>
);
```
		