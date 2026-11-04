# Cache Priming

Covers correct usage of `_prime_post_caches()` and existing bulk loaders to reduce repeated SQL reads when loading or rendering collections. Batch proven cache misses close to the consumer; avoid loading data that the caller will not use. Priming, caching hydrated objects, and retaining mutable instances are different optimizations.

## Patterns

### 1. Missing cache priming before iterating post-based objects

**Apply when:** an `array_map` or loop fetches multiple post-based objects by ID using `get_post()`, `wc_get_product()`, or a helper that resolves to repeated post reads, and the source has not already warmed the required caches.

**Correct pattern:**

```php
$products = array();
if ( ! empty( $product_ids ) ) {
    _prime_post_caches( $product_ids );
    $products = array_filter( array_map( 'wc_get_product', $product_ids ) );
}
```

Use integer IDs from a validated source. Place the prime before the first read of the batch, usually immediately before the loop or `array_map`. If an intervening hook actually consumes the same IDs, prime before that hook so its callbacks also benefit. Preserve hook timing and overridable methods when changing the loading path.

`_prime_post_caches()` warms WordPress posts and, depending on its arguments, terms and post metadata. It is not an HPOS order-table loader: a loop using `wc_get_order()` needs the WooCommerce bulk-order/data-store path for the active backend, not a blanket post-cache prime. See pattern 6 for the separate product reads made through order line items.

The helper is available in WooCommerce's supported WordPress versions; an availability guard is unnecessary in this project. An empty guard makes optional batches clear, but the helper also short-circuits empty input. It batches uncached post reads and can prime related metadata and terms; the query count depends on cache state and term work, so do not promise exactly two queries regardless of collection size.

