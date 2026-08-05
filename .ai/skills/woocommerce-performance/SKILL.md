---
name: woocommerce-performance
description: Identify performance issues in WooCommerce PHP code. Use when writing or reviewing code that loads collections of post-based objects (products, orders), renders product lists with images, reads multiple options in a loop or method, or writes SQL queries involving joins, aggregates, meta lookups, or range queries.
---

# WooCommerce Performance

## Post Cache Priming — [cache-priming.md](cache-priming.md)

Use when writing or reviewing code that loads collections of post-based objects (products, orders) or renders product lists with images. Covers `_prime_post_caches()` usage patterns — both as a generation guide (apply these patterns when writing new code) and a review guide (flag missing priming in existing code).

## Options Cache Priming — [options-cache-priming.md](options-cache-priming.md)

Use when writing or reviewing code that reads multiple non-autoloaded options in a loop or method. Covers `wp_prime_option_caches()` usage patterns for static key lists, derivable key patterns, and dynamically extracted key sets (excluding autoloaded options, which are already in cache).

## SQL Query Patterns — [sql-query-patterns.md](sql-query-patterns.md)

Use when writing or reviewing any SQL query in WooCommerce PHP code. Covers: redundant posts joins, fan-out JOIN on `wp_term_relationships`, N-query loops, OR index-merge, cross-product meta conditions, multi-fan join decoupling, WooCommerce lookup table preference (`wc_product_meta_lookup`, `wc_product_attributes_lookup`), and MIN/MAX full-scan elimination.
