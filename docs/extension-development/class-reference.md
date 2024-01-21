---
post_title: Classes in WooCommerce
menu_title: Classes in WooCommerce
tags: reference
---

## List of Classes in WooCommerce

For a list of Classes in WooCommerce, please see the [WooCommerce Code Reference](https://woocommerce.github.io/code-reference/packages/WooCommerce-Classes.html).

## Common Classes

### WooCommerce

The main class is `woocommerce` which is available globally via the `$woocommerce` variable. This handles the main functions of WooCommerce and init's other classes, stores site-wide variables, and handles error/success messages. The woocommerce class initializes the following classes when constructed:

-   `WC_Query` - stored in `$woocommerce->query`
-   `WC_Customer` - stored in `$woocommerce->customer`
-   `WC_Shipping` - stored in `$woocommerce->shipping`
-   `WC_Payment_Gateways` - stored in `$woocommerce->payment_gateways`
-   `WC_Countries` - stored in `$woocommerce->countries`

Other classes are auto-loaded on demand.

View the [WooCommerce Class Code Reference](https://woocommerce.github.io/code-reference/classes/WooCommerce.html) for a full list of methods contained in this class.

### WC_Product

WooCommerce has several product classes responsible for loading and outputting product data. This can be loaded through PHP using:

`$product = wc_get_product( $post->ID );`

In the loop this is not always necessary since calling  `the_post()` will automatically populate the global  `$product` variable if the post is a product.

View the [WC_Product Code Reference](https://woocommerce.github.io/code-reference/classes/WC-Product.html) for a full list of methods contained in this class.

### WC_Customer

The customer class allows you to get data about the current customer, for example:

```php
global $woocommerce;
$customer_country = $woocommerce->customer->get_country();
```

View the [WC_Customer Code Reference](https://woocommerce.github.io/code-reference/classes/WC-Customer.html) for a full list of methods contained in this class.

### WC_Cart

The cart class loads and stores the users cart data in a session. For example, to get the cart subtotal you could use:

```php
global $woocommerce;
$cart_subtotal = $woocommerce->cart->get_cart_subtotal();
```

View the [WC_Cart Code Reference](https://woocommerce.github.io/code-reference/classes/WC-Cart.html) for a full list of methods contained in this class.
