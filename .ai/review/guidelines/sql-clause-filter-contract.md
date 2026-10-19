# SQL carried by public filters

**Source:**

- #67871: restored the lookup-table join that third-party `posts_clauses` callbacks referenced.
- #67679: restored the `orders_count` SQL string.
- #68094: SQL errors when extensions join the lookup table.
- #68544: product category queries dropped the caller's term meta filter.
- #63800: restored `LOWER()` for case-sensitive collations.
- Earlier review findings: ORDER BY string changed; `count_notifications()` kept ORDER BY on `COUNT(*)`, returning 0 under ONLY_FULL_GROUP_BY.

**What broke:** Extensions edit SQL fragments through `posts_clauses` and report filters, and reference WC's table aliases. Changing those fragments broke their queries; aggregate mistakes returned wrong counts.

**Pattern to catch:** Changes to SQL passed through public filters (`posts_clauses`, report columns, query vars), to table aliases other callbacks reference, or to aggregates (ORDER BY or GROUP BY with COUNT, collation).

**How to confirm:** Write out the generated SQL for each branch (e.g. each stock status). Grep for alias names other callbacks use. Check the query under `ONLY_FULL_GROUP_BY`.

**Don't flag when:** Internal queries not passed through any filter.
