`Form` (component)
==================

A form component to handle form state and provide input helper props.

Props
-----

### `children`

- Type: *
- Default: null

A renderable component in which to pass this component's state and helpers.
Generally a number of input or other form elements.

### `errors`

- Type: Object
- Default: `{}`

Object of all initial errors to store in state.

### `initialValues`

- Type: Object
- Default: `{}`

Object key:value pair list of all initial field values.

### `onSubmitCallback`

- Type: Function
- Default: `noop`

Function to call when a form is submitted with valid fields.

### `validate`

- Type: Function
- Default: `noop`

A function that is passed a list of all values and
should return an `errors` object with error response.

### `touched`

- Type: undefined
- Default: `{}`


