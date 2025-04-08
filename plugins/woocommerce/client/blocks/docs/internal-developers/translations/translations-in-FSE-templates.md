# Translations in FSE templates

To make the WooCommerce Blocks plugin inclusive, all user-facing strings should be translatable. Managing [Translations in PHP files](../../internal-developers/translations/translations-in-PHP-files.md) and [Translations in JS/TS files](../../internal-developers/translations/translations-in-JS-TS-files.md) is simple as PHP and JS/TS are languages are programming languages which contain translation function. In comparison, FSE-templates are using plain HTML. As HTML is a markup language and not a programming language, translation functions such as `__()` and `_n()` are not available in HTML. Therefore, translations within FSE-templates require a different approach.

Let's take a look at `templates/parts/mini-cart.html`:

```html
<!-- wp:woocommerce/mini-cart-contents -->
<div class="wp-block-woocommerce-mini-cart-contents">
	<!-- wp:woocommerce/filled-mini-cart-contents-block -->
	<div class="wp-block-woocommerce-filled-mini-cart-contents-block">
		<!-- wp:woocommerce/mini-cart-title-block -->
		<div class="wp-block-woocommerce-mini-cart-title-block"></div>
		<!-- /wp:woocommerce/mini-cart-title-block -->
		<!-- wp:woocommerce/mini-cart-items-block -->
		<div class="wp-block-woocommerce-mini-cart-items-block">
			<!-- wp:woocommerce/mini-cart-products-table-block -->
			<div
				class="wp-block-woocommerce-mini-cart-products-table-block"
			></div>
			<!-- /wp:woocommerce/mini-cart-products-table-block -->
		</div>
		<!-- /wp:woocommerce/mini-cart-items-block -->
		<!-- wp:woocommerce/mini-cart-footer-block -->
		<div class="wp-block-woocommerce-mini-cart-footer-block"></div>
		<!-- /wp:woocommerce/mini-cart-footer-block -->
	</div>
	<!-- /wp:woocommerce/filled-mini-cart-contents-block -->

	<!-- wp:woocommerce/empty-mini-cart-contents-block -->
	<div class="wp-block-woocommerce-empty-mini-cart-contents-block">
		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center">
			<strong>Your cart is currently empty!</strong>
		</p>
		<!-- /wp:paragraph -->

		<!-- wp:woocommerce/mini-cart-shopping-button-block -->
		<div class="wp-block-woocommerce-mini-cart-shopping-button-block"></div>
		<!-- /wp:woocommerce/mini-cart-shopping-button-block -->
	</div>
	<!-- /wp:woocommerce/empty-mini-cart-contents-block -->
</div>
<!-- /wp:woocommerce/mini-cart-contents -->
```

This FSE-template contains the following part:

```html
<p class="has-text-align-center">
	<strong>Your cart is currently empty!</strong>
</p>
```

Having this text hardcoded in a FSE-template causes two problems:

1. This string can only be edited, when a user is using an FSE-theme, such as [Twenty Twenty-Two](https://wordpress.org/themes/twentytwentytwo/). If the user is using a non-FSE-theme, such as [Twenty Twenty-One](https://wordpress.org/themes/twentytwentyone/) or older, this FSE-template cannot be edited.
2. Even is a user is using an FSE-theme, every user that is using a site language other than the default one, has to manually change the string `Your cart is currently empty!`.

To handle translations within FSE-templates, we need to find the following code:

```html
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">
	<strong>Your cart is currently empty!</strong>
</p>
<!-- /wp:paragraph -->
```

We then replace this code with the following code:

```html
<!-- wp:pattern {"slug":"woocommerce/mini-cart-empty-cart-message"} /-->
```

In the file, that holds the logic for this FSE-template, in this case `src/BlockTypes/MiniCart.php`, we then register this placeholder as a block pattern:

```php
/**
 * Register block pattern for Empty Cart Message to make it translatable.
 */
public function register_empty_cart_message_block_pattern() {
    register_block_pattern(
        'woocommerce/mini-cart-empty-cart-message',
        array(
            'title'    => __( 'Empty Mini-Cart Message', 'woo-gutenberg-products-block' ),
            'inserter' => false,
            'content'  => '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center"><strong>' . __( 'Your cart is currently empty!', 'woo-gutenberg-products-block' ) . '</strong></p><!-- /wp:paragraph -->',
        )
    );
}
```

Finally, we call this function using the following code:

```php
/**
 * Initialize this block type.
 *
 * - Hook into WP lifecycle.
 * - Register the block with WordPress.
 */
protected function initialize() {
    parent::initialize();
    add_action( 'wp_loaded', array( $this, 'register_empty_cart_message_block_pattern' ) );
}
```

The PR for the implementation above can be found on <https://github.com/woocommerce/woocommerce-blocks/pull/6248/files>.
