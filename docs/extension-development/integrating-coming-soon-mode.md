---
post_title: Integrating with coming soon mode
tags: how-to, coming-soon
---

This guide provides examples for third-party developers and hosting providers on how to integrate their systems with WooCommerce's coming soon mode. For more details, please read the [developer blog post](https://developer.woocommerce.com/2024/06/18/introducing-coming-soon-mode/).

## Introduction

WooCommerce's coming soon mode allows you to temporarily make your site invisible to the public while you work on it. This guide will show you how to integrate this feature with your system, clear server cache when site visibility settings change, and sync coming soon mode with other plugins.

## Prerequisites

-   Familiarity with PHP and WordPress development.

## Step-by-step instructions

### Clear server cache on site visibility settings change

When the site's visibility settings change, it may be necessary to clear a server cache to apply the changes and re-cache customer-facing pages. The [`update_option`](https://developer.wordpress.org/reference/hooks/update_option/) hook can be used to achieve this.

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

### Syncing coming soon mode with other plugins

The coming soon mode can be programmatically synced from a plugin or application. Here are some example use cases:

-   Integrating with a maintenance mode plugin.
-   Integrating with a hosting provider's coming soon mode.

#### Trigger from WooCommerce

You can use the following example to run a code such as setting your plugin's status when coming soon mode option is updated:

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

#### Trigger from other plugins

You can use the following example to enable or disable WooCommerce coming soon mode from another plugin by directy updating `woocommerce_coming_soon` option:

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

#### 2-way sync with plugins

If 2-way sync is needed, use the following example where `update_option` will not recursively call `sync_coming_soon_from_other_plugins`:

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

### Custom exclusions filter

It is possible for developers to add custom exclusions that bypass the coming soon protection. This is useful for exclusions like always bypassing the screen on a specific IP address, or making a specific landing page available.

#### Disabling coming soon in all pages

If there is another feature that behaves similarly to WooCommerce's coming soon mode, it can cause unintended conflicts. The coming soon mode can be disabled by excluding all customer-facing pages. The following is an example:

```php
add_filter( 'woocommerce_coming_soon_exclude', function() {
    return true;
}, 10 );
```

#### Disabling coming soon except for a specific page

Use the following example to exclude a certain page based on the page's ID. Replace `<page-id>` with your page identifier:

```php
add_filter( 'woocommerce_coming_soon_exclude', function( $is_excluded ) {
    if ( get_the_ID() === <page-id> ) {
        return true;
    }
    return $is_excluded;
}, 10 );
```
