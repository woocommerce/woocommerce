# Performance patterns for Store API responses

This file is a thin wrapper over the `woocommerce-performance` skill, framed for Store API contexts. Read [`cache-priming.md`](../woocommerce-performance/cache-priming.md) in that skill for the underlying patterns; this file covers where to apply them in Store API code.

## When this matters

Any schema that serialises a **collection** of post-based objects (products or attachments) is a candidate for cache priming. Store API list responses are the most common case:

- `GET /cart` returns N cart line items, each loaded via `wc_get_product()`.
- Any future route returning a collection of products or attachment-backed resources, with individual loads inside the per-item loop.

Trace existing loading first: cold per-item reads can cause N+1 queries, while earlier loaders or caches may already cover them. Orders need the active WooCommerce data-store/bulk-order path; `_prime_post_caches()` does not load HPOS order tables.

## Where to apply priming in a Store API route

Place needed priming **before the first consuming read**, at a boundary with the bounded collection of IDs. A schema that owns the collection is one suitable place:

### Prime in `Schema::get_item_response()` (preferred)

When the schema receives the full collection (for example, a parent-resource schema's `get_item_response()` receives `$collection['items']` as an array), and earlier loaders have not covered the required reads, prime there. This example assumes validated items whose consumer reads the variation when present, otherwise the parent product:

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

This gives callers of the collection schema a shared loading boundary. Check whether child responses also read parent products or filtered IDs beyond the example's selection, and batch any proven gaps. If an earlier hook or loader consumes the same collection, prime before that first read instead.

### Check collections inside single-item routes

A route loading one product has no cross-product batch to optimize. Do not add a prime solely because it calls `wc_get_product()`; that load can involve several caches and queries. A single-item response can still contain a collection, such as gallery attachments, whose cold reads benefit from batching.

### Don't duplicate priming across the route and the schema

If the schema already covers the same IDs and caches before their first use, the route should reuse that coverage. The helper skips cached data, but a duplicate call still builds and checks the batch. Pick the shared boundary that serves the actual consumers.

## Two-phase priming for products and their images

Priming product posts does **not** prime their image attachments. If the schema resolves image URLs or details, check whether attachment posts and metadata are already warm; batch any uncovered reads before rendering.

See [`cache-priming.md` Pattern 2](../woocommerce-performance/cache-priming.md#2-missing-two-phase-image-priming-when-rendering-product-collections) for featured/gallery IDs, variation fallback, and getter filters. This narrower example covers a response that reads only WordPress thumbnail IDs and whose product IDs are already the final render set:

```php
if ( ! empty( $product_ids ) ) {
    $product_ids = array_unique( $product_ids );
    _prime_post_caches( $product_ids );

    $thumbnail_ids = array_filter( array_map(
        static fn( $id ) => (int) get_post_thumbnail_id( $id ),
        $product_ids
    ) );

    if ( ! empty( $thumbnail_ids ) ) {
        _prime_post_caches( array_unique( $thumbnail_ids ), false, true );
    }
}
```

The `false, true` arguments skip attachment terms and prime metadata needed by image rendering, including `_wp_attachment_metadata`. Omitting metadata can leave repeated cold reads; enable term priming only when the consumer needs those terms. Existing warm caches can make either batch unnecessary.

This thumbnail-only example is not sufficient for a response that also renders galleries. `ProductSchema::get_images()` reads featured and gallery IDs through product getters and primes attachments per product. For collection batching, match that final consumed set and getter context. When loaded products need both featured and gallery images, `ProductUtil::prime_image_caches( array_values( $products ) )` is a suitable helper; see the canonical reference for its term/meta flags and narrower alternatives.

For an existing `WP_Query` whose consumer uses WordPress thumbnail IDs, `update_post_thumbnail_cache( $wp_query )` batches the featured attachments. It does not collect WooCommerce galleries or replace product getter behavior.

## Common N+1 patterns to look for

When reviewing a new Store API schema, trace which reads remain cold before choosing a batch:

| Per-item operation | Candidate batch for uncovered reads |
| --- | --- |
| `wc_get_product( $id )` in a loop | `_prime_post_caches( $product_ids )` with flags matching downstream reads |
| Featured/gallery image rendering | Prime the consumed attachment IDs, usually with `( $attachment_ids, false, true )` when terms are unused |
| `get_term( $id )` for taxonomy attributes | `_prime_term_caches( $term_ids )`, or reuse post-term priming if it covers those IDs |
| `get_user_meta()` per user in a list | `update_meta_cache( 'user', $user_ids )` when metadata is not already warm |
| Multiple `get_option()` calls | `wp_prime_option_caches( $keys )` for bounded cold non-autoloaded or missing keys; saved autoloaded and positively/negatively cached keys normally need no extra prime |

When in doubt, ask: "What's the per-item DB cost?" If a single response triggers N reads of the same kind, there's a prime opportunity.

## What this skill doesn't cover

- **General priming patterns and edge cases** — see [`cache-priming.md`](../woocommerce-performance/cache-priming.md) in `woocommerce-performance`.
- **Options priming** — see [`options-cache-priming.md`](../woocommerce-performance/options-cache-priming.md).
- **Higher-level cache hits and order storage** — [cache priming, Pattern 3](../woocommerce-performance/cache-priming.md#3-priming-the-full-id-list-instead-of-only-uncached-ids) explains the boundary. For the current `OrderCache` integration and data-store dispatch, inspect `WC_Order_Factory::get_orders()` in `plugins/woocommerce/includes/class-wc-order-factory.php`.

This file is just the Store-API-specific framing. The substantive patterns live in the performance skill.

## Anti-patterns to avoid

- **Batching only within each item when the collection is available.** A product's own gallery batch can help, but collection-level batching can combine cold reads across products.
- **Leaving consumed image data cold.** Check both featured and gallery reads after product priming; raw image IDs alone need no attachment hydration.
- **Priming a single object without a demonstrated benefit.** Look for an actual collection of cold reads, including attachments within a single response.
- **Omitting required attachment metadata.** Post-only priming can leave metadata reads cold. Preserve existing coverage and use `update_meta_cache = true` when needed.
