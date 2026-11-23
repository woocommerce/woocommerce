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

All hooks must have a docblock with a description of when the hook fires, a `@since` annotation, and `@param` tags for each parameter. Formatting and `@since` placement follow the same rules as method docblocks — see `code-entities.md` in this skill.

For the `@since` version:

- New hooks: use the version from `includes/class-woocommerce.php` on trunk, removing the `-dev` suffix
- Existing hooks missing a docblock: use `git log -S "hook_name"` to find the version that introduced the hook

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

## Recursion Through Other Plugins' Callbacks

A hook callback can run again before it returns. When it reads data other plugins can filter, such as meta, options, permalinks, GUIDs, or capabilities, their callback on that read can call back into your hook. Neither callback is wrong on its own, but together they recurse until PHP runs out of memory, on every request that reaches the hook.

An example of this type of recursion shipped in 11.3.0 RC-1 ([#70198](https://github.com/woocommerce/woocommerce/pull/70198)). A `get_the_guid` callback read `_wp_attached_file` with `get_post_meta()`. Real Media Library's `get_post_metadata` callback for that key calls `get_the_guid()`, so stores running both ran out of memory on most pages with media.

Watch for this when a callback on a hook that runs many times per request (not an exclusive list, but examples: `get_the_guid`, `attachment_link`, `post_link`, `the_content`, `get_post_metadata`, `option_*`, `user_has_cap`, `map_meta_cap`) reads filtered data or calls a function that fires the same hook. When it does:

- Run the cheap checks first (post type, values the hook already passed in) so most calls return before the filtered read.
- Add a re-entrancy guard around the filtered read and reset it in `finally`. Classes from the DI container are shared, so an instance property is enough.
- Have the nested call return its input unchanged, and check that the outer call still reaches the right result. Returning a "safe" value from the nested call can make the outer result wrong.
- If a test is useful and necessary, make sure any recursion testing fails past a small limit rather than crashing the test environment.

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
