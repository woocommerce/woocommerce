Count
===

Display a number with a styled border

## Usage

```jsx
import Count from 'components/count';

export default function myCount() {
	return (
		<Count count={ 33 } />
	);
}
```

### Props

* Marks required props

Name | Type | Default | Description
--- | --- | --- | ---
`count`* | `number` | none | Value of the number to be displayed
`label` | `string` | "Total {props.count}" | A translated label with the number in context, used for screen readers
