# SQL query shapes to investigate

**Source:**

- No shipped regression traced. Fixed after the fact: #66603 (N-query product ordering), #68043, #65558 (tax rate queries), #68300 (widgets ordered by lookup table).
- Conditions and examples: [SQL query patterns](../../skills/woocommerce-performance/sql-query-patterns.md).

**What broke:** These changes addressed excess query work in particular WC paths. Their SQL shapes identify candidates for investigation; syntax alone does not establish a regression or an equivalent rewrite.

**Pattern to catch:** New or changed SQL with demonstrated excess work in one of these cases:

- a `wp_posts` join whose existence and eligibility checks are already guaranteed by the final ID set, including after filters;
- `term_relationships` fan-out where related rows only establish membership, rather than contributing required result rows;
- per-item queries that can be batched while preserving groups, overlap, empty results, keys, and types;
- an OR whose measured plan is expensive compared with an equivalent alternative; an existence shortcut may stop only after a positive match, not merely a known customer ID;
- crossed `meta_key IN` and `meta_value IN` lists where the caller actually requires specific pairs;
- posts + postmeta where an available lookup preserves eligibility, identity, freshness, and filter contracts and reduces work;
- a MIN/MAX aggregate whose plan scans many rows and for which equivalent endpoint probes demonstrably help; the engine may already optimize the aggregate.

**How to confirm:** Establish the result contract and inspect the executed plan for the changed path. Compare rows examined and end-to-end cost on representative data. For joins, check the final consumed IDs, not just the originating query. For endpoint probes, preserve all predicates, NULL skipping, and the aggregate's one-row result on empty/all-NULL input. Leading-key ranges do not guarantee ordering by trailing index columns.

**Don't flag when:** The concern is only SQL syntax or missing measurements, pre-existing work the hunk does not touch, removal of a proven anti-pattern, or test SQL. Correctness issues remain in scope on the normal severity scale: removing a load-bearing join or lookup gate, changing MIN/MAX result semantics, or an unguarded `IN ()`.
