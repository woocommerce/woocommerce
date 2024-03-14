---
post_title: How to check if WooCommerce is active
menu_title: Check if WooCommerce is active
tags: how-to
---

When developing for WooCommerce, ensuring that WooCommerce is installed and active before your code runs is crucial. This prevents errors related to missing WooCommerce functions or classes.

There are a few methods to achieve this. The first is to execute your code on the `woocommerce_loaded` action. This approach guarantees that WooCommerce and its functionalities are fully loaded and available for use. This is fired around the same time as the core `plugins_loaded` action. 

```php
add_action( 'woocommerce_loaded', 'prefix_woocommerce_loaded' );

function prefix_woocommerce_loaded() {
	// Custom code here. WooCommerce is active and all plugins have been loaded...
}
```

**Note**: At this stage, WordPress has not yet initialized the current user data.

Another method is to execute your code on the `woocommerce_init` action. This is executed right _after_ WooCommerce is active and initialized. This action (and the `before_woocommerce_init` action) fires in the context of the WordPress `init` action so at this point current user data has been initialized.

```php
add_action( 'woocommerce_init', 'prefix_woocommerce_init' );

function prefix_woocommerce_init() {
	// Custom code here. WooCommerce is active and initialized...
}
```

**Note**: The `before_woocommerce_init` hook is also an option, running just _before_ WooCommerce's initialization

Using the above hooks grants access to WooCommerce functions, enabling further condition checks. For instance, you might want to verify WooCommerce's version to ensure compatibility with your code:

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

Choosing the right hook based on your development needs ensures your WooCommerce extensions or customizations work seamlessly and efficiently.
