Segmented Selection
===

Create a panel of styled selectable options rendering stylized checkboxes and labels

## Usage

```jsx
const Numbers = () => {
	return (
		<SegmentedSelection
			options={ [
				{ value: 'one', label: 'One' },
				{ value: 'two', label: 'Two' },
				{ value: 'three', label: 'Three' },
				{ value: 'four', label: 'Four' },
			] }
			selected={ 'two' }
			legend="Select a number"
			onSelect={ data => { /* manipulate data here */ } }
			name="numbers"
		/>
	);
}

```

### Props

Required props are marked with `*`.

Name | Type | Default | Description
--- | --- | --- | ---
`className` | `string` | none | Additional classNames
`name`* | `string` | none | This will be the key in the key and value arguments supplied to `onSelect`
`legend`* | `string` | none | Create a legend visible to screen readers
`options`* | `array` | none | An Array of options to render. The array needs to be composed of objects with properties `label` and `value`.
`selected` | `string` | none | Value of selected item
`onSelect`* | `function` | none | Callback to be executed after selection
