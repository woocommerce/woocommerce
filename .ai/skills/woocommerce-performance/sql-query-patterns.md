# SQL Query Patterns: Performance Anti-patterns and Fixes

**When to use:** Writing or reviewing any SQL query in WooCommerce PHP code — joins, aggregates, meta lookups, or range queries. Apply proactively when generating new code, or flag violations when reviewing.

---

## Cross-cutting: safe `IN (...)` list construction

All patterns that build a dynamic `IN (...)` list share two requirements:

**1. Guard against empty input** — `WHERE col IN ()` is a MySQL syntax error. Always check before building the query:

```php
if ( empty( $ids ) ) {
    return array(); // never reach the SQL
}
$placeholders = implode( ',', array_map( 'absint', $ids ) );
```

**2. Sanitize values** — Use `array_map('absint', $ids)` for integer ID lists. For string values, use `$wpdb->prepare()` with `%s` placeholders or `esc_sql()`. Never interpolate unsanitized input.

```php
// Integer IDs (products, terms, orders):
$in_sql = implode( ',', array_map( 'absint', $ids ) );
$sql    = "WHERE product_id IN ($in_sql)";

// String values (slugs, statuses):
$placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
$sql = $wpdb->prepare( "WHERE stock_status IN ($placeholders)", ...$values );
```

---

## Pattern A — Redundant posts join (flag this)

**Only join `wp_posts` when you need a column from it.**

When a query receives `$product_ids` from an upstream `WP_Query` that explicitly enforced both `post_type='product'` **and** `post_status='publish'`, the `wp_posts` join adds no new filtering. A `WP_Query` result qualifies; a raw ID list from user input or a query that only enforced one of the two constraints does not.

| You need from `wp_posts`                                                   | Join required?                               |
|----------------------------------------------------------------------------|----------------------------------------------|
| `post_type`, `post_status`, `post_author`                                  | Yes                                          |
| `post_date`, `post_content`, `post_title`                                  | Yes                                          |
| Only `ID` — upstream enforced `post_type` **and** `post_status`            | **No — drop it**                             |
| Only `ID` — upstream enforced one or neither constraint                    | Yes (to enforce the missing constraint)      |
| Only `ID` — IDs from user input or external source                         | Yes (must enforce `post_type`/`post_status`) |

```sql
-- Redundant: posts joined only to use posts.ID
SELECT terms.term_id, COUNT(DISTINCT posts.ID) AS term_count
FROM wp_posts
INNER JOIN wp_term_relationships ON posts.ID = term_relationships.object_id
INNER JOIN wp_term_taxonomy       USING (term_taxonomy_id)
INNER JOIN wp_terms               USING (term_id)
WHERE posts.post_type   = 'product'
  AND posts.post_status = 'publish'
  AND posts.ID IN (1, 2, 3, ...)      -- already guaranteed by upstream WP_Query
  AND terms.term_id IN (10, 20, 30)
GROUP BY terms.term_id
```

**Fix — drive from the narrow side, drop posts:**

```sql
-- Optimal: starts from term_taxonomy (~174 rows for a taxonomy)
SELECT terms.term_id, COUNT(DISTINCT term_relationships.object_id) AS term_count
FROM wp_term_relationships
INNER JOIN wp_term_taxonomy USING (term_taxonomy_id)
INNER JOIN wp_terms         USING (term_id)
WHERE term_relationships.object_id IN (1, 2, 3, ...)
  AND terms.term_id IN (10, 20, 30)
GROUP BY terms.term_id
```

**Why it matters:** `wp_posts` is the largest table. Starting from `wp_term_taxonomy` gives the optimizer a narrow driving side (~174 rows for a product taxonomy) versus starting from `wp_posts` or the `(product × term)` cross-product in a lookup table (~25k rows). Eliminating the join also prevents the fan-out problem (Pattern C).

