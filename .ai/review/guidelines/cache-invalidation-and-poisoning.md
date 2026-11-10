# Cache invalidation, vary-by and shape

**Source:**

- #65957: cached poisoned status keys (disabled in #67058).
- #68114: order count cache built before a status was registered.
- #68723: product count stale after a plugin toggle.
- #66900: coupon lookup cache stale when unpublished outside CRUD.
- #67829: term caches stale after reorder.
- #65541 (shipping), #66909, #58535 (9.9.2): missing invalidation.
- #66131 / #67164: corrupt object-cache entries; #67513: inconsistent cache shape.
- #65947: a widget poisoned a shared cache.
- #68426: password-protected reviews cached for the wrong users.
- #67003: request-derived props in cacheable output; #65496: string ID read back from cache.

**What broke:** New caches served stale data because some writer did not invalidate them, leaked data because the key did not vary by user or context, or returned a different type than the fresh path.

**Pattern to catch:** A new or changed `wp_cache_*`, transient, or static cache. Check three things:

- every writer of the source data invalidates it, including non-CRUD writes, plugin toggles, and late registration;
- the key varies by everything the value depends on (user, password, status set, locale);
- the cached readback has the same type and shape as the fresh value.

**How to confirm:** List writers of the underlying data (`wp_update_post`, direct SQL, `register_post_status`). Compare stored and fresh return types. Check whether the key includes the user or context.

**Don't flag when:** Request-local static caches with no cross-request life, where the data cannot change mid-request.
