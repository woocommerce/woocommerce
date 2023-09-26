# Form

A form component to handle form state and provide input helper props.

## Usage

```jsx
const initialValues = { firstName: '' };

<Form onSubmit={ ( values ) => {} } initialValues={ initialValues }>
	{ ( { getInputProps, values, errors, handleSubmit } ) => (
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
</Form>;
```

## Usage with FormContext

```jsx
const initialValues = { firstName: '' };

const Field = () => {
	const formProps = useFormContext< { foo: string } >();

	return <input type="text" { ...formProps.getInputProps<string>( 'firstName' ) } />
}

<Form
	onSubmit={ ( values ) => {} }
	initialValues={ initialValues }
>
	{ ( {
		errors,
		handleSubmit,
	} ) => (
		<div>
			<Field />
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

To see the properties available within `useFormContext()` check out the `FormContext` type [here](./form-context.ts).

### Props

| Name            | Type     | Default | Description                                                                                                                    |
| --------------- | -------- | ------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `children`      | \*       | `null`  | A renderable component in which to pass this component's state and helpers. Generally a number of input or other form elements |
| `errors`        | Object   | `{}`    | Object of all initial errors to store in state                                                                                 |
| `initialValues` | Object   | `{}`    | Object key:value pair list of all initial field values                                                                         |
| `onSubmit`      | Function | `noop`  | Function to call when a form is submitted with valid fields                                                                    |
| `validate`      | Function | `noop`  | A function that is passed a list of all values and should return an `errors` object with error response                        |
| `touched`       | Object   | `{}`    | This prop helps determine whether or not a field has received focus                                                            |
| `onChange`      | Function | `null`  | A function that receives the value of the input; called when selected items change, whether added, edited, or removed          |
