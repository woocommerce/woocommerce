# Eager work on hot paths

**Source:**

- #69538: mailer built on every `woocommerce_init`.
- #62404 (10.4.1): excessive debug logging on the Payments page.
- #68965 (11.1.2): recursive variation data requests.
- #68016: scheduled-sales cron memory.
- #65922: patterns loaded eagerly.
- #67365: glob of block assets on the frontend, moved to admin-only.
- #65552, #68058: unbounded cache and transient size.
- Earlier review findings: all `WC_Emails` bootstrapped on My Account.

**What broke:** Work that only some requests need moved onto every request or onto product, cart, and checkout renders, and had to be pulled back.

**Pattern to catch:** New work on every request or a hot path (init hooks, product render, cart): singleton bootstraps, filesystem scans (`glob`, `scandir`, `file_exists` in loops), `WP_Query` / `wc_get_products` without limits, logging in loops, unbounded caches.

**How to confirm:** Which hook runs it and how often? Is it gated by context (admin, screen, feature flag)? Is it bounded in size and count?

**Don't flag when:** One-time admin-screen work, or work already behind a lazy getter.