**Canonical example:** `FilterData::get_attribute_counts` in `src/Internal/ProductFilters/FilterData.php`.

---

## Pattern B — Necessary posts join

When there is no upstream product ID pre-filter, `wp_posts` must be joined to enforce `post_type` and `post_status`:

```sql
SELECT terms.term_id, COUNT(DISTINCT posts.ID) AS term_count
FROM wp_posts
INNER JOIN wp_term_relationships ON posts.ID = term_relationships.object_id
INNER JOIN wp_term_taxonomy       USING (term_taxonomy_id)
INNER JOIN wp_terms               USING (term_id)
WHERE posts.post_type   = 'product'
  AND posts.post_status = 'publish'
  AND terms.term_id IN (10, 20, 30)
GROUP BY terms.term_id
```

This is correct — do not remove it.

---

## Pattern C — Fan-out JOIN on `wp_term_relationships` (flag this)

A fan-out occurs when `wp_term_relationships` is joined on `object_id` alone without also constraining `term_taxonomy_id`. Every product matched by the outer filter may appear in `wp_term_relationships` multiple times (once per category, tag, attribute, etc.), multiplying intermediate rows by the average number of taxonomy terms per product.

**Example at scale:** 24k products × 8 attribute terms × 7 category entries per product ≈ 1.34M intermediate rows from a query that should touch ~192k rows.

**Detection:** A join on `term_relationships.object_id` where `term_taxonomy_id` is not also constrained in the `JOIN ON` or a tight `WHERE` clause.

**Fix options:**

1. Add `AND term_taxonomy_id = <id>` to the join condition.
2. Replace the fan-out join entirely with a subquery:

```sql
-- Replaces: LEFT JOIN wp_term_relationships ON posts.ID = term_relationships.object_id
-- With:
WHERE posts.ID IN (
    SELECT object_id FROM wp_term_relationships
    WHERE term_taxonomy_id IN (...)
)
```

This is what `TaxQuery` (`src/Internal/ProductAttributesLookup/TaxQuery.php`) does for the `IN` operator — it intercepts `WP_Tax_Query`'s default fan-out `LEFT JOIN` and replaces it with a pre-materialized subquery.

---

## Pattern D — N-query loop (flag this)

Issuing one query per status/term/attribute in a loop is always worse than a single aggregated query. Collapse with `GROUP BY`.

```php
// Before: N queries
foreach ( $statuses as $status ) {
    $counts[ $status ] = $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM ... WHERE stock_status = %s", $status )
    );
}

// After: 1 query
$rows = $wpdb->get_results(
    "SELECT stock_status, COUNT(DISTINCT product_id) AS cnt
     FROM wc_product_meta_lookup
     WHERE product_id IN (...)
     GROUP BY stock_status"
);
$counts = array_fill_keys( $statuses, 0 );
foreach ( $rows as $row ) {
    if ( isset( $counts[ $row->stock_status ] ) ) {
        $counts[ $row->stock_status ] = (int) $row->cnt;
    }
}
```

**Canonical example:** `FilterData::get_stock_status_counts` in `src/Internal/ProductFilters/FilterData.php`.

---

## Pattern E — OR across heterogeneous indexed columns (flag this)

An `OR` condition that spans two different indexed columns forces an `index_merge sort_union` — the optimizer scans each index separately and merges the result sets. This is often worse than a single index range scan.

```sql
-- index_merge sort_union on billing_email + customer_id_status indexes:
WHERE status IN ('wc-processing', 'wc-completed')
  AND ( billing_email IN ('1', 'user@example.com') OR customer_id = 1 )
```

**Fix — check the selective branch first, avoid the OR in the best case:**

When one branch is strictly more selective (e.g. `customer_id` is a resolved integer vs. `billing_email` fallback), evaluate that branch first at the PHP level and only construct the OR fallback when the best-case branch cannot be used:

