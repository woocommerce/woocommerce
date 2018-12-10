`Search` (component)
====================

A search box which autocompletes results while typing, allowing for the user to select an existing object
(product, order, customer, etc). Currently only products are supported.

Props
-----

### `className`

- Type: String
- Default: null

Class name applied to parent div.

### `onChange`

- Type: Function
- Default: `noop`

Function called when selected results change, passed result list.

### `type`

- **Required**
- Type: One of: 'products', 'product_cats', 'orders', 'customers', 'coupons', 'taxes', 'variations'
- Default: null

The object type to be used in searching.

### `placeholder`

- Type: String
- Default: null

A placeholder for the search input.

### `selected`

- Type: Array
  - id: Number
  - label: String
- Default: `[]`

An array of objects describing selected values.

### `inlineTags`

- Type: Boolean
- Default: `false`

Render tags inside input, otherwise render below input.

### `staticResults`

- Type: Boolean
- Default: `false`

Render results list positioned statically instead of absolutely.

