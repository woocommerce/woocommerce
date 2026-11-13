# SQL prepare and identifiers

**Source:**

- #67544 / #67551 (11.0.1): SQL injection via `report_args.orderby` on CSV export.
- #67546 / #67554: `woocommerce_date_type` injection.
- #69236: Store API slug filter.
- #58857 (9.9.4): admin report conditions.
- #66060: double escaping in prepared queries; #67932.

**What broke:** Request and report args reached SQL identifiers (ORDER BY, date column) without an allowlist. `%i` (WP 6.2+) covers identifier names, but `ASC`/`DESC` and other keywords still need an allowlist. Dynamic `IN (...)` lists also need care.

**Pattern to catch:** Request or option values flowing into SQL identifiers (ORDER BY, column names) without an allowlist. Values without `prepare()`. An `IN` list without `absint` or placeholders, or without an empty-list guard.

**How to confirm:** Trace from `$_GET`, the REST arg, or the option to the query. For identifiers, look for an allowlist or schema check; `esc_sql` is not enough.

**Don't flag when:** Constants, or values checked against a fixed list in the same function.
