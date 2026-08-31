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

To see the properties available within `useFormContext()`, check out the [`FormContextType` definition](./types.ts).

### Props

| Name            | Type     | Default | Description                                                                                                                                                          |
| --------------- | -------- | ------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `children`      | \*       | `null`  | A renderable component in which to pass this component's state and helpers. Generally a number of input or other form elements                                       |
| `errors`        | Object   | `{}`    | Object of all initial errors to store in state                                                                                                                       |
| `initialValues` | Object   | `{}`    | Object key:value pair list of all initial field values                                                                                                               |
| `onSubmit`      | Function | `noop`  | Function to call when a form is submitted with valid fields                                                                                                          |
| `validate`      | Function | `noop`  | A function that is passed a list of all values and should return an `errors` object with error response                                                              |
| `touched`       | Object   | `{}`    | This prop helps determine whether or not a field has received focus                                                                                                  |
| `onChange`      | Function | `noop`  | Called once for each entry a value call wrote. Receives `( { name, value }, values, isValid )`. See [Change notifications](#change-notifications)                    |
| `onChanges`     | Function | `noop`  | Called once per value call, with every entry that call wrote. Receives `( [ { name, value } ], values, isValid )`. See [Change notifications](#change-notifications) |

### Change notifications

`onChange` and `onChanges` report only the entries a call wrote. The complete next form state is always the second argument, so a consumer that just needs the new values can read that and ignore the reported entries entirely. A consumer that needs to react to fields it did not write should read `values` rather than wait for a notification about them.

-   `setValue( name, value )` reports exactly one entry: the top-level key it wrote. For a flat name that is the name you passed. Sibling fields are never reported, even when the form holds other values.
-   A nested write, in dot or bracket notation, is reported under its top-level key with the updated subtree. With `dimensions: { width: 1, height: 2 }` in the form, `setValue( 'dimensions.width', 5 )` reports `{ name: 'dimensions', value: { width: 5, height: 2 } }`. Paths resolve the way lodash `set` resolves them, so a form that holds a literal key such as `'a.b'` has that key written and reported as is.
-   `setValue( name, value )` is a no-op when the path steps through `__proto__`, `constructor` or `prototype`. lodash refuses to write those keys, so the state does not change and nothing is reported. A literal key that merely contains one of them, such as `'a.constructor'`, is written normally, since lodash writes an existing literal key in place rather than as a path.
-   `setValues( patch )` shallow-merges `patch` into the form state and reports the patch's keys in JavaScript key order.
-   `resetForm()` replaces the state silently and reports nothing.
-   Every call is a distinct logical change. Repeated writes to one field, and writes that set a field to the value it already holds, are each reported in call order. Values are never compared for equality and changes are never deduplicated.
-   Callbacks are synchronous and ordered: validation errors are enqueued first, then `onChange` fires once per written entry in order, then `onChanges` fires once for the call.
-   Several value calls made in the same synchronous stack, such as one event handler or one effect, accumulate. Each call builds on the state left by the one before it, and each callback receives the complete state as of its own call.