Select the term/meta flags from downstream reads. [#54984](https://github.com/woocommerce/woocommerce/pull/54984) benefited from post-only priming; [#61057](https://github.com/woocommerce/woocommerce/pull/61057) found full term/meta priming faster for product responses. Neither result establishes universal flag values for every consumer.

Add a comment when placement or a cache dependency needs explanation. A comment that only restates the prime call is not required.

---

### 2. Missing two-phase image priming when rendering product collections

**Apply when:** code loads products and then renders their images, such as related products, upsells, blocks, or a REST response that resolves attachment URLs.

Product metadata must be available before discovering image IDs. Prime or reuse the product caches first, resolve the products that will actually render, then prime the needed attachment records.

**Correct pattern — simple product collections:**

This example assumes the consumer renders only each visible product's featured image. It does not prime galleries the consumer will never use.

```php
$products = array();
if ( ! empty( $product_ids ) ) {
    _prime_post_caches( $product_ids );
    $products = array_filter(
        array_filter( array_map( 'wc_get_product', $product_ids ) ),
        'wc_products_array_filter_visible'
    );

    $thumbnail_ids = array_filter(
        array_unique( array_map( static fn( $product ) => (int) $product->get_image_id(), $products ) )
    );
    if ( ! empty( $thumbnail_ids ) ) {
        _prime_post_caches( $thumbnail_ids, false, true );
    }
}
```

Here attachment metadata is needed by image rendering, while attachment terms are not. Change those flags if the actual consumer has different needs.

**Common locations to check:** `woocommerce_related_products()`, `woocommerce_upsell_display()`, the `RelatedProducts` block type, and collection serializers that load image URLs or attachment details.

**Correct pattern — variation collections:**

The two phases still apply, but the image IDs must match the values the consumer uses. A variation's `get_image_id( 'view' )` can inherit its parent's image and apply filters. Warming variation metadata alone does not make raw `_thumbnail_id` equivalent to that getter.

Normalize the collected IDs before priming: cast valid IDs to integers, remove empty values, and deduplicate. The examples below assume the ID values come from the expected metadata or getter contract; handle invalid filtered values at the boundary when that contract is not guaranteed.

**Form A — IDs only, with a consumer that uses the same raw metadata:**

Use this form only when the consumer itself reads raw variation `_thumbnail_id` and `_product_image_gallery` values. Having IDs rather than loaded objects is not sufficient justification: raw meta omits getter filters and the parent-image fallback. `_product_image_gallery` is stored as a comma-separated string (`"12,34,56"`), not a serialized array.

```php
if ( ! empty( $variation_ids ) ) {
    _prime_post_caches( $variation_ids, false, true );

    $attachment_groups = array();
    foreach ( $variation_ids as $variation_id ) {
        $attachment_groups[] = array( get_post_meta( $variation_id, '_thumbnail_id', true ) );
        $attachment_groups[] = explode( ',', (string) get_post_meta( $variation_id, '_product_image_gallery', true ) );
    }
    $attachment_ids = array_filter( array_unique( array_map( 'intval', array_merge( ...$attachment_groups ) ) ) );
    if ( ! empty( $attachment_ids ) ) {
        _prime_post_caches( $attachment_ids, false, true );
    }
}
```

The variation metadata reads can use the warmed cache. That does not mean the entire rendering path performs no additional work: inspect parent data, filters, and the attachment fields the consumer reads.

**Form B — objects already loaded:**

When `WC_Product_Variation` objects are already available, use their getters in the consumer's context, typically the default `'view'` context. `get_image_id()` returns a scalar; `get_gallery_image_ids()` returns an array. The initial load below is illustrative: reuse an existing loaded collection instead of loading it again.

```php
$variations = array();
if ( ! empty( $variation_ids ) ) {
    _prime_post_caches( $variation_ids );
    $variations = array_filter( array_map( 'wc_get_product', $variation_ids ) );
}

// Apply the consumer's visibility and stock filters before collecting images.
if ( ! empty( $variations ) ) {
    $attachment_groups = array();
    foreach ( $variations as $variation ) {
        $attachment_groups[] = array( $variation->get_image_id() );
        $attachment_groups[] = $variation->get_gallery_image_ids();
    }
    $attachment_ids = array_filter( array_unique( array_map( 'intval', array_merge( ...$attachment_groups ) ) ) );
    if ( ! empty( $attachment_ids ) ) {
        _prime_post_caches( $attachment_ids, false, true );
    }
}
```

**Choosing between forms:** prefer object getters when the render path uses object getters. Scope the attachment prime to the post-filter collection so excluded variations do not contribute images that will never render. If loading variations also causes repeated parent-product reads, inspect and batch those reads at their existing loading boundary.

**Prefer `ProductUtil::prime_image_caches()` when its scope matches the consumer.** For already-loaded `WC_Product` objects whose featured and gallery images are needed, it collects image IDs through the default view getters, normalizes them, and primes the attachments:

```php
wc_get_container()->get( \Automattic\WooCommerce\Internal\Utilities\ProductUtil::class )
    ->prime_image_caches( array_values( $variations ) );
```

`WC_Product_Variation` extends `WC_Product`, so variation collections are accepted. Pass a numerically indexed copy: the current helper preserves collection keys before unpacking gallery arrays into `array_merge()`, which can fail for string-keyed collections. `array_values()` leaves the caller's keyed collection intact. An explicit collect-and-prime path can be appropriate when the consumer needs only featured images, a different getter context, or a narrower cache scope. A legacy `includes/` file is not by itself a reason to assume the DI container is unavailable; check the actual lifecycle and dependencies.

**Scope priming to attachment IDs your code actually accesses.** Calls such as `get_post()`, `wp_attachment_is_image()`, `wp_get_attachment_image_src()`, and `get_the_title()` can load attachment records or metadata. Merely returning integer image IDs, such as a response's `gallery_image_ids`, does not need attachment hydration. [#63006](https://github.com/woocommerce/woocommerce/pull/63006) and [#69319](https://github.com/woocommerce/woocommerce/pull/69319) illustrate collection-specific placement.

---

### 3. Priming the full ID list instead of only uncached IDs

**Apply when:** another cache layer has already resolved some objects, but the code still prepares a post-cache prime for the original full list.

Resolve object-cache hits first, then prime the remaining IDs whose hydration actually uses WordPress posts:

```php
if ( ! empty( $uncached_product_ids ) ) {
    _prime_post_caches( $uncached_product_ids );
}
```

`_prime_post_caches()` itself skips cached post rows. The opportunity here is avoiding unnecessary list-building and hydration after a higher-level object cache has already satisfied the consumer. Do not add a second per-ID cache lookup merely to duplicate the helper's own check.

For order objects, use the active backend's loader for the unresolved IDs; HPOS data is not stored in the post cache. The [#62820 review](https://github.com/woocommerce/woocommerce/pull/62820#discussion_r2710344921) illustrates narrowing cache warming after object-cache hits.

---

### 4. Priming at each rendering entry point independently

**Apply when:** priming is added to one rendering function while an equivalent block or REST handler reads the same kind of data through another path.

Audit each entry point and its existing loading behavior. One path may already be warm while another needs a batch; a shared loader may cover both. Do not assume that a change to a classic template also reaches a block or Store API response.

**Check pairs:**

- `woocommerce_related_products()` ↔ `RelatedProducts` block type.
- `woocommerce_upsell_display()` ↔ an upsells block that renders the same products.
- Legacy template functions ↔ Store API schema handlers.

Prefer a shared loading boundary when its bounded callers benefit. Keep priming before the first actual consumer, without moving broad hydration into global initialization simply to reduce a later query count. [#65276](https://github.com/woocommerce/woocommerce/pull/65276) warms only the page records needed by later checks.

---

### 5. Prefer native batching arguments over manual priming

**Apply when:** a loop iterates results from a query API that can warm the caches needed by the consumer.

**Decision process:**

1. Identify repeated reads: a loop or `array_map` calls `wc_get_product()`, `get_post()`, or another loader for each item.
2. Check whether the data source offers native batching and what cache layers it warms.
3. Use the native option when it covers the required reads; batch any remaining proven gaps separately.

**`get_comments()` example:** `update_comment_post_cache => true` batches parent post-cache loading. For a bounded page of product comments:

```php
$comments = get_comments(
    array(
        'post_type'                 => 'product',
        'number'                    => 20,
        'update_comment_post_cache' => true,
    )
);
foreach ( $comments as $comment ) {
    $product = wc_get_product( $comment->comment_post_ID );
}
```

The native flag warms parent WordPress posts; inspect whether the subsequent product loader also needs terms or metadata that this path has not loaded. Do not assume that warming posts completely hydrates WooCommerce products. The flag is also not an HPOS order loader, even when comments refer to orders.

Use an explicit batch for needed data that the native argument does not cover. The presence of a native argument does not forbid all additional priming.

---

### 6. Check existing product priming when iterating order line items

**Apply when:** code collects product IDs from `get_items()` or `get_items( 'line_item' )` and then calls `_prime_post_caches()` on those IDs.

**Why it may be redundant:** existing order loading paths already prime many of these products:

1. **Batch path** — order data stores can call `prime_caches_for_orders()` → `prime_order_item_caches_for_orders()` → `prime_product_post_caches_for_order_items()`, which collects product and variation IDs from stored item metadata.
2. **Lazy-load path** — `WC_Abstract_Order::get_items()` primes product post caches when it first loads line items for an order.

**Recognition example:** this collect-and-prime loop warrants checking against the existing loading path before adding or retaining it:

```php
$product_ids = array();
foreach ( $results['results'] as $order ) {
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        if ( $item instanceof \WC_Order_Item_Product ) {
            $product_ids[] = $item->get_product_id();
            $product_ids[] = $item->get_variation_id();
        }
    }
}
$product_ids = array_filter( array_unique( array_map( 'intval', $product_ids ) ) );
if ( ! empty( $product_ids ) ) {
    _prime_post_caches( $product_ids );
}
```

**Prove coverage before deleting it.** Compare the IDs the later consumer actually reads with the IDs primed earlier. Lazy loading may prime a variation rather than both the variation and its parent. Already-populated in-memory items, items appended by `woocommerce_order_get_items`, and filtered product IDs can differ from the stored IDs used by the bulk path.

When the sets match, another collect-and-prime loop duplicates work and can be removed. When they differ, retain or add only the missing batch at the consumer's loading boundary. Do not label every `get_items()`-based prime as dead code.

---

### 7. Check WP_Query result shape and cache flags before adding priming

**Apply when:** code calls `WP_Query::query()` or `new WP_Query(...)` and then primes the returned results.

A full-object `WP_Query` with default cache settings normally primes its results. Inspect `fields`, `cache_results`, `update_post_meta_cache`, and `update_post_term_cache` before adding another prime. ID-only queries and disabled cache flags need separate assessment; they do not have the same warming behavior as the default full-object path.

`WP_Query::query()` normally returns `WP_Post` objects, not integer IDs. Never pass those objects to `_prime_post_caches()`: converting an object to an integer does not extract its post ID.

**Do not add this prime to a default full-object query:**

```php
$posts = $query->query( $query_args );
_prime_post_caches( $posts ); // Incorrect: this API expects IDs, not WP_Post objects.
```

When a separate, proven cache gap requires IDs from a full-object result, extract them explicitly:

```php
$product_ids = array_map( 'intval', wp_list_pluck( $posts, 'ID' ) );
```

Extraction alone does not justify priming. For an ID-only query, inspect which later calls hydrate those IDs and use pattern 1 only for caches that are still needed. Do not change a caller's deliberate cache settings without understanding its memory and lifecycle constraints.

---

## Backward Compatibility

Use the active data store for the object type and preserve hooks and overrides in its loading path. Select `_prime_post_caches()` flags from what the downstream consumer needs, rather than applying the same combination everywhere.

For example, when a post-based consumer needs terms and a separate loader already guarantees metadata is warm:

```php
_prime_post_caches( $post_ids, true, false );
```

That is a conditional example, not a general order-loading recipe. Either term or metadata priming can be disabled when downstream readers do not need that cache or an earlier loader already guarantees it is warm.

## Check the cost and lifetime

Large batches can reduce round trips while increasing memory, bytes fetched, or unwanted hydration. Keep batches bounded to the page or collection being consumed. Measure the actual endpoint in cold and warm states, with and without persistent object caching, and with representative collection sizes.

Do not infer a full-page improvement from an isolated method benchmark. [#69100](https://github.com/woocommerce/woocommerce/pull/69100#issuecomment-5912552732) rejected a fast path after consumer-level measurements.

For hierarchical term priming, maintain a visited set and termination behavior for malformed or cyclic data. A few extra queries are preferable to a nonterminating traversal ([#70278](https://github.com/woocommerce/woocommerce/pull/70278#discussion_r4386236231)).

When moving beyond priming into value or instance caching, use the lifecycle checks in [database operations](database-operations.md#make-caching-and-scheduling-correct-across-their-lifecycle): identity and cloning, cache scope, mutation, invalidation, eviction, and upgrade behavior. Document non-obvious constraints that future changes must preserve.