```sql
-- Best case: customer_id known → single range on customer_id_status (covering index,
-- no row data fetched, ~6ms)
WHERE status IN ('wc-processing', 'wc-completed')
  AND customer_id = 1

-- Parity (customer_id unavailable): same index_merge as before, but now the more
-- selective index (customer_id_status) is listed first, which improves merge order
WHERE status IN ('wc-processing', 'wc-completed')
  AND ( customer_id = 1 OR billing_email IN ('user@example.com') )
```

**Why it matters:** A covering index scan (`Using index`) reads no row data and handles 6–12ms queries. An `index_merge sort_union` touches multiple indexes, merges intermediate sets, and materializes a temporary table.

**Canonical example:** `wc_customer_bought_product()` in `includes/wc-user-functions.php`.

---

## Pattern F — Cross-product `meta_key IN + meta_value IN` (flag this)

Combining `meta_key IN ('_key1', '_key2') AND meta_value IN ('val1', 'val2')` creates a logical cross-product: it matches any combination of the two keys with any of the two values, including semantically invalid ones (e.g. `_billing_email = '1'`). This forces a `range` scan over the entire `meta_key` index rather than a precise lookup.

```sql
-- Bad: range scan on meta_key, 200k rows examined; also semantically incorrect
-- (matches _billing_email = '1' and _customer_user = 'email')
WHERE meta_key IN ( '_billing_email', '_customer_user' )
  AND meta_value IN ( '1', 'user@example.com' )
```

**Fix — separate conditions per key:**

```sql
-- Good: each branch is a ref/const on meta_key; ~104k rows per branch (49% fewer total)
-- Note: when both branches must be evaluated (parity case), the OR still causes a range
-- scan on meta_key. The gain is realized in the best-case branch (only one key needed).
WHERE ( meta_key = '_customer_user' AND meta_value = '1' )
   OR ( meta_key = '_billing_email' AND meta_value = 'user@example.com' )
```

**Bonus — decouple multi-fan joins via subquery:**

When the postmeta result set drives a join into a second fan-out table (e.g. `order_items → order_itemmeta`), a flat JOIN multiplies the fans: `postmeta_rows × itemmeta_rows`. Restructure the postmeta side as a subquery to force semijoin materialization (`Start/End temporary`) and prevent fan multiplication:

```sql
-- Flat join: postmeta fan × itemmeta fan multiply
SELECT DISTINCT im.meta_value
FROM wp_posts AS p
INNER JOIN wp_postmeta                     AS pm ON p.ID = pm.post_id
INNER JOIN wp_woocommerce_order_items      AS i  ON p.ID = i.order_id
INNER JOIN wp_woocommerce_order_itemmeta   AS im ON i.order_item_id = im.order_item_id
WHERE ...

-- Subquery: postmeta side materializes as semijoin; fans are additive, not multiplicative
SELECT DISTINCT itemmeta.meta_value
FROM wp_woocommerce_order_items    AS items
INNER JOIN wp_woocommerce_order_itemmeta AS itemmeta ON items.order_item_id = itemmeta.order_item_id
WHERE items.order_id IN (
    SELECT posts.ID FROM wp_posts AS posts
    INNER JOIN wp_postmeta AS postmeta ON posts.ID = postmeta.post_id
    WHERE posts.post_type   = 'shop_order'
      AND posts.post_status IN ('wc-processing', 'wc-completed')
      AND postmeta.meta_key   = '_customer_user'
      AND postmeta.meta_value = '1'
)
  AND itemmeta.meta_key   IN ('_product_id', '_variation_id')
  AND itemmeta.meta_value != '0'
```

