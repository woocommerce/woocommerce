# Known-bad SQL shapes

**Source:**

- No shipped regression traced. Fixed after the fact: #66603 (N-query product ordering), #68043, #65558 (tax rate queries), #68300 (widgets ordered by lookup table).
- Rules from `.ai/skills/woocommerce-performance`.

**What broke:** These query shapes were found and fixed in WC after they reached stores. The aim is to stop new ones.

**Pattern to catch:** New or changed SQL with one of these shapes:

- a redundant `wp_posts` join when upstream already enforced type and status;
- `term_relationships` joined without `term_taxonomy_id`;
- per-item queries that could be one GROUP BY;
- OR across two indexes;
- `meta_key IN` crossed with `meta_value IN`;
- posts + postmeta where `wc_product_meta_lookup` / `wc_product_attributes_lookup` (with correct gates) would do;
- MIN/MAX as a full scan.

**How to confirm:** Reason about the plan for the hunk only. Is the join load-bearing? An upstream `post_status => 'any'` means it is.

**Don't flag when:** Pre-existing shapes the hunk does not touch, hunks that remove an anti-pattern, and test SQL. Correctness issues in query code are still yours, rated on the normal scale: removing a load-bearing join or lookup gate, nullable MIN/MAX rewrites, an unguarded `IN ()`.
