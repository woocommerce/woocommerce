---
post_title: How to check if WooCommerce is active
menu_title: Check if WooCommerce is active
tags: how-to
---

When developing for WooCommerce, it's important to check that WooCommerce is installed and active before your own code runs. This ensures no errors occur from missing WooCommerce functions or classes.

There are a few methods to achieve this. The most reliable approach is to initiate your code on `plugins_loaded`. This means you can be sure that WooCommerce has already initiated, by checking if one of its core classes exists.

```php
add_action( 'plugins_loaded', 'prefix_plugins_loaded' );

function prefix_plugins_loaded() {
	// Check if the "WooCommerce" class exists. If not, exit early.
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// Custom code here. WooCommerce is active and initiated...
}
```

Another route you could take is to initiate your code on the `woocommerce_init` hook. We know this only runs when WooCommerce is active and initiated.

```php
add_action( 'woocommerce_init', 'prefix_woocommerce_init' );

function prefix_woocommerce_init() {
	// Custom code here. WooCommerce is active and initiated...
}
```

However, in some cases you may want to check if WooCommerce exists *before* `plugins_loaded`. This method is slightly less reliable, because it assumes the path of the WooCommerce plugin hasn't been changed. It's also worth noting that this method may run _before or after_ WooCommerce is ready; meaning you may not be able to utilize WooCommerce functions yet.

```php
// Test to see if WooCommerce is active (including network activated).

$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if (
	in_array( $plugin_path, wp_get_active_and_valid_plugins() ) ||
	in_array( $plugin_path, wp_get_active_network_plugins() )
) {
	// Custom code here. WooCommerce is active, however it has not
	// necessarily initialized...
}
```
