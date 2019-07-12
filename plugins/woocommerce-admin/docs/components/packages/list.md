`List` (component)
==================

List component to display a list of items.

Props
-----

### `className`

- Type: String
- Default: null

Additional class name to style the component.

### `items`

- **Required**
- Type: Array
  - title: String - Title displayed for the list item.
  - description: String - Description displayed beneath the list item title.
  - before: ReactNode - Content displayed before the list item text.
  - after: ReactNode - Content displayed after the list item text.
  - onClick: Function - Content displayed after the list item text.
- Default: null

An array of list items.

