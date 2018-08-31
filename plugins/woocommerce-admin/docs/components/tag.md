`Tag` (component)
=================

This component can be used to show an item styled as a "tag", optionally with an `X` + "remove".
Generally this is used in a collection of selected items, see the Search component.



Props
-----

### `id`

- **Required**
- Type: Number
- Default: null

The ID for this item, used in the remove function.

### `label`

- **Required**
- Type: String
- Default: null

The name for this item, displayed as the tag's text.

### `remove`

- Type: Function
- Default: null

A function called when the remove X is clicked. If not used, no X icon will display.

### `removeLabel`

- Type: String
- Default: `__( 'Remove tag', 'wc-admin' )`

The label for removing this item (shown when hovering on X, or read to screen reader users). Defaults to "Remove tag".

### `screenReaderLabel`

- Type: String
- Default: null

A more descriptive label for screen reader users. Defaults to the `name` prop.

