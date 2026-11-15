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

**What broke:** Missing priming was fixed after release again and again: per-ID loads in loops and sequential non-autoloaded option reads each added a query per item.

**Pattern to catch:** - Loops calling `wc_get_product` / `wc_get_order` / `get_post` per raw ID without `_prime_post_caches`.

- Product collections rendered with images without `ProductUtil::prime_image_caches()`.
- Sequential non-autoloaded `get_option()` calls (use `wp_prime_option_caches`).
- Removing an effective prime.
- Dead priming: after a full-post `WP_Query`, on order line items, or behind `is_callable` guards.

**How to confirm:** See where the IDs come from (`'fields' => 'ids'` or full posts). Check whether the options are autoloaded (WC settings API options are). Check transient names are only primed under `! wp_using_ext_object_cache()`.

**Don't flag when:** Autoloaded options. Loads through order `get_items()`. Tests and fixtures.
