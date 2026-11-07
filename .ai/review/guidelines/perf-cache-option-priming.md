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
- Rules from `.ai/skills/woocommerce-performance`.

**What broke:** Missing priming was fixed after release again and again: cold per-ID loads in loops and sequential reads of non-autoloaded or unsaved options each added a query per item.

**Pattern to catch:**

- Loops calling `wc_get_product` / `get_post` per raw ID with no `_prime_post_caches`, bulk loader, or native batching argument. For `wc_get_order` the loader is the order data store's bulk path; `_prime_post_caches` does not load HPOS tables.
- Product collections rendered with images and no attachment prime for the IDs the render path reads (`ProductUtil::prime_image_caches()` or an explicit `_prime_post_caches( $attachment_ids, false, true )`).
- Sequential `get_option()` calls on non-autoloaded or unsaved options with no `wp_prime_option_caches` before the first read.
- Removing an effective prime.
- Dead priming: after a full-post `WP_Query` with default cache flags, on `get_items()` product IDs the order loader already primed, or behind `is_callable` guards.

**How to confirm:** See where the IDs come from (`'fields' => 'ids'`, disabled cache flags, or full posts) and which IDs the consumer finally reads: items added by `woocommerce_order_get_items` and in-memory items are not covered by the order loader's prime, and the lazy `get_items()` path primes the variation but not its parent. Check whether the options are saved and autoloaded (WC settings API options are once saved); a name already in `alloptions` or `notoptions` costs no SQL. Check transient names are primed only under `! wp_using_ext_object_cache() && ! wp_installing()`, and `_site_transient_*` names through `wp_prime_network_option_caches` on multisite paths.

**Don't flag when:** Saved autoloaded options. Loads through order `get_items()` that the earlier prime covered. Tests and fixtures.
