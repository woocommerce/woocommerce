# REST and AJAX permission checks

**Source:**

- #67540 / #67567 (11.0.1): marketplace subscription activate endpoint.
- #68862 (11.1.1): legacy options API, mobile app login.
- #67552 (11.0.1): review-order shortcode authorization.
- #54267 (9.5.2): customers API.
- #63501 (10.5.3): Store API batch routed to non-Store-API endpoints.
- Earlier review findings: byte-exact REST route matching returned 401 on third-party routes.

**What broke:** Routes, AJAX actions, and shortcodes shipped with missing or weak capability checks, or path matching that could be bypassed. Over-strict matching also blocked third-party routes.

**Pattern to catch:** New `register_rest_route` / `wp_ajax_*` / ability without a real `permission_callback` or capability. `__return_true` on mutating routes. Route or path matching that can be bypassed, or is so strict it blocks others.

**How to confirm:** Read the permission callback. Check the capability matches the object (`edit_shop_orders` vs `manage_woocommerce`). Check state-changing AJAX verifies a nonce.

**Don't flag when:** Public read-only Store API routes by design (products; cart for the session owner).
