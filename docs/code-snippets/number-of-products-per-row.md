---
post_title: Change number of related products displayed
tags: code-snippet
---

Add code to your child theme's functions.php file or via a plugin that allows custom functions to be added, such as the [Code snippets](https://wordpress.org/plugins/code-snippets/) plugin. Avoid adding custom code directly to your parent theme's `functions.php` file as this will be wiped entirely when you update the theme.

Please note that it does not work for all themes because of the way they're coded.

```php
if ( ! function_exists( 'YOUR_PREFIX_related_products_args' ) ) {
	/**
	 * Change number of related products output.
	 *
	 * @param  array $args The related products args.
	 * @return array The modified related products args.
	 */
	function YOUR_PREFIX_related_products_args( $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		$args['posts_per_page'] = 4; // 4 related products.
		$args['columns']        = 2; // Arranged in 2 columns.

		return $args;
	}
}
add_filter( 'woocommerce_output_related_products_args', 'YOUR_PREFIX_related_products_args', 20 );
```
