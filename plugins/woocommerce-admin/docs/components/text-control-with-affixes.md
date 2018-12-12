Text Control With Affixes
============================

This component is essentially a wrapper (really a reimplementation) around the TextControl component that adds support for affixes, i.e. the ability to display a fixed part either at the beginning or at the end of the text input.



Props
-----

The set of props accepted by the component will be specified below.
Props not included in this set will be applied to the input element.

### `label`

If this property is added, a label will be generated using label property as the content.

- Type: `String`

### `help`

If this property is added, a help text will be generated using help property as the content.

- Type: `String`

### `type`

Type of the input element to render. Defaults to "text".

- Type: `String`
- Default: "text"

### `value`

The current value of the input.

- **Required**
- Type: `String`

### `className`

The class that will be added with "components-base-control" to the classes of the wrapper div.
If no className is passed only components-base-control is used.

- Type: `String`

### `onChange`

A function that receives the value of the input.

- **Required**
- Type: `function`

### `prefix`

Markup to be inserted at the beginning of the input.

- Type: ReactNode

### `suffix`

Markup to be appended at the end of the input.

- Type: ReactNode

### `noWrap`

A flag that prevents the prefix and suffix from wrapping when the component is displayed on small viewports. This basically disables the corresponding breakpoint.

- Type: Boolean
- Default: null