---
post_title: WooCommerce coding standards
menu_title: Coding standards
tags: reference
---

## Position of hooks

Position hooks below the function call, as this follows the common pattern in the WordPress and WooCommerce ecosystem.

### Example

```php
/**
 * Add custom message.
 */
function YOUR_PREFIX_custom_message() {
    echo 'This is a custom message';
}
add_action( 'wp_footer', 'YOUR_PREFIX_custom_message' );
```

## Prefixing function calls

Use a consistent prefix for all function calls. For the code snippets in this repo, use the prefix `YOUR_PREFIX`.

### Example

```php
/**
 * Add custom discount.
 */
function YOUR_PREFIX_custom_discount( $price, $product ) {
    return $price * 0.9;  // 10% discount
}
add_filter( 'woocommerce_product_get_price', 'YOUR_PREFIX_custom_discount', 10, 2 );
```

## Translatable texts and text domains

Make all plain texts translatable, and use a consistent text domain. This aligns with the best practices for internationalisation. For the code snippets in this repo, use the textdomain `YOUR-TEXTDOMAIN`.

### Example

```php
/**
 * Add custom message.
 */
function YOUR_PREFIX_welcome_message() {
    echo __( 'Welcome to our website', 'YOUR-TEXTDOMAIN' );
}
add_action( 'wp_footer', 'YOUR_PREFIX_welcome_message' );
```

## Use of function_exists()

Wrap all function calls in a `function_exists()` call to prevent errors due to potential function redeclaration.

### Example

```php
/**
 * Add thumbnail support.
 */
if ( ! function_exists( 'YOUR_PREFIX_theme_setup' ) ) {
    function YOUR_PREFIX_theme_setup() {
        add_theme_support( 'post-thumbnails' );
    }
}
add_action( 'after_setup_theme', 'YOUR_PREFIX_theme_setup' );
```
