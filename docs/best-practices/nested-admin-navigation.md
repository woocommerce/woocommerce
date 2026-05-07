---
post_title: Nested admin navigation (experimental)
menu_title: Nested admin navigation
tags: admin, navigation, experimental
---

# Nested admin navigation

The `navigation_v2` feature flag consolidates all WooCommerce menu items
under a single top-level `WooCommerce` rail item. Enable it from
**WooCommerce → Settings → Advanced → Features**.

## What it does

When enabled, the flag:

- Removes Woo-related top-level items from WP's native rail: Products,
  Analytics, Marketing, Payments (all variants), WooPayments, and Klaviyo
  (when no Marketing parent is present).
- Keeps the `WooCommerce` top-level registration in place; positions it
  directly after the Dashboard item.
- Shows two navigation surfaces:
    - **Hover cascade** — hovering the rail item opens a flyout.
    - **Rail replacement** — on any Woo page, the native rail is replaced
      by a 160px Woo rail with a `← WordPress` link that returns to the
      WP Dashboard.

## Filter hook

Extensions can override placement via the `woocommerce_admin_menu_tree` filter:

```php
add_filter(
    'woocommerce_admin_menu_tree',
    function ( $tree, $raw_menu, $raw_submenu ) {
        \Automattic\WooCommerce\Internal\Admin\Navigation\WC_Admin_Nav::add(
            $tree,
            'my-plugin-reports',
            array(
                'parent' => 'wc-admin&path=/analytics/overview',
                'title'  => 'My Reports',
            )
        );
        return $tree;
    },
    10,
    3
);
```

Four helpers are available on `WC_Admin_Nav`: `add()`, `move()`, `remove()`,
`rename()`. All mutate `$tree` by reference.

## Auto-nesting

Submenu items registered under the `woocommerce` parent via
`add_submenu_page( 'woocommerce', ... )` auto-attach without any filter changes.
This covers the majority of existing extensions without migration.

## Known limitations

- Multisite network admin — flag is ignored; native rail only.
- Plugins registering menu items after priority 999 stay in the native rail.
- Plugins rewriting `parent_file` via the `parent_file` filter (which runs
  after the reconciler) — the tree reflects pre-filter state. Affects a small
  number of known plugins.

## Disabling

Uncheck the flag to restore the native rail. The feature is byte-identical to
uninstalled when disabled — verified by a regression test.
