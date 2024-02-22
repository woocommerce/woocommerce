# Translations for lazy-loaded components

The Mini-Cart block as well as inner blocks of the Cart and the Checkout blocks are lazy-loaded. To lazy-load them, the translation chunks needs to be registered. This takes place in `/src/BlockTypes/AbstractBlock.php`:

```php
/**
 * Injects Chunk Translations into the page so translations work for lazy loaded components.
 *
 * The chunk names are defined when creating lazy loaded components using webpackChunkName.
 *
 * @param string[] $chunks Array of chunk names.
 */
protected function register_chunk_translations( $chunks ) {
  foreach ( $chunks as $chunk ) {
    $handle = 'wc-blocks-' . $chunk . '-chunk';
    $this->asset_api->register_script( $handle, $this->asset_api->get_block_asset_build_path( $chunk ), [], true );
    wp_add_inline_script(
      $this->get_block_type_script( 'handle' ),
      wp_scripts()->print_translations( $handle, false ),
      'before'
    );
    wp_deregister_script( $handle );
  }
}
```

## Lazy-loaded translations of the Cart block

The translations of the inner blocks of the Cart block are loaded in this function in `src/BlockTypes/Cart.php`:

```php
/**
 * Register script and style assets for the block type before it is registered.
 *
 * This registers the scripts; it does not enqueue them.
 */
protected function register_block_type_assets() {
  parent::register_block_type_assets();
  $chunks        = $this->get_chunks_paths( $this->chunks_folder );
  $vendor_chunks = $this->get_chunks_paths( 'vendors--cart-blocks' );

  $this->register_chunk_translations( array_merge( $chunks, $vendor_chunks ) );
}
```

## Lazy-loaded translations of the Checkout block

The translations of the inner blocks of the Cart block are loaded in this function in `src/BlockTypes/Checkout.php`:

```php
/**
 * Register script and style assets for the block type before it is registered.
 *
 * This registers the scripts; it does not enqueue them.
 */
protected function register_block_type_assets() {
  parent::register_block_type_assets();
  $chunks        = $this->get_chunks_paths( $this->chunks_folder );
  $vendor_chunks = $this->get_chunks_paths( 'vendors--cart-blocks' );
  $shared_chunks = [ 'cart-blocks/order-summary-shipping--checkout-blocks/order-summary-shipping-frontend' ];
  $this->register_chunk_translations( array_merge( $chunks, $vendor_chunks, $shared_chunks ) );
}
```

## Lazy-loaded translations of the Mini-Cart block

The translations of the inner blocks of the Mini-Cart block are loaded in this function in `src/BlockTypes/MiniCart.php`:

```php
/**
 * Prepare translations for inner blocks and dependencies.
 */
protected function get_inner_blocks_translations() {
	$wp_scripts   = wp_scripts();
	$translations = array();

	$chunks        = $this->get_chunks_paths( $this->chunks_folder );
	$vendor_chunks = $this->get_chunks_paths( 'vendors--mini-cart-contents-block' );
	$shared_chunks = [ 'cart-blocks/cart-line-items--mini-cart-contents-block/products-table-frontend' ];

	foreach ( array_merge( $chunks, $vendor_chunks, $shared_chunks ) as $chunk ) {
		$handle = 'wc-blocks-' . $chunk . '-chunk';
		$this->asset_api->register_script( $handle, $this->asset_api->get_block_asset_build_path( $chunk ), [], true );
		$translations[] = $wp_scripts->print_translations( $handle, false );
		wp_deregister_script( $handle );
	}

	$translations = array_filter( $translations );

	return implode( '', $translations );
}
```

## Register lazy-loading components

The functions above show how the chunks are lazy-loaded. These chunks are currently defined in `assets/js/blocks/checkout/inner-blocks/register-components.ts` and look like this:

```js
[...]

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_CONTACT_INFORMATION,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/contact-information" */ './checkout-contact-information-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_SHIPPING_ADDRESS,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/shipping-address" */ './checkout-shipping-address-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: metadata.CHECKOUT_BILLING_ADDRESS,
	component: lazy( () =>
		import(
			/* webpackChunkName: "checkout-blocks/billing-address" */ './checkout-billing-address-block/frontend'
		)
	),
} );

[...]
```

Please note that the snippet above serves for demo purposes and only lists a few of the definitions.

## Related PRs

Lazy-loading translations within the WooCommerce Blocks plugin changed over time. The following PRs allow to dive deeper into the topic:

-   [Refactor webpack splitting to fix missing translation](https://github.com/woocommerce/woocommerce-blocks/pull/6420)
-   [Lazy load missing translation files](https://github.com/woocommerce/woocommerce-blocks/pull/5112)
-   [Extract function from lazyLoadScript to simplify code](https://github.com/woocommerce/woocommerce-blocks/pull/4631)
-   [Lazy Loading Atomic Components](https://github.com/woocommerce/woocommerce-blocks/pull/2777)
