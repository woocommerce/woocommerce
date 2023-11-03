TextControl
===

An input field use for text inputs in forms.

## Usage

```jsx
<TextControl
	label="Input label"
	value={ value }
	onChange={ value => setState( { value } ) }
/>;
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | ``null`` | Additional CSS classes
`disabled` | Boolean | ``null`` | Disables the field
`label` | String | ``null`` | Input label used as a placeholder
`onClick` | Function | ``null`` | On click handler called when the component is clicked, passed the click event
`value` | String | ``null`` | The value of the input field
