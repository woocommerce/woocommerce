---
post_title: Nested admin navigation (experimental)
menu_title: Nested admin navigation
tags: [ admin, navigation, experimental ]
---

# Nested admin navigation

The `navigation_v2` feature flag consolidates all WooCommerce menu items
under a single top-level `WooCommerce` rail item. Enable it from
**WooCommerce → Settings → Advanced → Features**.

## What it does

When enabled, the flag:

- Builds a curated tree of Woo navigation items from WP's `$menu`/`$submenu`
  globals, the default tree (`default-tree.php`), and the
  `woocommerce_admin_menu_tree` filter.
- On Woo pages (resolved via `Context::is_woo_page()`), splices that tree
  back into `$menu`/`$submenu` so WordPress's native admin-menu renderer
  emits the Woo rail directly — each tree root becomes its own top-level
  entry, first-level children populate its native flyout, and WP's
  `index.php` (Dashboard) entry is relabeled in place as the rail's
  back-to-Dashboard link.
- On non-Woo pages, leaves the native rail intact; hovering the
  `WooCommerce` item reveals the curated tree as a native flyout
  (built into `$submenu['woocommerce']` server-side).
- A JS module (`admin-navigation-v2.js`) handles the pieces WP's native
  admin rail can't: the third-level cascade (flyouts deeper than one
  level — it walks each rail root's submenu after DOM load and attaches
  grandchild items), the slide-out "back to WordPress" overlay panel and
  its color-scheme inheritance, hover-intent timing, and keeping the
  current item highlighted across wc-admin SPA navigation.

## Filter hook

Extensions can override placement via the `woocommerce_admin_menu_tree`
filter. The tree is a flat array keyed by slug — mutate it directly:

```php
add_filter(
    'woocommerce_admin_menu_tree',
    function ( $tree, $raw_menu, $raw_submenu ) {
        // Add an item.
        $tree['my-plugin-reports'] = array(
            'parent'   => 'wc-admin&path=/analytics/overview',
            'title'    => 'My Reports',
            'position' => 50,
        );

        // Move an item: repoint its `parent`.
        if ( isset( $tree['some-slug'] ) ) {
            $tree['some-slug']['parent'] = 'wc-settings';
        }

        // Rename an item: change its `title`.
        if ( isset( $tree['another-slug'] ) ) {
            $tree['another-slug']['title'] = 'New label';
        }

        // Remove an item: unset its key.
        unset( $tree['unwanted-slug'] );

        return $tree;
    },
    10,
    3
);
```

Each node is an array with `parent` (a slug, or `null` for the
WooCommerce root), `title`, and `position`; `capability` and `icon` are
optional. Nodes whose `parent` is not present in the tree are dropped.

## Auto-nesting

Submenu items registered under the `woocommerce` parent via
`add_submenu_page( 'woocommerce', ... )` auto-attach without any filter
changes. This covers the majority of existing extensions without
migration.

## Known limitations

- Multisite network admin — flag is ignored; native rail only.
- Plugins rewriting `parent_file` via the `parent_file` filter (which
  runs after the reconciler) — the tree reflects pre-filter state.
  Affects a small number of known plugins.

## Disabling

Uncheck the flag to restore the native rail. The feature is
byte-identical to uninstalled when disabled — verified by a regression
test.
