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
 * @since 8.2.0
 *
 * @param int $order_id The processed order ID.
 * @param array $order_data The order data.
 */
do_action( 'woocommerce_order_processed', $order_id, $order_data );
```

## Hook Documentation Requirements

All hooks must have docblocks that include, in this order:

- Description of when the hook fires
- `@since` annotation with the version number, right after the description and before the `@param` tags, with a blank comment line on either side, as in [WordPress's inline documentation standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/#4-hooks-actions-and-filters)
    - For new hooks: Use the version from `includes/class-woocommerce.php` on trunk, removing `-dev` suffix
    - For existing hooks: Use `git log -S "hook_name"` to find when it was introduced
- `@param` tags for each parameter passed to the hook

**Action hook example:**

```php
/**
 * Fires after a product is saved.
 *
 * @since 9.5.0
 *
 * @param int        $product_id The product ID.
 * @param WC_Product $product    The product object.
 */
do_action( 'woocommerce_product_saved', $product_id, $product );
```

**Filter hook example:**

```php
/**
 * Filters the product price before display.
 *
 * @since 9.5.0
 *
 * @param string     $price   The formatted price.
 * @param WC_Product $product The product object.
 */
$price = apply_filters( 'woocommerce_product_price', $price, $product );
```

## Regenerating the Published Hook Docs

Hooks in `plugins/woocommerce/src/Blocks` and `plugins/woocommerce/src/StoreApi` are published as a developer reference generated from their docblocks:

- `plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/actions.md`
- `plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/hooks/filters.md`

After adding, removing, or editing a hook or its docblock in either directory, regenerate them and commit the result alongside the code change:

```bash
pnpm --filter=@woocommerce/block-library build:docs
```

Nothing in CI checks these files, so a skipped run leaves the published reference silently stale.

Points to keep in mind:

- Never hand-edit `actions.md` or `filters.md`. Fix the source docblock and regenerate.
- Only hooks in `src/Blocks` and `src/StoreApi` reach the docs, so a hook elsewhere needs no regeneration.
- Keeping it that way takes maintenance. The generator scans all of `plugins/woocommerce/src` except the directories listed under `extra.wp-hooks.ignore-files` in `plugins/woocommerce/client/blocks/composer.json`. When you add a new top-level directory to `src/`, add it to that list as well — otherwise the next `build:docs` run stops with an error the first time it finds a documented hook there.
- The command also refreshes `docs/block-development/reference/block-references.md`, which is the one generated file CI does validate.
- Docblock text lands in the docs as Markdown. Wrap literal angle-bracket placeholders in backticks (`` `<hook-name>` ``) so markdownlint doesn't read them as inline HTML.
- A hook with no docblock at the call site is skipped by the generator, and `internal_`-prefixed hooks are filtered out on purpose.

See `plugins/woocommerce/client/blocks/bin/hook-docs/README.md` for how the pipeline works and how to change its scope.
