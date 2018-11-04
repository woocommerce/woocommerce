`Tag` (component)
=================

This component can be used to show an item styled as a "tag", optionally with an `X` + "remove"
or with a popover that is shown on click.



Props
-----

### `id`

- Type: Number
- Default: null

The ID for this item, used in the remove function.

### `label`

- **Required**
- Type: String
- Default: null

The name for this item, displayed as the tag's text.

### `popoverContents`

- Type: ReactNode
- Default: null

Contents to display on click in a popover

### `remove`

- Type: Function
- Default: null

A function called when the remove X is clicked. If not used, no X icon will display.

### `screenReaderLabel`

- Type: String
- Default: null

A more descriptive label for screen reader users. Defaults to the `name` prop.

