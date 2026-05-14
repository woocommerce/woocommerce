# Performance patterns for Store API responses

This file is a thin wrapper over the `woocommerce-performance` skill, framed for Store API contexts. Read [`cache-priming.md`](../woocommerce-performance/cache-priming.md) in that skill for the underlying patterns; this file covers where to apply them in Store API code.

## When this matters

Any schema that serialises a **collection** of post-based objects (products, orders, attachments) is a candidate for cache priming. Store API list responses are the most common case:

- `GET /cart` returns N cart line items, each loaded via `wc_get_product()`.
- Any future route returning a collection of products, orders, or attachment-backed resources — each item loaded individually inside the per-item loop.

Without priming, each per-item lookup hits the database individually — classic N+1.

## Where to apply priming in a Store API route

The priming must run **before** the per-item loop and at the level that has the full collection of IDs. Two structural choices:

### Prime in `Schema::get_item_response()` (preferred)

When the schema receives the full collection (e.g., a parent-resource schema's `get_item_response()` receives `$collection['items']` as an array), prime there:

```php
public function get_item_response( $collection ) {
    $items = $collection['items'] ?? array();

    $product_ids = array_filter( array_map(
        static fn( $item ) => (int) ( $item['variation_id'] ?: $item['product_id'] ),
        $items
    ) );

    if ( ! empty( $product_ids ) ) {
        _prime_post_caches( array_unique( $product_ids ) );
    }

    return array(
        // ...
        'items' => array_map(
            fn( $item ) => $this->item_schema->get_item_response( $item ),
            $items
        ),
    );
}
```

This makes the schema the single owner of priming logic. Any consumer that calls `Schema::get_item_response()` — the route, an internal block, an admin tool — gets the prime for free without remembering to do it themselves.

### Don't prime in single-item routes

Routes that return one item (POST returning the added member, single-item GET) don't need priming. `wc_get_product()` on one ID is one query whether you prime or not. Priming a "batch of one" is the same query count with extra ceremony.

### Don't duplicate priming across the route and the schema

If the schema already primes, the route shouldn't prime again. Pick one — schema is preferred. Otherwise the same `_prime_post_caches()` call runs twice on the same IDs (the second is a no-op on warm cache, but it's noise).

## Two-phase priming for products and their images

Products primed via `_prime_post_caches()` does **not** prime their thumbnail attachments. If your schema renders product images (most do), you need a second pass.

See `woocommerce-performance/cache-priming.md` Pattern #2 for the canonical form. Adapted for Store API:

```php
if ( ! empty( $product_ids ) ) {
    $product_ids = array_unique( $product_ids );
    _prime_post_caches( $product_ids );

    $thumbnail_ids = array_filter( array_map(
        static fn( $id ) => (int) get_post_thumbnail_id( $id ),
        $product_ids
    ) );

    if ( ! empty( $thumbnail_ids ) ) {
        _prime_post_caches( array_unique( $thumbnail_ids ), true, true );
    }
}
```

The `true, true` arguments to the second `_prime_post_caches()` enable update_term_cache and update_meta_cache — the latter is critical because `wp_get_attachment_image_src()` reads `_wp_attachment_metadata` from postmeta. Without `update_meta_cache = true`, you've primed the post rows but not the attachment metadata, leaving an N+1 inside the image render loop.

Alternative: `update_post_thumbnail_cache( $wp_query )` is a WordPress-idiomatic helper for this exact pattern. Construct a synthetic `WP_Query` with `posts = [ array of WP_Post ]` and call it. Slightly more ceremony but uses the WP-blessed code path.

## Common N+1 patterns to look for

When reviewing a new Store API schema, check whether each per-item operation has a corresponding batch prime up front:

| Per-item operation | Required prime |
| --- | --- |
| `wc_get_product( $id )` in a loop | `_prime_post_caches( $product_ids )` |
| `get_post_thumbnail_id()` + image rendering | Plus `_prime_post_caches( $thumbnail_ids, true, true )` |
| `get_term( $id )` for taxonomy attributes | `_prime_term_caches( $term_ids )` (or use `update_term_cache = true` on the post prime) |
| `get_user_meta()` per user in a list | `update_meta_cache( 'user', $user_ids )` |
| Multiple `get_option()` calls | `wp_prime_option_caches( $keys )` — see `woocommerce-performance/options-cache-priming.md` |

When in doubt, ask: "What's the per-item DB cost?" If a single response triggers N reads of the same kind, there's a prime opportunity.

## What this skill doesn't cover

- **General priming patterns and edge cases** — see [`cache-priming.md`](../woocommerce-performance/cache-priming.md) in `woocommerce-performance`.
- **Options priming** — see [`options-cache-priming.md`](../woocommerce-performance/options-cache-priming.md).
- **OrderCache and other Woo-specific cache layers** — `woocommerce-performance/cache-priming.md` covers these.

This file is just the Store-API-specific framing. The substantive patterns live in the performance skill.

## Anti-patterns to avoid

- **Priming inside the per-item loop.** Defeats the point. The prime call must happen once, before the loop, on the full ID list.
- **Priming products without their images.** Half-fix; the image render loop is still N+1. Apply two-phase priming when rendering product collections that include images.
- **Priming on single-item routes.** Adds code without saving any queries. Skip.
- **Forgetting `update_meta_cache = true` when priming attachments.** Primes the attachment posts but leaves `_wp_attachment_metadata` un-cached, which is what `wp_get_attachment_image_src()` actually reads.
