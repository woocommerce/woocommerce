---
post_title: Adjust the quantity input values
tags: code-snippet
---

Set the starting value, maximum value, minimum value, and increment amount for quantity input fields on product pages.

Add this code to your child theme's `functions.php` file or via a plugin that allows custom functions to be added, such as the [Code snippets](https://wordpress.org/plugins/code-snippets/) plugin. Avoid adding custom code directly to your parent theme's `functions.php` file, as this will be wiped entirely when you update the theme.

```php
if ( ! function_exists( 'YOUR_PREFIX_woocommerce_quantity_input_args' ) ) {
	/**
	 * Adjust the quantity input values for simple products
	 */
	function YOUR_PREFIX_woocommerce_quantity_input_args( $args, $product ) {
		// Only affect the starting value on product pages, not the cart
		if ( is_singular( 'product' ) ) {
			$args['input_value'] = 4;
		}

		$args['max_value'] 	= 10; // Maximum value
		$args['min_value'] 	= 2;  // Minimum value
		$args['step'] 		= 2;  // Quantity steps

		return $args;
	}

	add_filter( 'woocommerce_quantity_input_args', 'YOUR_PREFIX_woocommerce_quantity_input_args', 10, 2 );
}

if ( ! function_exists( 'YOUR_PREFIX_woocommerce_available_variation' ) ) {
	/**
	 * Adjust the quantity input values for variations
	 */
	function YOUR_PREFIX_woocommerce_available_variation( $args ) {
		$args['max_qty'] = 20; // Maximum value (variations)
		$args['min_qty'] = 2;  // Minimum value (variations)

		// Note: the starting value and step for variations is controlled
		// from the 'woocommerce_quantity_input_args' filter shown above for
    // simple products

		return $args;
	}

	add_filter( 'woocommerce_available_variation', 'YOUR_PREFIX_woocommerce_available_variation' );
}
```

If you are looking for a little more power, check out our [Min/Max Quantities](https://woo.com/products/minmax-quantities) extension!