**Canonical example:** Legacy path of `wc_customer_bought_product()` in `includes/wc-user-functions.php` (PR #63995).

---

## Pattern G — Prefer WooCommerce lookup tables over posts+postmeta join

WooCommerce maintains two dedicated lookup tables with pre-aggregated product data and narrow, indexed rows. When the required data exists there, drop the `wp_posts + wp_postmeta` join entirely.

### `wc_product_meta_lookup` — product scalars

Stores one row per product. Available columns and indexes:

```sql
-- Columns (all indexed unless noted):
product_id       BIGINT          PK
sku              VARCHAR(100)    INDEX sku(50)
global_unique_id VARCHAR(100)    (no index)
virtual          TINYINT(1)      INDEX virtual
downloadable     TINYINT(1)      INDEX downloadable
min_price        DECIMAL(19,4)   INDEX min_max_price(min_price, max_price)
max_price        DECIMAL(19,4)   (part of min_max_price)
onsale           TINYINT(1)      INDEX onsale
stock_quantity   DOUBLE          INDEX stock_quantity
stock_status     VARCHAR(100)    INDEX stock_status
rating_count     BIGINT          (no index)
average_rating   DECIMAL(3,2)    (no index)
total_sales      BIGINT          (no index)
tax_status       VARCHAR(100)    (no index)
tax_class        VARCHAR(100)    (no index)
```

**Example — 2-table join replaced by single-table lookup (82% faster):**

```sql
-- Before: posts + postmeta join, ~55ms avg (1,002 rows scanned, rowid filter)
SELECT wp_posts.ID
FROM wp_posts
INNER JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id
WHERE wp_posts.post_type   = 'product'
  AND wp_posts.post_status = 'publish'
  AND wp_postmeta.meta_key   = '_downloadable'
  AND wp_postmeta.meta_value = 'yes'
LIMIT 1

-- After: single index seek on lookup table, ~10ms avg
SELECT product_id FROM wp_wc_product_meta_lookup WHERE downloadable = 1 LIMIT 1
```

**Canonical example:** `DownloadsWrapper` in `src/Blocks/BlockTypes/OrderConfirmation/DownloadsWrapper.php`; `FilterData::get_stock_status_counts` in `src/Internal/ProductFilters/FilterData.php`.

### `wc_product_attributes_lookup` — product-term relationships

Stores one row per `(product, term)` pair with `in_stock` and variation flags. Use for attribute-based filtering and counting instead of joining `wp_term_relationships`.

**Canonical example:** `Filterer` in `src/Internal/ProductAttributesLookup/Filterer.php`.

### Regeneration guards

The two tables use different options with opposite semantics — gate each one separately:

```php
// wc_product_meta_lookup: option is truthy while the table is being regenerated.
if ( get_option( 'woocommerce_product_lookup_table_is_generating' ) ) {
    // fall back to wp_postmeta
} else {
    // use wc_product_meta_lookup
}

// wc_product_attributes_lookup: must be explicitly enabled by the merchant.
if ( 'yes' === get_option( 'woocommerce_attribute_lookup_enabled' ) ) {
    // use wc_product_attributes_lookup
} else {
    // fall back to wp_term_relationships
}
```

**Exception:** Code that builds or regenerates the lookup tables themselves (`LookupDataStore`, regeneration scripts) must read from the source tables (`wp_postmeta`, `wp_term_relationships`). This is not a Pattern G violation.

---

## Pattern H — `MIN/MAX` full scan → `UNION ALL + LIMIT 1`

`SELECT MIN(col), MAX(col) FROM table WHERE ...` requires a full index scan of all matching rows. When the column is covered by a sorted composite index, the min and max can each be found in a single seek by reading one row from each end.

**When it applies:** All three conditions must hold:

1. The WHERE predicates on the leading index columns are equality constraints (`col = const`) or bounded ranges that the optimizer can resolve as a contiguous index segment.
2. The ORDER BY column is the trailing key of that same index.
3. `LIMIT 1` stops the scan after the first qualifying row.

The `type_status_date(type, status, date_created_gmt)` index satisfies all three conditions: `type = 'shop_order'` is an equality (const), `status != 'trash'` is a bounded range the optimizer walks within the index, and `date_created_gmt` is the trailing key read directionally. `LIMIT 1` stops after the first row in each direction.

```sql
-- Full scan: 51,831 rows examined, ~66ms avg
SELECT MIN(date_created_gmt), MAX(date_created_gmt)
FROM wp_wc_orders
WHERE type = 'shop_order' AND status != 'trash'

-- Two seeks via UNION ALL: 2 rows total, ~12ms avg (82% faster)
SELECT MIN(date_created_gmt), MAX(date_created_gmt)
FROM (
    ( SELECT date_created_gmt FROM wp_wc_orders
      WHERE type = 'shop_order' AND status != 'trash'
      ORDER BY date_created_gmt DESC LIMIT 1 )
    UNION ALL
    ( SELECT date_created_gmt FROM wp_wc_orders
      WHERE type = 'shop_order' AND status != 'trash'
      ORDER BY date_created_gmt ASC LIMIT 1 )
) d
```

**NULL handling:** `MIN()` / `MAX()` ignore NULLs; `ORDER BY col ASC LIMIT 1` returns NULL first (MySQL sorts NULLs before values in ASC). On a nullable column the two approaches produce different results. Pattern H is only safe when the column is `NOT NULL`, or when both UNION branches add `AND col IS NOT NULL`.

**If the required index does not exist:** Adding it is a prerequisite, not a reason to skip the pattern. Evaluate whether the index maintenance cost is justified (it usually is for high-read, low-write tables like `wp_wc_orders`). For example, `SELECT MAX(post_modified_gmt) FROM wp_posts WHERE post_type IN ('product', 'product_variation')` is a Pattern H candidate, but `post_modified_gmt` has no standard WordPress index — the optimization requires first adding `(post_type, post_modified_gmt)`.

**Table scope:** The pattern applies to any table with a suitable composite index — not only `wp_wc_orders`. When HPOS is disabled, orders reside in `wp_posts` with index `type_status_date(post_type, post_status, post_date)` — a different trailing key than `wp_wc_orders`. Always verify the target table's actual index covers the WHERE + ORDER BY columns before applying the rewrite.

**Canonical example:** `ListTable` in `src/Internal/Admin/Orders/ListTable.php`.

---

## Summary checklist

When writing or reviewing a SQL query:

- [ ] Does the join on `wp_posts` use any column besides `ID`? If not and IDs are pre-filtered, drop the join. (Pattern A)
- [ ] Is `wp_term_relationships` joined without constraining `term_taxonomy_id`? Fan-out risk. (Pattern C)
- [ ] Are there N queries in a loop with a different parameter each time? Collapse with `GROUP BY`. (Pattern D)
- [ ] Is the narrowest table driving the query? Prefer `wp_term_taxonomy` / `wp_terms` (small) over `wp_posts` / `wp_term_relationships` (large). (Pattern A)
- [ ] Does a `WHERE` clause use `OR` across two different indexed columns? Check the more selective branch first at the PHP level. (Pattern E)
- [ ] Does a `WHERE` clause combine `meta_key IN (...) AND meta_value IN (...)`? Split into per-key conditions. (Pattern F)
- [ ] Are two fan-out tables joined flat (multiplying rows)? Decouple one side as a subquery. (Pattern F)
- [ ] Does a `wp_posts + wp_postmeta` join fetch data available in `wc_product_meta_lookup`? Use the lookup table if the regeneration guard passes. (Pattern G)
- [ ] Does any dynamic `IN (...)` list guard against empty input and sanitize values? (Cross-cutting)
- [ ] Does the query compute `MIN(col)` or `MAX(col)` over a large indexed table? Use `UNION ALL + LIMIT 1` if WHERE + ORDER BY share a composite index and the column is `NOT NULL` (or both branches add `AND col IS NOT NULL`). (Pattern H)
- [ ] After applying any optimization, verify the execution plan changed as expected with `EXPLAIN` — check `type`, `key`, `rows`, and `Extra` columns before and after.
