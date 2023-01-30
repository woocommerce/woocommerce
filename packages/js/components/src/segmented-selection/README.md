SegmentedSelection
===

Create a panel of styled selectable options rendering stylized checkboxes and labels

## Usage

```jsx
<SegmentedSelection
	options={ [
		{ value: 'one', label: 'One' },
		{ value: 'two', label: 'Two' },
		{ value: 'three', label: 'Three' },
		{ value: 'four', label: 'Four' },
	] }
	selected={ selected }
	legend="Select a number"
	onSelect={ ( data ) => setState( { selected: data[ name ] } ) }
	name={ name }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | `null` | Additional CSS classes
`options` | Array | `null` | (required) An Array of options to render. The array needs to be composed of objects with properties `label` and `value`
`selected` | String | `null` | Value of selected item
`onSelect` | Function | `null` | (required) Callback to be executed after selection
`name` | String | `null` | (required) This will be the key in the key and value arguments supplied to `onSelect`
`legend` | String | `null` | (required) Create a legend visible to screen readers

### `options` structure

The `options` array needs to be composed of objects with properties:

- `value`: String - Input value for this option.
- `label`: String - Label for this option.