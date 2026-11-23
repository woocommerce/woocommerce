# Hook argument contracts

**Source:**

- #68707 moved `woocommerce_package_rates` ahead of the free-shipping hide; reverted in #68854.
- #66856: restored the `rel` key in loop add-to-cart args.
- #67679: restored the Taxes report `orders_count` string carried by `woocommerce_admin_report_columns`.
- #66822: restored the breadcrumb callback.
- #58574 (9.9.3): reverted a typed filter callback param.
- #67854, #68798.
- Earlier review findings: `woocommerce_structured_data_context` `@type` became an array; `woocommerce_untrash_order` got `wc-pending` instead of `pending`; ORDER BY string seen by other `posts_clauses` callbacks changed; sanitizer returned null.

**What broke:** The hook kept its name but what it passed changed: type, value set, keys, escaping, SQL text, or when it ran relative to a core step. Extension callbacks comparing against the old value broke silently.

**Pattern to catch:** The hook name stays the same, but an arg's type, value set, keys, escaping, or SQL text changes, or the hook moves relative to a core step. Includes values that are passed through `apply_filters` later.

**How to confirm:** Compare the value at the `apply_filters` / `do_action` call before and after. Grep in-tree callbacks. List what an extension could test against (`'pending' ===`, `is_string`, `strpos` on SQL).

**Don't flag when:** A new arg appended at the end. A value change only on an unreleased path. A stated, changelogged contract change is still a finding, at Medium.
