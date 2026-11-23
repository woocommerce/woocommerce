# Order reads and writes through the posts tables

**Source:**

- #46715: `wc/v3/reports/orders/totals` counted orders with `wp_count_posts( 'shop_order' )` and returned zeros with HPOS on and sync off.
- #65493: legacy admin reports and `wc/v3/reports/sales` built their SQL on `{$wpdb->posts}` and returned empty results on HPOS-authoritative sites with sync off.
- #67015: the #65493 fix translated only some legacy `posts` columns, so a bare `post_status` reached `wc_orders` and reports rendered zeros with no error.

**What broke:** Code outside the order data stores read orders from `wp_posts` / `wp_postmeta`. With HPOS authoritative and compatibility sync off, those tables hold `shop_order_placehold` posts, nothing, or stale copies from before sync was turned off, so counts, lists, and totals came back empty or wrong without an error.

**Pattern to catch:**

- `wp_count_posts()`, `get_posts()`, `WP_Query`, or `get_post()` / `get_post_status()` for `shop_order` or `shop_order_refund`.
- `get_post_meta()` / `update_post_meta()` / `delete_post_meta()` on an order or refund ID.
- SQL that selects orders from `$wpdb->posts` or their meta from `$wpdb->postmeta`.
- An HPOS translation layer that maps only some legacy columns or query args.

**How to confirm:** Is the ID or post type an order or refund? Trace the read with HPOS authoritative and sync off: does it reach `wc_orders`? The safe forms are `wc_get_order()`, `wc_get_orders()`, `$order->get_meta()`, `OrderUtil::get_count_for_type()`, and SQL on `OrderUtil::get_table_for_orders()` that uses that table's own column names. For a translation layer, check that every legacy column the callers use is mapped, qualified and bare.

**Don't flag when:** Posts-table access inside the order data stores, the sync, or the migration, where it is the job; a legacy query-arg mapping there (`OrdersTableQuery`) is still a translation layer. A posts branch that runs only when `OrderUtil::custom_orders_table_usage_is_enabled()` is false, next to an HPOS branch. Non-order post types (products, coupons, pages).
