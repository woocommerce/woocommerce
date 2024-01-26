---
post_title: How to check if WooCommerce is active
menu_title: Check if WooCommerce is active
tags: how-to
---

You can wrap your plugin in a check to see if WooCommerce is installed:

```php
// Test to see if WooCommerce is active (including network activated).

$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (

in_array( $plugin_path, wp_get_active_and_valid_plugins() )

|| in_array( $plugin_path, wp_get_active_network_plugins() )

) {

// Custom code here. WooCommerce is active, however it has not

// necessarily initialized (when that is important, consider

// using the \`woocommerce_init\` action).

}
```

Note that this check will fail if the WC plugin folder is named anything other than woocommerce.
