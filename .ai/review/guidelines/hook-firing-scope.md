# Hooks firing more, less, or elsewhere

**Source:**

- #58472 (9.9.1): restored a removed webhook comment-filter callback.
- #49797 (9.1.4): reverted a change that fired `product_add_publish` too often.
- Earlier review findings: `woocommerce_analytics_new_customer` stopped firing after a read-only lookup swap; geo/locale filters fired once per locale; settings filters now fired on the frontend; `save_items()` stopped calling the overridable `delete_items()`, breaking custom data stores; reset bypassed default-status filters.
- Earlier review findings: `clean_post_cache`; `set_object_terms`.

**What broke:** Refactors moved or gated code so a hook or overridable method stopped running, ran many times, or ran in a new context. Analytics, webhooks, and custom data stores depend on these.

**Pattern to catch:** Moving or gating code so a hook or overridable method runs fewer times, more times, or in a new context. Covers:

- adding a loop around a filtered getter;
- moving code out of an `is_admin()` gate;
- replacing a CRUD or data-store method call with direct SQL or a lookup;
- removing `add_action` / `add_filter` lines.

**How to confirm:** Trace each path that reached the hook before and after. For data-store changes, check whether `$this->method()` dispatch was replaced by inline logic. Count calls per request on hot paths that feed Jetpack Sync, webhooks, or analytics.

**Don't flag when:** A hook moved within the same request phase with the same call count. Removing a callback that was registered twice.
