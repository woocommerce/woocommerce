Form
===

A form component to handle form state and provide input helper props.

## Usage

```jsx
const initialValues = { firstName: '' };

<Form
	onSubmitCallback={ ( values ) => {} }
	initialValues={ initialValues }
>
	{ ( {
		getInputProps,
		values,
		errors,
		handleSubmit,
	} ) => (
		<div>
			<TextControl
				label={ 'First Name' }
				{ ...getInputProps( 'firstName' ) }
			/>
			<Button
				isPrimary
				onClick={ handleSubmit }
				disabled={ Object.keys( errors ).length }
			>
				Submit
			</Button>
		</div>
	) }
</Form>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`children` | * | `null` | A renderable component in which to pass this component's state and helpers. Generally a number of input or other form elements
`errors` | Object | `{}` | Object of all initial errors to store in state
`initialValues` | Object | `{}` | Object key:value pair list of all initial field values
`onSubmitCallback` | Function | `noop` | Function to call when a form is submitted with valid fields
`validate` | Function | `noop` | A function that is passed a list of all values and should return an `errors` object with error response
`touched` |  | `{}` | 
