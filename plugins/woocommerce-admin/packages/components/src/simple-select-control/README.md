SimpleSelectControl
===

A component for displaying a material styled 'simple' select control.

## Usage

```jsx
const petOptions = [
	{
		value: 'cat',
		label: 'Cat',
	},
	{
		value: 'dog',
		label: 'Dog',
	},
];

<SimpleSelectControl
	label="What is your favorite pet?"
	onChange={ value => this.setState( { pet: value } ) }
	options={ petOptions }
	value={ pet }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | `null` | Additional class name to style the component
`label` | String | `null` | A label to use for the main select element
`options` | Array | `null` | An array of options to use for the dropddown
`onChange` | Function | `null` | A function that receives the value of the new option that is being selected as input
`value` | String | `null` | The currently value of the select element
`help` | One of type: string, node | `null` | If this property is added, a help text will be generated using help property as the content

### `options` structure

The `options` array needs to be composed of objects with properties:

- `value`: String - Input value for this option.
- `label`: String - Label for this option.
- `disabled`: Boolean - Disable the option.