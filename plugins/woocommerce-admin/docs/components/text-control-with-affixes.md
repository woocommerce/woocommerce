`TextControlWithAffixes` (component)
====================================

This component is essentially a wrapper (really a reimplementation) around the
TextControl component that adds support for affixes, i.e. the ability to display
a fixed part either at the beginning or at the end of the text input.

Props
-----

### `label`

- Type: String
- Default: null

If this property is added, a label will be generated using label property as the content.

### `help`

- Type: String
- Default: null

If this property is added, a help text will be generated using help property as the content.

### `type`

- Type: String
- Default: `'text'`

Type of the input element to render. Defaults to "text".

### `value`

- **Required**
- Type: String
- Default: null

The current value of the input.

### `className`

- Type: String
- Default: null

The class that will be added with "components-base-control" to the classes of the wrapper div.
If no className is passed only components-base-control is used.

### `onChange`

- **Required**
- Type: Function
- Default: null

A function that receives the value of the input.

### `prefix`

- Type: ReactNode
- Default: null

Markup to be inserted at the beginning of the input.

### `suffix`

- Type: ReactNode
- Default: null

Markup to be appended at the end of the input.

