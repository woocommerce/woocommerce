# Product List Page

This is a temporary wrapper around `@wordpress/admin-ui`'s `Page` for the experimental product list.

The product list prototype uses a compact two-line header: title and actions in the first row, subtitle in the second row, then the DataViews toolbar immediately below. The current `Page` component does not expose a public option for this compact header spacing, so this wrapper lets the product list own the header markup without changing `@wordpress/admin-ui`.

Remove this wrapper once `Page` supports this layout directly.
