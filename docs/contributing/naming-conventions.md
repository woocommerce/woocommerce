---
post_title: Naming conventions
menu_title: Naming conventions
tags: reference
---

## PHP

WooCommerce core generally follows [WordPress PHP naming conventions](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/#naming-conventions). On top of that, function, class, and hook names should be prefixed. For functions the prefix is `wc_`, for classes is `WC_` and for hooks is `woocommerce_`.

Function name examples:

- `wc_get_product()`
- `wc_is_active_theme()`

Class name examples:

- `WC_Breadcrumb`
- `WC_Cart`

Hook name examples (actions or filters):

- `woocommerce_after_checkout_validation`
- `woocommerce_get_formatted_order_total`

There are however some exceptions which apply to classes defined inside `src/`. Within this directory:

- We do not use the `WC_` prefix for class names (the prefix is not needed, because all of the classes in this location live within the `Automattic\WooCommerce` namespace)
- Classes are named using the `CamelCase` convention (however, method names should still be `underscore_separated`)
- Class files should match the class name and do not need the `class-` prefix (for example, the filename for the `StringUtil` class is `StringUtil.php`)

## JS

WooCommerce core follows [WordPress JS naming conventions](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/#naming-conventions). As with PHP, function, class, and hook names should be prefixed, but the convention for JS is slightly different, and camelCase is used instead of snake_case. For functions, the prefix is `wc`, for classes is `WC` and for hooks is `woocommerce`.

Function name example:

- `wcSettings()`

Class name example:

- `WCOrdersTable`

Hook name example (actions or filters):

- `woocommerceTracksEventProperties`

## CSS and SASS

See [CSS SASS coding guidelines and naming conventions](https://github.com/woocommerce/woocommerce/wiki/CSS-SASS-coding-guidelines-and-naming-conventions).
