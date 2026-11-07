# Known-bad SQL shapes

**Source:**

- No shipped regression traced. Fixed after the fact: #66603 (N-query product ordering), #68043, #65558 (tax rate queries), #68300 (widgets ordered by lookup table).
- Rules from `.ai/skills/woocommerce-performance`.

**What broke:** These query shapes were found and fixed in WC after they reached stores. The aim is to stop new ones without flagging rewrites that merely look different.

**Pattern to catch:** New or changed SQL with one of these shapes:

- a `wp_posts` join that enforces nothing the final incoming ID set does not already guarantee;
- `term_relationships` joined without `term_taxonomy_id` when the rows only establish membership;
- per-item queries that could be one GROUP BY with the same keys, zero buckets, and DISTINCT semantics;
- OR across two indexes with a plan that shows excess work;
- `meta_key IN` crossed with `meta_value IN` where the caller needs specific pairs;
- posts + postmeta where `wc_product_meta_lookup` / `wc_product_attributes_lookup` (with correct gates) give the same eligibility, identity, and freshness;
- MIN/MAX whose plan scans many rows and an indexed endpoint seek would not.

**How to confirm:** Reason about the plan for the hunk only. Is the join load-bearing? An upstream `post_status => 'any'`, or IDs that extension filters can widen, means it is. Does the rewrite keep eligible rows, duplicates, NULLs, ordering, and pagination? Syntax alone is not a finding either way: a subquery or lookup read is not proof of improvement, and an OR or MIN/MAX is not proof of a scan.

**Don't flag when:** Pre-existing shapes the hunk does not touch, hunks that remove an anti-pattern, missing benchmarks on their own, and test SQL. Correctness issues in query code are still yours, rated on the normal scale: removing a load-bearing join or lookup gate, nullable MIN/MAX rewrites without `IS NOT NULL`, an unguarded `IN ()`.
