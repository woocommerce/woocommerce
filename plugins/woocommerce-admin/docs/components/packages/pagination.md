`Pagination` (component)
========================

Use `Pagination` to allow navigation between pages that represent a collection of items.
The component allows for selecting a new page and items per page options.

Props
-----

### `page`

- **Required**
- Type: Number
- Default: null

The current page of the collection.

### `onPageChange`

- Type: Function
- Default: `noop`

A function to execute when the page is changed.

### `perPage`

- **Required**
- Type: Number
- Default: null

The amount of results that are being displayed per page.

### `onPerPageChange`

- Type: Function
- Default: `noop`

A function to execute when the per page option is changed.

### `total`

- **Required**
- Type: Number
- Default: null

The total number of results.

### `className`

- Type: String
- Default: null

Additional classNames.

