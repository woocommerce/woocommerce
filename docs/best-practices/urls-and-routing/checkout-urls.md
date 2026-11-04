---
post_title: Shareable Checkout URLs
sidebar_label: Checkout URLs
---

# Shareable Checkout URLs

Custom checkout links automatically populate the cart with specific products and redirect customers straight to checkout with a unique session ID.

The custom checkout link path is `/checkout-link/` and is not customizable.

## Supported parameters

### Products

The `products` parameter accepts a comma-separated list of products. Each product uses the following positional format:

```plaintext
id-or-sku:quantity:variation-data:cart-item-data
```

The quantity, variation data, and cart item data are optional. The default quantity is `1`. Keep an empty field between colons when omitting a field that comes before one you want to provide.

The product identifier can be:

- A product or variation ID, such as `123`.
- A nonnumeric SKU, such as `BLUE-SHIRT`.
- A numeric SKU prefixed with `sku=`, such as `sku=999999`. Without the prefix, a numeric identifier is treated as a product ID.

Variation and cart item data use `key=value` pairs. Separate multiple pairs in the same field with semicolons. Variation data selects a variation, while cart item data passes additional values consumed by extensions or custom product types. The accepted cart item data depends on the extension handling the product.

For example:

```plaintext
products=123
products=123:2
products=BLUE-SHIRT:2
products=sku=999999:2
products=123:1:color=black;size=medium
products=123:1::nyp=120
products=123:2:color=black;size=medium:nyp=120
```

The last three examples add variation data, cart item data, or both. The empty variation field in `123:1::nyp=120` is required so that `nyp=120` is treated as cart item data.

Commas separate products, colons separate product fields, and semicolons separate data pairs. Prefix one of these delimiters with `~` when it is part of an SKU or data value rather than a separator. For example, the SKU `ABC,123:BLUE` is represented as follows:

```plaintext
products=ABC~,123~:BLUE:1
```

Existing links containing only product IDs and quantities continue to work.

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

- Product ID `123` will be added with quantity `2`.
- Product ID `456` will be added with quantity `1`.
- The coupon code `SPRING10` will be applied.
- The customer will be taken directly to the checkout page.

## Sessions

Once the user is redirected to the checkout page, the cart is populated with the products and coupon code. The final URL includes a `session` parameter storing the ID of the session. Future changes to the checkout will be persisted in the session, enabling persistent and shareable carts.
