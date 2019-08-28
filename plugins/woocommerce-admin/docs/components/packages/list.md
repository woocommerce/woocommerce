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
  - after: ReactNode - Content displayed after the list item text.
  - before: ReactNode - Content displayed before the list item text.
  - className: String - Additional class name to style the list item.
  - description: String - Description displayed beneath the list item title.
  - href: String - Href attribute used in a Link wrapped around the item.
  - onClick: Function - Content displayed after the list item text.
  - target: String - Target attribute used for Link wrapper.
  - title: String - Title displayed for the list item.
- Default: null

An array of list items.

