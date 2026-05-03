# Working with Hooks

## Hook Callback Naming Convention

Name hook callback methods: `handle_{hook_name}` with `@internal` annotation.

**Examples:**

```php
/**
 * Handle the woocommerce_init hook.
 *
 * @internal
 */
public function handle_woocommerce_init() {
    // Initialize components
}

/**
 * Handle the woocommerce_before_checkout hook.
 *
 * @internal
 *
 * @param WC_Checkout $checkout The checkout object.
 */
public function handle_woocommerce_before_checkout( $checkout ) {
    // Setup checkout process
}
```

## Hook Docblocks

If you modify a line that fires a hook without a docblock:

1. Add docblock with description and `@param` tags
2. Use `git log -S "hook_name"` to find when it was introduced
3. Add `@since` annotation with that version

```php
/**
 * Fires after an order has been processed.
 *
 * @param int $order_id The processed order ID.
 * @param array $order_data The order data.
 *
 * @since 8.2.0
 */
do_action( 'woocommerce_order_processed', $order_id, $order_data );
```

## Hook Documentation Requirements

All hooks must have docblocks that include:

- Description of when the hook fires
- `@param` tags for each parameter passed to the hook
- `@since` annotation with the version number (last line, with blank line before)
    - For new hooks: Use the version from `includes/class-woocommerce.php` on trunk, removing `-dev` suffix
    - For existing hooks: Use `git log -S "hook_name"` to find when it was introduced

**Action hook example:**

```php
/**
 * Fires after a product is saved.
 *
 * @param int        $product_id The product ID.
 * @param WC_Product $product    The product object.
 *
 * @since 9.5.0
 */
do_action( 'woocommerce_product_saved', $product_id, $product );
```

**Filter hook example:**

```php
/**
 * Filters the product price before display.
 *
 * @param string     $price   The formatted price.
 * @param WC_Product $product The product object.
 *
 * @since 9.5.0
 */
$price = apply_filters( 'woocommerce_product_price', $price, $product );
```
