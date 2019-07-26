`SimpleSelectControl` (component)
=================================

A component for displaying a material styled 'simple' select control.

Props
-----

### `className`

- Type: String
- Default: null

Additional class name to style the component.

### `label`

- Type: String
- Default: null

A label to use for the main select element.

### `options`

- Type: Array
  - value: String - Input value for this option.
  - label: String - Label for this option.
  - disabled: Boolean - Disable the option.
- Default: null

An array of options to use for the dropddown.

### `onChange`

- Type: Function
- Default: null

A function that receives the value of the new option that is being selected as input.

### `value`

- Type: String
- Default: null

The currently value of the select element.

