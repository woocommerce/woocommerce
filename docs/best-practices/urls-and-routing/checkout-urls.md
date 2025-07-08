---
post_title: Shareable Checkout URLs
sidebar_label: Checkout URLs
---

# Shareable Checkout URLs

Custom checkout links automatically populate the cart with specific products and redirect customers straight to checkout with a unique session id. 

The custom checkout link path is `/checkout-link/` and is not customizable.

## Supported parameters

### Products

```plaintext
products=123:2,456:1
```

A comma-separated list of product IDs and quantities. For example, `123:2,456:1`. This feature supports simple products with no additional options. Individual variations can also be added to cart by using the correct variation ID.

### Coupon

```plaintext
coupon=SPRING10
```

A coupon code to apply to the cart. For example, `SPRING10`.

## Example

```plaintext
https://yourstore.com/checkout-link/?products=123:2,456:1&coupon=SPRING10
```

In this link:

- Product ID `123` will be added with quantity `2`
- Product ID `456` with quantity `1`
- The coupon code `SPRING10` will be applied
- The customer is taken directly to the checkout page

## Sessions

Once the user is redirected to the checkout page, the cart is populated with the products and coupon code. The final URL includes a `session` parameter storing the ID of the session. Future changes to the checkout will be persisted in the session, enabling persistent and shareable carts.
