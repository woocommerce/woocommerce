# SQL Query Patterns: Performance Anti-patterns and Fixes

**When to use:** Writing or reviewing any SQL query in WooCommerce PHP code — joins, aggregates, meta lookups, or range queries. Use the patterns to identify candidates, then establish equivalence and measure the actual workload before choosing a rewrite. For indexes, large writes and benchmarking, also read [database operations](database-operations.md).

## Establish the question before changing its SQL

Record the eligible entities and the unit being counted: orders, parent products, variations, terms, or metadata rows. Check publication, visibility, stock, custom statuses/types, NULLs, duplicate metadata, missing rows, ordering, pagination, and result types. HPOS and posts storage can require different plans and predicates.

A count must describe the same population as its corresponding ID selector. Two counts added together may intentionally count an overlapping row twice; one ordered CASE counts it at most once. Moving a branch's condition into a shared WHERE can remove rows another branch previously included. Exercise a truth table of the old and new predicates before pursuing small per-row CPU savings. The [#69360 review](https://github.com/woocommerce/woocommerce/pull/69360#discussion_r4445338840) supplies a counterexample to a proposed CASE simplification; the proposal alone is not an equivalence proof.

SQL equality also depends on collation and coercion. Case-insensitive comparisons can merge distinct custom status strings. A row count equal to the number of children does not prove each child is represented when metadata allows duplicates. For exact agreement, establish identity coverage and comparison semantics; a bounded ID/value read followed by strict PHP comparison may be clearer. The [child-stock review](https://github.com/woocommerce/woocommerce/pull/68546#discussion_r4445639644) illustrates these identity/collation questions; its proposed rewrite is not a general equivalence proof.

Moving grouping/deduplication between SQL and PHP also changes equality and key normalization. [#66853](https://github.com/woocommerce/woocommerce/pull/66853) repaired batched variation-attribute keys using the existing WordPress canonicalization, not the SQL LOWER described in its stale body. Validate filter results before introducing stricter array operations, while preserving the established return contract: [#66810](https://github.com/woocommerce/woocommerce/pull/66810) restored legacy pass-through rather than silently converting malformed values to an empty array.

Preserve supported hooks, data-store overrides, and filterable query paths. A fast path that stops calling an override changes behavior even if signatures remain intact. Retain a compatible fallback when the optimized path cannot preserve those contracts.

Table names and numeric IDs in SQL examples are illustrative; use runtime table names and prepared values in application code.

---

## Cross-cutting: safe `IN (...)` list construction

All patterns that build a dynamic `IN (...)` list share two requirements:

**1. Guard against empty input** — `WHERE col IN ()` is a MySQL syntax error. Check before building the query, preserving the caller's established empty-result behavior. An empty include list must not accidentally become an unrestricted query:

```php
if ( empty( $ids ) ) {
    return array(); // never reach the SQL
}
$ids = array_map( 'absint', $ids );
```

**2. Validate and prepare values** — Normalize validated integer ID lists before interpolation. Casting arbitrary values is not input validation. For string values, use `$wpdb->prepare()` with `%s` placeholders; escaping alone is not a substitute for value placeholders. Never interpolate unsanitized input.

```php
// Integer IDs (products, terms, orders):
$in_sql = implode( ',', array_map( 'absint', $ids ) );
$sql    = "WHERE product_id IN ($in_sql)";

// String values (slugs, statuses):
$placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
$sql = $wpdb->prepare( "WHERE stock_status IN ($placeholders)", ...array_values( $values ) );
```

Prepare only newly introduced fragments when incorporating SQL that has already been prepared. Preparing the whole statement again can reinterpret literal percent syntax; the [#69320 preparation discussion](https://github.com/woocommerce/woocommerce/pull/69320#discussion_r4316217493) includes a literal `50%s` search regression case.

---

## Pattern A — Redundant posts join (flag this)

**Remove the posts join only after proving its filtering and existence checks are redundant.**

An upstream `WP_Query` may already enforce `post_type='product'` and `post_status='publish'`. Before dropping the join, prove the **final** incoming ID set still guarantees every predicate the join enforces, including after filters and cache invalidation. The fact that IDs originated from `WP_Query` is insufficient by itself; a join can enforce existence and eligibility even when SELECT only uses its ID.

| You need from `wp_posts`                                                   | Join required?                               |
|----------------------------------------------------------------------------|----------------------------------------------|
| `post_type`, `post_status`, `post_author`                                  | Yes                                          |
| `post_date`, `post_content`, `post_title`                                  | Yes                                          |
| Only `ID` — final ID set guarantees all join predicates and existence            | Candidate for removal                             |
| Only `ID` — upstream enforced one or neither constraint                    | Yes (to enforce the missing constraint)      |
| Only `ID` — IDs from an unvalidated external source                         | Yes (must enforce `post_type`/`post_status`) |

```sql
-- Candidate: the final IDs already guarantee the same posts eligibility
SELECT terms.term_id, COUNT(DISTINCT posts.ID) AS term_count
FROM wp_posts AS posts
INNER JOIN wp_term_relationships AS term_relationships ON posts.ID = term_relationships.object_id
INNER JOIN wp_term_taxonomy       USING (term_taxonomy_id)
INNER JOIN wp_terms AS terms USING (term_id)
WHERE posts.post_type   = 'product'
  AND posts.post_status = 'publish'
  AND posts.ID IN (1, 2, 3)      -- eligibility must still hold for this final set
  AND terms.term_id IN (10, 20, 30)
GROUP BY terms.term_id
```

**Candidate — drop the proven-redundant join:**

```sql
-- Preserve distinct product identity while removing the redundant relation
SELECT terms.term_id, COUNT(DISTINCT term_relationships.object_id) AS term_count
FROM wp_term_relationships AS term_relationships
INNER JOIN wp_term_taxonomy USING (term_taxonomy_id)
INNER JOIN wp_terms AS terms USING (term_id)
WHERE term_relationships.object_id IN (1, 2, 3)
  AND terms.term_id IN (10, 20, 30)
GROUP BY terms.term_id
```

**Why it matters:** Removing a redundant relation can avoid work. Table size and selectivity vary between stores; neither textual FROM order nor the name of the smallest table dictates the optimizer's driving table. This change does not itself eliminate multiplication from other one-to-many joins; inspect those separately (Pattern C).

**Source example:** `FilterData::get_attribute_counts` in `src/Internal/ProductFilters/FilterData.php`, [#65141](https://github.com/woocommerce/woocommerce/pull/65141). The counts use already-filtered IDs; this does not authorize removing eligibility from an unfiltered lookup query.

---

## Pattern B — Necessary posts join

When the query needs published parent products and no upstream ID set guarantees that population, retain the posts eligibility checks. In this query the join supplies `post_type` and `post_status`:

```sql
SELECT terms.term_id, COUNT(DISTINCT posts.ID) AS term_count
FROM wp_posts AS posts
INNER JOIN wp_term_relationships AS term_relationships ON posts.ID = term_relationships.object_id
INNER JOIN wp_term_taxonomy       USING (term_taxonomy_id)
INNER JOIN wp_terms AS terms USING (term_id)
WHERE posts.post_type   = 'product'
  AND posts.post_status = 'publish'
  AND terms.term_id IN (10, 20, 30)
GROUP BY terms.term_id
```

Do not remove this join merely because no posts column appears in the returned data. Any alternative must preserve the same eligibility and existence checks.

---

## Pattern C — Fan-out JOIN on `wp_term_relationships` (flag this)

A fan-out can occur when `wp_term_relationships` is joined on `object_id` alone without also constraining `term_taxonomy_id`. Every product matched by the outer filter may appear in `wp_term_relationships` multiple times (once per category, tag, attribute, etc.), multiplying intermediate rows by the average number of taxonomy terms per product.

**Illustrative multiplicity:** If separate joins match eight attribute rows and seven category rows per product, 24k products can produce about 1.34M combinations before deduplication. When categories only establish eligibility, the consumer may need only the 192k product/attribute relationships.

**Detection:** A join on `term_relationships.object_id` where `term_taxonomy_id` is not also constrained in the `JOIN ON` or a tight `WHERE` clause.

**Candidate changes:**

1. Constrain `term_taxonomy_id` when that constraint is part of the intended question. Adding it arbitrarily changes the result.
2. If related rows only establish membership, compare an IN/EXISTS form with the current join:

```sql
-- Replaces: LEFT JOIN wp_term_relationships ON posts.ID = term_relationships.object_id
-- With:
WHERE posts.ID IN (
    SELECT object_id FROM wp_term_relationships
    WHERE term_taxonomy_id IN (10, 20, 30)
)
```

`TaxQuery` (`src/Internal/ProductAttributesLookup/TaxQuery.php`) uses membership for the `IN` operator. Preserve taxonomy transformation, child expansion, empty lists and extension hooks when replacing framework SQL. Replacing a LEFT JOIN with membership loses unmatched rows unless existing predicates already require a match; keep the join when related columns or multiplicity are needed.

The engine chooses the semijoin strategy. IN does not force materialization or guarantee linear work. In MySQL EXPLAIN, `Start temporary`/`End temporary` describe Duplicate Weedout; `MATERIALIZED`/`<subqueryN>` indicate materialization. Verify the selected plan using the [MySQL semijoin documentation](https://dev.mysql.com/doc/refman/8.4/en/semijoins-antijoins.html).

[#65119](https://github.com/woocommerce/woocommerce/pull/65119) improved dense matching sets but regressed its sparse 50K-product sample. Test relationship density as well as table size. [#69321](https://github.com/woocommerce/woocommerce/pull/69321#discussion_r4193316278) illustrates comparing membership before forcing join order; neither shape is universally faster.

---

## Pattern D — N-query loop (flag this)

When repeated queries scan substantially the same bounded population, compare GROUP BY or conditional aggregation. Several selective point lookups can be cheaper than an unrestricted aggregate. Keep requested status/term predicates, zero buckets, original result types and COUNT versus COUNT DISTINCT semantics.

The following comparison assumes nonempty, validated product IDs and canonical stock-status values, with the same eligibility and freshness established for both paths. Custom values need separate checks for SQL collation versus PHP array-key equality before grouping:

```php
if ( empty( $statuses ) ) {
    return array();
}

$id_sql = implode( ',', array_map( 'absint', $product_ids ) );
$table  = $wpdb->wc_product_meta_lookup;

// Before: one count per requested status over the same product set.
foreach ( $statuses as $status ) {
    $counts[ $status ] = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT product_id) FROM {$table}
             WHERE product_id IN ({$id_sql}) AND stock_status = %s",
            $status
        )
    );
}

// Candidate: retain both the product and requested-status restrictions.
$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT stock_status, COUNT(DISTINCT product_id) AS cnt
         FROM {$table}
         WHERE product_id IN ({$id_sql}) AND stock_status IN ({$placeholders})
         GROUP BY stock_status",
        ...array_values( $statuses )
    )
);
$counts = array_fill_keys( $statuses, 0 );
foreach ( $rows as $row ) {
    if ( isset( $counts[ $row->stock_status ] ) ) {
        $counts[ $row->stock_status ] = (int) $row->cnt;
    }
}
```

**Source example:** `FilterData::get_stock_status_counts` in `src/Internal/ProductFilters/FilterData.php`. In [#69320](https://github.com/woocommerce/woocommerce/pull/69320), conditional DISTINCT counts preserve duplicate metadata and custom status keys. The [review retained the empty-status guard](https://github.com/woocommerce/woocommerce/pull/69320#discussion_r4337958438). Test empty, missing, duplicate and custom values rather than only ordinary populated fixtures.

---

## Pattern E — OR across heterogeneous indexed columns

An OR across different indexed columns can use index merge or another plan. It does not force `index_merge sort_union`, and reordering its textual branches does not guarantee a better plan.

```sql
-- HPOS purchase eligibility can include both account and email identities.
WHERE status IN ('wc-processing', 'wc-completed')
  AND (customer_id = 12 OR billing_email = 'buyer@example.test')
```

**Check semantics before splitting branches:** A known account ID does not subsume guest purchases by billing email, even when that email matches the account. A collection or count must include the complete qualifying union, with overlap deduplicated where required. Caching the result of only the account branch can preserve an incomplete answer across later calls.

For a boolean existence question, it can be valid to try a selective branch first and return after a **positive** match. A negative result must still check the other promised identities. Knowing the customer ID is not itself a positive result, and this early return does not apply to collecting all purchases.

**Plan comparison:** Compare the complete OR with equivalent alternatives on representative data. A covering index may reduce row fetches, but an observed millisecond result belongs to that schema, cache state and population; it is not a property of the syntax.

**Corrective example:** `wc_customer_bought_product()` in `includes/wc-user-functions.php`. The [guest-order correction #65517](https://github.com/woocommerce/woocommerce/pull/65517) qualifies the earlier optimization in [#63995](https://github.com/woocommerce/woocommerce/pull/63995).

---

## Pattern F — Cross-product `meta_key IN + meta_value IN` (flag this)

Combining `meta_key IN ('_key1', '_key2') AND meta_value IN ('val1', 'val2')` creates a logical cross-product: it matches any combination of the two keys with any of the two values, including semantically invalid ones (e.g. `_billing_email = '1'`). When keys have distinct value domains, the semantic mistake exists regardless of the selected access plan.

```sql
-- Incorrect pairing: matches unintended key/value combinations
-- (matches _billing_email = '1' and _customer_user = 'email')
WHERE meta_key IN ( '_billing_email', '_customer_user' )
  AND meta_value IN ( '1', 'user@example.com' )
```

**Fix — separate conditions per key:**

```sql
-- Correct pairing; inspect the actual plan rather than assuming an access method.
WHERE ( meta_key = '_customer_user' AND meta_value = '1' )
   OR ( meta_key = '_billing_email' AND meta_value = 'user@example.com' )
```

**Bonus — decouple multi-fan joins via subquery:**

When the postmeta result set drives a join into a second fan-out table (e.g. `order_items → order_itemmeta`), a flat JOIN multiplies the fans: `postmeta_rows × itemmeta_rows`. When only membership in the qualifying order set is needed, compare a subquery to avoid multiplying outer results by matching postmeta rows. This gives the optimizer choices; it does not force materialization (see Pattern C):

```sql
-- Flat join: postmeta fan × itemmeta fan multiply
SELECT DISTINCT im.meta_value
FROM wp_posts AS p
INNER JOIN wp_postmeta                     AS pm ON p.ID = pm.post_id
INNER JOIN wp_woocommerce_order_items      AS i  ON p.ID = i.order_id
INNER JOIN wp_woocommerce_order_itemmeta   AS im ON i.order_item_id = im.order_item_id
WHERE p.post_type = 'shop_order'
  AND p.post_status IN ('wc-processing', 'wc-completed')
  AND pm.meta_key = '_customer_user'
  AND pm.meta_value = '1'
  AND im.meta_key IN ('_product_id', '_variation_id')
  AND im.meta_value != '0'

-- Membership candidate: duplicate qualifying postmeta rows need not multiply items
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

**Scope of the example:** Both query shapes must use the same qualifying orders. The displayed subquery is account-ID-only for illustration; an API promising email/guest purchases must preserve that union too (Pattern E). Pairing predicates correctly does not guarantee a faster plan.

**Source example:** Legacy path of `wc_customer_bought_product()` in `includes/wc-user-functions.php`, [#63995](https://github.com/woocommerce/woocommerce/pull/63995), read with its later correction in Pattern E.

---

## Pattern G — Prefer WooCommerce lookup tables over posts+postmeta join

WooCommerce maintains two dedicated lookup tables with pre-aggregated product data and narrow, indexed rows. When the lookup matches the required meaning and freshness, it can replace metadata access. It does not automatically replace posts eligibility or existence checks.

### `wc_product_meta_lookup` — product scalars

Stores one row per product or variation, without post type or publication status. The following is a navigation aid for the current schema; verify `WC_Install` and the installed database before relying on any index, since schema updates can lag code:

```sql
-- Selected schema fields and index definitions; verify against WC_Install:
product_id       BIGINT          PK
sku              VARCHAR(100)    INDEX sku(50)
global_unique_id VARCHAR(100)    INDEX global_unique_id(50)
mpn              VARCHAR(100)    INDEX mpn(50)
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

**Example — replace metadata access while retaining published-parent eligibility:**

```sql
-- Before: published downloadable parent products via postmeta
SELECT wp_posts.ID
FROM wp_posts
INNER JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id
WHERE wp_posts.post_type   = 'product'
  AND wp_posts.post_status = 'publish'
  AND wp_postmeta.meta_key   = '_downloadable'
  AND wp_postmeta.meta_value = 'yes'
LIMIT 1

-- Candidate: lookup replaces postmeta; posts still supplies eligibility.
SELECT lookup_table.product_id
FROM wp_wc_product_meta_lookup AS lookup_table
INNER JOIN wp_posts AS posts ON posts.ID = lookup_table.product_id
WHERE posts.post_type = 'product'
  AND posts.post_status = 'publish'
  AND lookup_table.downloadable = 1
LIMIT 1
```

**Source boundary:** `DownloadsWrapper` in `src/Blocks/BlockTypes/OrderConfirmation/DownloadsWrapper.php` ([#63043](https://github.com/woocommerce/woocommerce/pull/63043)) serves an editor capability heuristic. Its omission of publish/type predicates does not prove equivalence for this query. The posts join can be removed only if the final incoming ID set already guarantees those conditions (Pattern A).

### `wc_product_attributes_lookup` — product-term relationships

Stores product-term relationships with parent/variation identity, stock and taxonomy information. Choose the identity the caller counts and retain DISTINCT/visibility behavior. Compare it with term relationships rather than assuming it is faster: [#65141](https://github.com/woocommerce/woocommerce/pull/65141) retained term relationships for one attribute-count path after comparison.

**Canonical example:** `Filterer` in `src/Internal/ProductAttributesLookup/Filterer.php`.

### Regeneration guards

The two tables use different lifecycle options. Reuse the area's established availability and fallback checks; these options are not interchangeable:

```php
// wc_product_meta_lookup: option is truthy while the table is being regenerated.
if ( get_option( 'woocommerce_product_lookup_table_is_generating' ) ) {
    // fall back to wp_postmeta
} else {
    // use wc_product_meta_lookup
}

// wc_product_attributes_lookup: use only when the enabled option is 'yes'.
if ( 'yes' === get_option( 'woocommerce_attribute_lookup_enabled' ) ) {
    // use wc_product_attributes_lookup
} else {
    // fall back to wp_term_relationships
}
```

An enabled lookup can still be stale during deferred updates or out-of-band imports. Check the caller's acceptable freshness. For fields used by the fast path, compare normal-save and full-regeneration derivations and test their intended agreement. A completed rebuild does not prove equivalent meaning: [#43011](https://github.com/woocommerce/woocommerce/pull/43011) corrected rebuilt sale flags that omitted the regular-price comparison.

**Exception:** Code that builds or regenerates the lookup tables themselves (`LookupDataStore`, regeneration scripts) must read from the source tables (`wp_postmeta`, `wp_term_relationships`). This is not a Pattern G violation.

---

## Pattern H — Compare `MIN/MAX` with `UNION ALL + LIMIT 1`

First inspect the existing plan: the engine may already optimize MIN/MAX through an index. If it scans many rows, test two ordered LIMIT 1 probes while retaining all eligibility predicates and aggregate result shape:

```sql
-- Baseline aggregate; the engine may already optimize it.
SELECT MIN(date_created_gmt), MAX(date_created_gmt)
FROM wp_wc_orders
WHERE type = 'shop_order' AND status != 'trash';

-- Candidate endpoint probes with the same aggregate result shape.
SELECT MIN(date_created_gmt), MAX(date_created_gmt)
FROM (
    (SELECT date_created_gmt FROM wp_wc_orders
     WHERE type = 'shop_order' AND status != 'trash'
       AND date_created_gmt IS NOT NULL
     ORDER BY date_created_gmt ASC LIMIT 1)
    UNION ALL
    (SELECT date_created_gmt FROM wp_wc_orders
     WHERE type = 'shop_order' AND status != 'trash'
       AND date_created_gmt IS NOT NULL
     ORDER BY date_created_gmt DESC LIMIT 1)
) AS bounds;
```

Table names here are illustrative; use the runtime table name. NULL guards preserve MIN/MAX's non-NULL semantics; the outer aggregate preserves one NULL-valued result row on empty/all-NULL input. Backdated orders mean ID order cannot substitute for date order.

A composite `(type,status,date)` index is not globally date-ordered when status varies. In [#63039](https://github.com/woocommerce/woocommerce/pull/63039), the probes selected the separate `date_created` index and applied residual filters. Two returned dates do not mean two examined rows: a long run of ineligible records can make a probe expensive. Do not add an index automatically to enable this rewrite; evaluate its operational cost separately. [MySQL ORDER BY optimization](https://dev.mysql.com/doc/refman/8.4/en/order-by-optimization.html).

**Source example:** `ListTable` in `src/Internal/Admin/Orders/ListTable.php` ([#63039](https://github.com/woocommerce/woocommerce/pull/63039)); verify the current implementation and the NULL/result-shape conditions above.

**Table scope:** HPOS and posts-authoritative storage have different columns and indexes. Verify the target table rather than treating the similarly named `type_status_date` indexes as interchangeable. Adding an index is a separate design decision; account for writes, build cost and migration states using [database operations](database-operations.md).

---

## Pattern I — Keep specialized fast paths narrow and bounded

A per-status UNION can use a `(type,status,date)` index for each fixed-status branch and merge a small ordered page. That does not make it useful for full counts, deep offsets, arbitrary filters or all database setups. In [#65663](https://github.com/woocommerce/woocommerce/pull/65663#pullrequestreview-4478443894), a large Vitess store benefited while ordinary setups were slightly slower. The accepted implementation validates query shape, caps branch/page work, preserves externally changed SQL and retains the original fallback. Its size threshold is a local policy, not a universal crossover point. Include the cost of deciding whether to use the fast path.

Compare the **executed** SQL after framework hooks, not just the generated string. In [#66111](https://github.com/woocommerce/woocommerce/pull/66111#discussion_r3543439014), a request filter accidentally disabled WordPress's ID-only split query and fetched full rows. The fix preserved the framework optimization; the overall PR later closed unmerged. Restrict textual SQL edits to the intended clause so literals and extension-supplied syntax are not rewritten accidentally.

When a framework filter is expensive over the whole store, first derive a smaller valid candidate set, then retain the framework eligibility checks. In [#66786](https://github.com/woocommerce/woocommerce/pull/66786#discussion_r3614242300), the query narrowed role checks to token authors. Eligibility still precedes pagination so pages and totals stay correct. The same query count can do less work; do not bypass role/site checks or assume constant-time access.

---

## Pattern J — Remove sorting or grouping only when it has no semantic effect

Remove ORDER BY when the caller needs only existence or an unordered set and ordering does not determine membership through LIMIT/OFFSET or another consumer. A top-N set still needs its ordering to choose the rows. Remove grouping only when it cannot affect multiplicity or aggregate meaning after all query filters run. Preserve pagination offsets. [#65413](https://github.com/woocommerce/woocommerce/pull/65413) targets a narrow primary-key/no-join case, not arbitrary single-table GROUP BY; [#70121](https://github.com/woocommerce/woocommerce/pull/70121) removes unneeded sorting from IDs used as a set.

---

## Summary checklist

When writing or reviewing a SQL query:

- [ ] Have eligible entities, result types, duplicates, NULLs, ordering and storage authority been preserved?
- [ ] Does the final ID set guarantee every predicate and existence check supplied by a proposed redundant join? (Patterns A/B)
- [ ] Does a one-to-many join multiply rows unnecessarily, and would membership preserve unmatched-row behavior? Check the selected strategy rather than assuming materialization. (Patterns C/F)
- [ ] Can repeated queries share a bounded aggregate while retaining requested keys, zero buckets and DISTINCT semantics? Compare selective point lookups too. (Pattern D)
- [ ] Does an OR represent multiple valid identities? Preserve the complete union; early positive return is only for an existence question. (Pattern E)
- [ ] Are different metadata keys paired with their intended values? (Pattern F)
- [ ] Does a lookup preserve eligibility, identity and freshness, with normal-save and rebuild paths agreeing on the consumed fields? (Pattern G)
- [ ] Are dynamic lists guarded, validated and prepared without re-preparing already prepared fragments?
- [ ] Does the existing MIN/MAX plan already use an index effectively? Do endpoint probes preserve NULL/empty results, and how many rows do they examine? (Pattern H)
- [ ] Are a specialized fast path's query shape, branch/page work and fallback bounded, including after framework hooks? (Pattern I)
- [ ] Does removing sorting or grouping change a top-N set, pagination, duplicates or aggregate meaning? (Pattern J)
- [ ] Have extension hooks and overrides, current schema, execution plans and consuming-operation measurements been checked? Record untested conditions.
