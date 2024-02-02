---
post_title: How to check if WooCommerce is active
menu_title: Check if WooCommerce is active
tags: how-to
---

When developing for WooCommerce, it's important to check that WooCommerce is installed and active before your own code runs. This ensures no errors occur from missing WooCommerce functions or classes.

There are a few methods to achieve this. The first is to initialize your code on `woocommerce_loaded`. This means you can be sure that WooCommerce has loaded and its functions are ready to use. This is fired at the same time as the core `plugins_loaded` action. **Note**: at this point, the current user has not yet been set.

```php
add_action( 'woocommerce_loaded', 'prefix_woocommerce_loaded' );

function prefix_woocommerce_loaded() {
	// Custom code here. WooCommerce is active and all plugins have been loaded...
}
```

You can also initialize your code on the `woocommerce_init` hook. We know this only runs when WooCommerce is active and initialized. This fires around the same time as the core `init` action, but is triggered _after_ WooCommerce initialization finishes. **Note**: you can also utilize the `before_woocommerce_init` hook, which is fired on the core `init` hook, just _before_ WooCommerce is initialized.

```php
add_action( 'woocommerce_init', 'prefix_woocommerce_init' );

function prefix_woocommerce_init() {
	// Custom code here. WooCommerce is active and initialized...
}
```

Using the hooks recommended above means you have access to WooCommerce functions, which can then be used to check for additional conditions. For example, you might want to check the version of WooCommerce before proceeding:

```php
add_action( 'woocommerce_init', 'prefix_woocommerce_init' );

function prefix_woocommerce_init() {
	// Only continue if we have access to version 8.7.0 or higher.
	if ( version_compare( wc()->version, '8.7.0', '<' ) ) {
		return;
	}

	// Custom code here. WooCommerce is active and initialized...
}
```
