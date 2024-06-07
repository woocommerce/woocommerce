---
post_title: Naming conventions
menu_title: Naming conventions
tags: reference
---

## PHP

WooCommerce core generally follows [WordPress PHP naming conventions](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/#naming-conventions). 

There are some additional conventions that apply, depending on the location of the code.

### `/src`

Classes defined inside `/src` follow the [PSR-4](https://www.php-fig.org/psr/psr-4/) standard. See the [README for `/src`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/README.md) for more information.

The following conventions apply to this directory:

- No class name prefix is needed, as all classes in this location live within the `Automattic\WooCommerce` namespace.
- Classes are named using `CamelCase` convention.
- Functions are named using `snake_case` convention.
- Class file names should match the class name. They do not need a `class-` prefix.
- The namespace should match the directory structure.
- Hooks are prefixed with `woocommerce_`.
- Hooks are named using `snake_case` convention.

For example, the class defined in `src/Util/StringUtil.php` should be named `StringUtil` and should be in the `Automattic\WooCommerce\Util` namespace.  

### `/includes`

The `/includes` directory contains legacy code that does not follow the PSR-4 standard. See the [README for `/includes`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/README.md) for more information.

The following conventions apply to this directory:

- Class names are prefixed with `WC_`.
- Classes are named using `Upper_Snake_Case` convention.
- Functions are prefixed with `wc_`.
- Functions are named using `snake_case` convention.
- Hooks are prefixed with `woocommerce_`.
- Hooks are named using `snake_case` convention.

Class name examples:

- `WC_Cache_Helper`
- `WC_Cart`

Function name examples:

- `wc_get_product()`
- `wc_is_active_theme()`

Hook name examples (actions or filters):

- `woocommerce_after_checkout_validation`
- `woocommerce_get_formatted_order_total`

## JS

WooCommerce core follows [WordPress JS naming conventions](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/#naming-conventions).

As with PHP, function, class, and hook names should be prefixed, but the convention for JS is slightly different.

- Global class names are prefixed with `WC`. Class names exported from modules are not prefixed.
- Classes are named using `UpperCamelCase` convention.
- Global function names are prefixed with `wc`. Function names exported from modules are not prefixed.
- Functions are named using `camelCase` convention.
- Hooks names are prefixed with `woocommerce`.
- Hooks are named using `camelCase` convention.

Global class name example:

- `WCOrdersTable`

Global function name example:

- `wcSettings()`

Hook name example (actions or filters):

- `woocommerceTracksEventProperties`

## CSS and SASS

See [CSS/Sass Naming Conventions](./css-sass-naming-conventions.md).
