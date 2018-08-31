`Search` (component)
====================

A search box which autocompletes results while typing, allowing for the user to select an existing object
(product, order, customer, etc). Currently only products are supported.

Props
-----

### `onChange`

- Type: Function
- Default: `noop`

Function called when selected results change, passed result list.

### `type`

- **Required**
- Type: One of: 'products', 'orders', 'customers'
- Default: null

The object type to be used in searching.

