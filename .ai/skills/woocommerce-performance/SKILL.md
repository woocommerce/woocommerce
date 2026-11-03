---
name: woocommerce-performance
description: Identify performance issues in WooCommerce PHP code. Use when writing or reviewing code that loads collections of post-based objects (products, orders), renders product lists with images, reads multiple options in a loop or method, or writes SQL queries involving joins, aggregates, meta lookups, or range queries, and changes to indexes, bulk writes, cache invalidation, maintenance jobs or deferred initialization.
---

# WooCommerce Performance

Optimize the work required by the caller while preserving its data and extension contracts. A smaller query count, familiar SQL shape, or merged precedent is not proof of an improvement on another store. Check the current implementation and schema before applying a historical pattern.

## Post Cache Priming — [cache-priming.md](cache-priming.md)

Use when writing or reviewing code that loads collections of post-based objects (products, orders) or renders product lists with images. Covers `_prime_post_caches()` usage patterns — as a generation and review guide. Trace the actual cache layer and loading path before adding or removing a prime; HPOS orders are not post-based objects.

## Options Cache Priming — [options-cache-priming.md](options-cache-priming.md)

Use when writing or reviewing code that reads multiple non-autoloaded options in a loop or method. Covers `wp_prime_option_caches()` usage patterns for static key lists, derivable key patterns, and dynamically extracted key sets (distinguishing existing autoloaded options from missing keys, historic autoload state, and the active transient backend).

## SQL Query Patterns — [sql-query-patterns.md](sql-query-patterns.md)

Use when writing or reviewing any SQL query in WooCommerce PHP code. Covers: redundant versus necessary posts joins, fan-out JOINs, repeated counts, identity unions, metadata key/value pairing, membership subqueries, lookup semantics, MIN/MAX endpoint candidates, narrow fast paths, and ordering/grouping. Establish the result contract before comparing plans: eligible entities, duplicates, NULLs, result types, pagination, storage authority, and extension filters.

## Database Operations and Measurement — [database-operations.md](database-operations.md)

Use for indexes, large writes, cleanup, locks, cache invalidation, profiling, repeated computation or deferred initialization. Covers schema rollout, bounded worker memory/time, cache keys and lifecycle, concurrency, and measurements of the consuming operation. For cache changes, establish the key, lifetime, mutation paths and acceptable staleness.

In the change description, identify the workload improved, evidence supporting that conclusion, compatibility preserved and untested conditions. Use the project's existing testing and verification workflows.
