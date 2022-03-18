TextControlWithAffixes
===

This component is essentially a wrapper (really a reimplementation) around the
TextControl component that adds support for affixes, i.e. the ability to display
a fixed part either at the beginning or at the end of the text input.

## Usage

```jsx
<TextControlWithAffixes
    suffix="%"
    label="Text field with a suffix"
    value={ fourth }
    onChange={ value => setState( { fourth: value } ) }
/>
<TextControlWithAffixes
    prefix="$"
    label="Text field with prefix and help text"
    value={ fifth }
    onChange={ value => setState( { fifth: value } ) }
    help="This is some help text."
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`label` | String | `null` | If this property is added, a label will be generated using label property as the content
`help` | String | `null` | If this property is added, a help text will be generated using help property as the content
`type` | String | `'text'` | Type of the input element to render. Defaults to "text"
`value` | String | `null` | (required) The current value of the input
`className` | String | `null` | The class that will be added with "components-base-control" to the classes of the wrapper div. If no className is passed only components-base-control is used
`onChange` | Function | `null` | (required) A function that receives the value of the input
`prefix` | ReactNode | `null` | Markup to be inserted at the beginning of the input
`suffix` | ReactNode | `null` | Markup to be appended at the end of the input
`disabled` | Boolean | `null` | Disables the field
