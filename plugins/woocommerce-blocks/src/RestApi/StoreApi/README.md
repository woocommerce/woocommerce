# WooCommerce Store API

The WooCommerce Store API is a public-facing REST API that makes it possible to add items to cart, update the cart, and retrieve cart data in JSON format.

Unlike the main WooCommerce REST API, this API does not require authentication. It is intended to be used via AJAX and other client side code, such as the add-to-cart functionality in blocks, to provide functionality to customers.

## Endpoints

-   GET `/wc/store/cart` - Get a representation of the cart, including totals.
-   GET `/wc/store/cart/items` - Get cart items.
-   GET `/wc/store/cart/items/<key>` - Get a single cart item.
-   PUT `/wc/store/cart/items/<key>` - Update a single cart item (quantity only).
-   POST `/wc/store/cart/items` - Create a new cart item.
-   DELETE `/wc/store/cart/items` - Delete all cart items (clear cart).
-   DELETE `/wc/store/cart/items/<key>` - Delete a single cart item.
