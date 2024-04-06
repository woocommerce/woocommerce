---
post_title: WooCommerce coding standards
menu_title: Coding standards
tags: reference
---

Adhering to WooCommerce coding standards is essential for maintaining high code quality, ensuring compatibility, and facilitating easier maintenance and updates. This document outlines the recommended coding practices for developers working within the WooCommerce ecosystem, including the use of hooks, function prefixing, translatable texts, and code structure.


## Position of Hooks

Position hooks below the function call to align with the common pattern in the WordPress and WooCommerce ecosystem.

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

Use a consistent prefix for all function calls to avoid conflicts. For the code snippets in this repo, use `YOUR_PREFIX`.

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

Ensure all plain texts are translatable and use a consistent text domain, adhering to internationalization best practices. For the code snippets in this repo, use the textdomain `YOUR-TEXTDOMAIN`.

### Example

```php
/**
 * Add welcome message.
 */
function YOUR_PREFIX_welcome_message() {
    echo __( 'Welcome to our website', 'YOUR-TEXTDOMAIN' );
}
add_action( 'wp_footer', 'YOUR_PREFIX_welcome_message' );
```

## Use of function_exists()

To prevent errors from potential function redeclaration, wrap all function calls with `function_exists()`.

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

## Code Quality Standards

To ensure the highest standards of code quality, developers are encouraged to adhere to the following practices:

### WooCommerce Sniffs and WordPress Code Standards

- **Ensure no code style issues** when code is passed through WooCommerce Sniffs and WordPress Code Standards for PHP_CodeSniffer.

### Automated Testing

- **Unit Tests**: Implement automated unit tests to validate code functionality in isolation.
- **E2E Tests**: Utilize automated end-to-end tests to verify the integrated operation of components within the application.

### Tracking and Managing Bugs

- **Monitor and aim to minimize** the number of open bugs, ensuring a stable and reliable product.

### Code Organization

- **Organize code in self-contained classes** to avoid creating "god/super classes" that contain all plugin code. This practice promotes modularity and simplifies maintenance.

By following these coding standards and practices, developers can create high-quality, maintainable, and secure WooCommerce extensions that contribute positively to the WordPress ecosystem.
