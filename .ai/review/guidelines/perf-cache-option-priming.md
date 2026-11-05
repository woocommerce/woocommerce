# Cache and option priming

**Source:**

- #66498: variation images not primed.
- #66350: `count_matching_variations`.
- #65436: product collection image caches.
- #65468: analytics extended info.
- #66774: cart page queries.
- #69319 / #69301: variable product transients.
- #68043: N+1 in `wc_get_formatted_variation`.
- #65883: feature-flag and gateway options.
- Conditions and examples: [cache priming](../../skills/woocommerce-performance/cache-priming.md) and [option cache priming](../../skills/woocommerce-performance/options-cache-priming.md).

**What broke:** Cold per-ID loads and sequential option reads caused repeated queries in these paths. Existing cache coverage and the active storage backend determine whether another batch helps.

**Pattern to catch:**

- Repeated cold post reads through `wc_get_product()` / `get_post()` without effective batching. For `wc_get_order()`, inspect the active WooCommerce data-store/bulk-order loader; `_prime_post_caches()` does not load HPOS tables.
- Product images whose consumed attachment posts or metadata remain cold. Match the final rendered collection's featured/gallery IDs and getter behavior; `ProductUtil::prime_image_caches()` is one possible batch, not a mandatory helper.
- Sequential cold non-autoloaded or missing option reads that a bounded `wp_prime_option_caches()` call can batch.
- Removing an effective prime.
- Duplicating proven cache coverage: inspect `WP_Query` cache flags and the final product IDs consumed after `get_items()` filters before calling a prime dead. Availability guards for priming helpers present in all supported WordPress versions are redundant.

**How to confirm:** Trace the first read, final ID set, query flags, and existing loader coverage. Check actual option existence, autoload state, and positive/negative cache entries; settings definitions alone do not establish stored state. Ordinary transient backing options need `! wp_using_ext_object_cache() && ! wp_installing()`. Site transients need the correct network-option API and network ID on multisite under the same storage guard. Missing names can grow the negative cache as new unique names accumulate, not on every repeated call.

**Don't flag when:** The required data is already warm, including saved autoloaded options and order-item product IDs covered by an earlier prime. Do not exempt hook-added or in-memory items without checking coverage. Tests and fixtures remain out of scope.
