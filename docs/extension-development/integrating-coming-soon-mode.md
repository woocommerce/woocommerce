---
post_title: Integrating with coming soon mode
tags: how-to, coming-soon
---

# Integrating with coming soon mode

This guide provides examples for third-party developers and hosting providers on how to integrate their systems with WooCommerce's coming soon mode. For more details, please read the [developer blog post](https://developer.woocommerce.com/2024/06/18/introducing-coming-soon-mode/).

## Clear server cache on site visibility settings change

When the site's visibility settings is changed, it may be necessary to clear a server cache in order for the changes to be applied and customer-facing pages re-cached. You can utilize [`update_option`](https://developer.wordpress.org/reference/hooks/update_option/) hook to achieve this.

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

## Syncing coming soon mode with other plugins

You can programmatically sync the coming soon mode from your plugin or application. Here are some example use cases:

-   Integrating with a maintenance mode plugin.
-   Integrating with hosting provider's coming soon mode.

### Trigger from WooCommerce

```php
add_action( 'update_option_woocommerce_coming_soon', 'sync_coming_soon_to_other_plugins', 10, 3 );

function sync_coming_soon_to_other_plugins( $old_value, $new_value, $option ) {
    $is_enabled = $new_value === 'yes';

    // Implement your logic to sync coming soon status.
    if ( function_exists( 'set_your_plugin_status' ) ) {
        set_your_plugin_status( $is_enabled );
    }
}
```

### Trigger from other plugins

The following function example can be called from another plugin to enable or disable WooCommerce coming soon mode.

```php
function sync_coming_soon_from_other_plugins( $is_enabled ) {
    // Check user capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    // Set coming soon mode.
    if ( isset( $is_enabled ) ) {
        update_option( 'woocommerce_coming_soon', $is_enabled ? 'yes' : 'no' );
    }
}
```

### 2-way sync with plugins

If 2-way sync is needed, you can use the following example where `update_option` will not recursively call `sync_coming_soon_from_other_plugins`.

```php
add_action( 'update_option_woocommerce_coming_soon', 'sync_coming_soon_to_other_plugins', 10, 3 );

function sync_coming_soon_to_other_plugins( $old_value, $new_value, $option ) {
    $is_enabled = $new_value === 'yes';

    // Implement your logic to sync coming soon status.
    if ( function_exists( 'set_your_plugin_status' ) ) {
        set_your_plugin_status( $is_enabled );
    }
}

function sync_coming_soon_from_other_plugins( $is_enabled ) {
    // Check user capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    if ( isset( $is_enabled ) ) {
        // Temporarily remove the action to prevent a recursive call.
        remove_action( 'update_option_woocommerce_coming_soon', 'sync_coming_soon_to_other_plugins', 10, 3 );

        // Set coming soon mode.
        update_option( 'woocommerce_coming_soon', $is_enabled ? 'yes' : 'no' );

        // Re-add the action.
        add_action( 'update_option_woocommerce_coming_soon', 'sync_coming_soon_to_other_plugins', 10, 3 );
    }
}
```

## Disable coming soon customer-facing page

If you already have another feature that behaves similarly to WooCommerce's coming soon mode, it can cause unintended conflicts. You can disable the coming soon mode by excluding all shopper-facing pages.

```php
// Exclude all customer-facing pages from coming soon mode.
add_filter( 'woocommerce_coming_soon_exclude', function() {
    return true;
}, 10 );
```

You can also use it to apply coming soon mode to all pages except for a specific page:

```php
add_filter( 'woocommerce_coming_soon_exclude', function( $is_excluded ) {
    if ( get_the_ID() === <page-id> ) {
        return true;
    }
    return $is_excluded;
}, 10 );
```
