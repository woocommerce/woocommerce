# Cache Priming

Covers correct usage of `_prime_post_caches()` to reduce SQL query counts when loading or rendering collections of post-based objects.

## Patterns

### 1. Missing cache priming before iterating post-based objects

**Apply when:** any `array_map` or loop fetches multiple objects by ID using `get_post()`, `wc_get_product()`, `wc_get_order()`, or any function that resolves to a `get_post()` call per item (e.g. a `format_*` helper that calls `get_post()` internally).

**Correct pattern:**

```php
if ( ! empty( $ids ) ) {
    // Prime caches to reduce future queries.
    _prime_post_caches( $ids );
    $products = array_map( 'wc_get_product', $ids );
}
```

The comment `// Prime caches to reduce future queries.` must always sit **inside** the `if` block, directly above the call. Do not place it before the `if`. Place the prime immediately before the loop or `array_map` that consumes the IDs. Exception: if a `do_action` call between the guard and the loop passes the IDs as arguments (e.g. `do_action( 'wc_before_products_starting_sales', $product_ids )`), move the prime before that action so hooked callbacks loading the same objects also benefit from the warmed cache. If the action does not receive the IDs, keep the prime directly above the loop.

`_prime_post_caches()` is a WordPress internal (underscore-prefixed) that has existed since WP 4.1. The minimum supported WordPress version for WooCommerce guarantees its presence — `is_callable( '_prime_post_caches' )` guards are unnecessary and must be removed when encountered. Always wrap in `! empty()` to avoid a no-op SQL on empty arrays.

---

### 2. Missing two-phase image priming when rendering product collections

**Apply when:** Code that fetches products and then renders them (templates, blocks), especially with thumbnails.

**Correct pattern:**

```php
if ( ! empty( $product_ids ) ) {
    // Prime caches to reduce future queries.
    _prime_post_caches( $product_ids );
    $products = array_filter( array_map( 'wc_get_product', $product_ids ), 'wc_products_array_filter_visible' );

    // Prime caches to reduce future queries.
    _prime_post_caches( array_filter( array_map( fn( $p ) => (int) $p->get_image_id(), $products ) ) );
}
```

Applies to: `woocommerce_related_products()`, `woocommerce_upsell_display()`, block type `RelatedProducts`, and any similar rendering functions.

---

### 3. Priming the full ID list instead of only uncached IDs

**Apply when:** `_prime_post_caches()` called on the original full list of IDs, even when an object cache layer (e.g., `OrderCache`) has already resolved some of them.

Prime only the IDs not already in cache:

```php
$uncached_ids = ...; // IDs remaining after object cache lookup
if ( ! empty( $uncached_ids ) ) {
    _prime_post_caches( $uncached_ids );
}
```

---

### 4. Priming at each rendering entry point independently

**Apply when:** Cache priming added in one rendering function but not in the equivalent block type or REST API handler serving the same data.

Blocks and classic templates are separate entry points — each must be audited and primed independently.

**Check pairs:**

- `woocommerce_related_products()` ↔ `RelatedProducts` block type
- `woocommerce_upsell_display()` ↔ any upsells block
- Legacy template functions ↔ StoreApi schema handlers

---

### 5. Prefer native batching arguments over manual priming

**Apply when:** A loop iterates over results from a WordPress query function that natively supports post cache warming.

**Decision process:**

1. Identify the N+1: a loop or `array_map` calls `wc_get_product()`, `get_post()`, or similar on each item.
2. Before adding `_prime_post_caches()`, check whether the data source has a native batching argument.

**`get_comments()`** supports `update_comment_post_cache => true`, which batch-loads the parent post cache as part of the query itself — no separate prime needed. The post type of the parent can be `product`, `order`, `post`, or any other — the argument applies regardless:

```php
$comments = get_comments(
    array(
        'post_type'                 => 'product', // or 'order', 'post', etc.
        'update_comment_post_cache' => true,
        // ...
    )
);
foreach ( $comments as $comment ) {
    $product = wc_get_product( $comment->comment_post_ID ); // cache already warm
}
```

Use `_prime_post_caches()` only when no such native argument exists on the data source.

---

### 6. Do not prime product post caches when iterating order line items — already handled by get_items() and batch priming

**Apply when:** A loop collects product IDs from order line items (via `get_items()` or `get_items( 'line_item' )`) and then calls `_prime_post_caches()` on those IDs.

**Why it is wrong:** Two independent mechanisms already cover this, and both fire before any explicit priming could add value:

1. **Batch path** — when orders are loaded via a query (CPT or HPOS data store), the data store calls `prime_caches_for_orders()` → `prime_order_item_caches_for_orders()` → `prime_product_post_caches_for_order_items()`, which primes all `_product_id` and `_variation_id` values from raw item meta in a single `_prime_post_caches()` call before the caller ever touches the order objects.

2. **Lazy-load path** — `WC_Abstract_Order::get_items()` primes product post caches on first item load per order (`abstract-wc-order.php`, inside the `if ( ! isset( $this->items[ $group ] ) )` branch for `line_item` type).

By the time an explicit `_prime_post_caches()` runs after collecting IDs from a `get_items()` loop, all product post caches are already warm on both CPT and HPOS backends.

**Example**:

```php
// Prime product caches to avoid N+1 queries during serialization.
$product_ids = array();
foreach ( $results['results'] as $order ) {
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        if ( $item instanceof \WC_Order_Item_Product ) {
            $product_ids[] = $item->get_product_id();
            $product_ids[] = $item->get_variation_id();
        }
    }
}
$product_ids = array_unique( array_filter( $product_ids ) );
if ( ! empty( $product_ids ) ) {
    _prime_post_caches( $product_ids, true, true );
}
```

**Recognition pattern:** any block that (a) loops over orders, (b) calls `get_items()` or `get_items( 'line_item' )` inside that loop, and (c) collects `get_product_id()` / `get_variation_id()` values to feed into a subsequent `_prime_post_caches()` call. The entire collect-and-prime block is dead code and can be deleted.

---

### 7. Do not prime after WP_Query — it already handles caching

**Apply when:** Code runs `WP_Query::query()` (or `new WP_Query(...)`) and then calls `_prime_post_caches()` on the returned value.

**Why it is wrong:** `WP_Query` automatically primes the post, meta, and term caches for every post it loads (controlled by `update_post_meta_cache` and `update_post_term_cache`, both `true` by default). Calling `_prime_post_caches()` afterward is redundant.

Additionally, `WP_Query::query()` returns an array of `WP_Post` objects (when no `fields` argument is set), not integer IDs. `_prime_post_caches()` internally calls `intval()` on each item — `intval( WP_Post )` returns `1`, not the post ID. The function silently misbehaves.

**Do not add priming here:**

```php
$result = $query->query( $query_args );
// Wrong — $result is WP_Post[] and WP_Query already primed all caches.
_prime_post_caches( $result );
$products = array_map( 'wc_get_product', $result );
```

Priming is only needed when starting from a raw list of IDs not loaded through `WP_Query` — see pattern 1.

---

## Backward Compatibility

Pass `false` for the `$update_meta_cache` parameter when meta is being handled separately, to avoid double-priming:

```php
_prime_post_caches( $order_ids, true, false ); // skip meta priming, include terms
```
