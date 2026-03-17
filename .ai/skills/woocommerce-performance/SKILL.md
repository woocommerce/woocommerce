---
name: woocommerce-performance
description: Identify missing cache priming calls in WooCommerce PHP code. Use when writing or reviewing code that loads collections of post-based objects (products, orders), renders product lists with images, or reads multiple options in a loop or method.
---

# WooCommerce Performance

## Post Cache Priming — [cache-priming.md](cache-priming.md)

Use when writing or reviewing code that loads collections of post-based objects (products, orders) or renders product lists with images. Covers `_prime_post_caches()` usage patterns — both as a generation guide (apply these patterns when writing new code) and a review guide (flag missing priming in existing code).

## Reducing Excessive Store Reads — [store-reads-reduction.md](store-reads-reduction.md)

Use when writing or reviewing code that calls lazy-loading data store accessors (e.g. `get_items()`, `get_coupon_codes()`) more than once for the same result within a method or template block. Covers identification criteria and the extract-to-local-variable fix.
