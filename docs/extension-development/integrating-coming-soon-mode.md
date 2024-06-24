---
post_title: Integrating with coming soon mode
tags: how-to, coming-soon
---

# Integrating with coming soon mode

This guide provides examples for third-party developers and hosting providers on how to integrate their systems with WooCommerce's coming soon mode. We will cover common tasks using hooks as described in the [WooCommerce Developer Blog Post](https://developer.woocommerce.com/2024/06/18/introducing-coming-soon-mode/).

## Clear server cache on site visibility settings change

When the site's visibility settings is changed, it may be necessary to clear the server cache in order for the changes to be applied and customer-facing pages re-cached. We can utilize [`update_option`](https://developer.wordpress.org/reference/hooks/update_option/) hook to achieve this.

```php
add_action( 'update_option_woocommerce_coming_soon', 'clear_server_cache', 10, 3 );
add_action( 'update_option_woocommerce_store_pages_only', 'clear_server_cache', 10, 3 );

function clear_server_cache( $old_value, $new_value, $option ) {
    // Implement your logic to clear the server cache.
    if ( function_exists( 'your_cache_clear_function' ) ) {
        your_cache_clear_function();
    }
}
```

## Syncing coming soon mode from other plugins

You can also programatically sync the coming soon mode from your application by direcly setting the relevant options. An example use case is such as using the coming soon page as a maintenance page.

```php
function enable_coming_soon() {
    // Check user capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    // Enable coming soon.
    update_option( 'woocommerce_coming_soon', 'yes' );
}
```

## Disable coming soon customer-facing page

If you already have another feature that behaves similar to WooCommerce's coming soon mode, it can cause unintended conflicts. You could disable the coming soon mode by excluding all shopper-facing pages.

```php
add_filter( 'woocommerce_coming_soon_exclude', function() {
    return true;
}, 10 );
```

