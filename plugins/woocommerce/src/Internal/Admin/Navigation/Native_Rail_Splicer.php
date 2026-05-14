<?php

declare( strict_types = 1 );

/**
 * Native rail splicer for navigation_v2.
 *
 * On Woo pages, mutates the `$menu` and `$submenu` globals so WordPress's
 * native admin-menu renderer emits the Woo rail. Eliminates the JS-driven
 * `#adminmenu.empty()` + rebuild path; the JS now only injects the
 * third-level cascade.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Splices the tree into `$menu`/`$submenu` for native rail rendering.
 */
class Native_Rail_Splicer {

	/**
	 * Splice the tree into the global $menu/$submenu when on a Woo page.
	 *
	 * No-op when off a Woo page — non-Woo pages keep WP's native rail and
	 * the existing `$submenu['woocommerce']` flyout (built by Menu_Reconciler).
	 *
	 * @param array $tree Final reconciled tree.
	 */
	public function splice( array $tree ): void {
		if ( ! Context::is_woo_page( $tree ) ) {
			return;
		}

		$pre_splice_submenu_woocommerce = $GLOBALS['submenu']['woocommerce'] ?? array();

		$this->relabel_dashboard();
		$this->strip_non_woo_top_level();
		$this->hide_woocommerce_top_level();
		$this->insert_woo_roots( $tree );
		$this->populate_root_submenus( $tree );
		$this->preserve_access_check_entries( $tree, $pre_splice_submenu_woocommerce );
		$this->map_real_parents_for_access_checks( $tree );
		$this->register_rail_root_hooknames( $tree );
		$this->force_current_highlight( $tree );

		// Some WC subsystems (importers, exporters) register
		// `add_submenu_page` entries on `admin_menu` and then unset them
		// from $submenu on `admin_head` so the URL works but the menu item
		// is hidden. That admin_head pass runs after our splicer and wipes
		// the rail entries we just wrote. Re-populate after WP's
		// admin_head action has finished so menu-header.php sees our
		// version — capture by ref since the same instance is reused for
		// the action callback context.
		add_action(
			'admin_head',
			function () use ( $tree ) {
				$this->populate_root_submenus( $tree );
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Remap each spliced bare-slug rail root back to `woocommerce` via
	 * `$_wp_real_parent_file`, so that `user_can_access_admin_page()` resolves
	 * the slug's hookname to the registration WC originally created.
	 *
	 * Background: after `insert_woo_roots()` puts a slug like `wc-admin` into
	 * `$menu` as a top-level entry, WP's `get_admin_page_parent()` second
	 * branch (which iterates `$menu` for `$menu[i][2] === $plugin_page`) finds
	 * it and returns the slug ITSELF as the parent. `get_plugin_page_hookname()`
	 * then computes `admin_page_<slug>` — which isn't registered, because WC
	 * registered the page via `add_submenu_page('woocommerce', …)` (hookname:
	 * `woocommerce_page_<slug>`). The access check returns false → 403.
	 *
	 * `$_wp_real_parent_file` is WP's documented remap that
	 * `get_admin_page_parent()` consults after the lookup: if `[$slug] =>
	 * 'woocommerce'` is set, the function returns `woocommerce` instead of
	 * `$slug`, and the hookname computation lands on `woocommerce_page_<slug>`.
	 *
	 * Skipped for rail roots that already have an `$admin_page_hooks` entry
	 * (post-type pages, plugins that called `add_menu_page` themselves). Those
	 * have their own legitimate hookname registration under their original
	 * prefix (`product_page_<slug>`, `marketing_page_<slug>`, etc.); remapping
	 * would break it.
	 *
	 * @param array $tree Final reconciled tree.
	 */
	private function map_real_parents_for_access_checks( array $tree ): void {
		global $_wp_real_parent_file, $admin_page_hooks;
		if ( ! is_array( $_wp_real_parent_file ) ) {
			$_wp_real_parent_file = array();
		}
		foreach ( $tree as $slug => $node ) {
			if ( ( $node['parent'] ?? null ) !== 'woocommerce' ) {
				continue;
			}
			if ( isset( $admin_page_hooks[ $slug ] ) ) {
				continue;
			}
			if ( ! isset( $_wp_real_parent_file[ $slug ] ) ) {
				$_wp_real_parent_file[ $slug ] = 'woocommerce';
			}
		}
	}

	/**
	 * Ensure WP's menu URL generator can resolve every rail-root to an
	 * `admin.php?page=<slug>` href.
	 *
	 * `menu-header.php` decides whether to emit `admin.php?page=…` or a naked
	 * literal href via `has_action( $hookname )`. For tree slugs that aren't
	 * registered with a page callback (compound wc-admin routes like
	 * `wc-admin&path=/customers`, and any rail root whose owning module
	 * hasn't registered a hookname by the time we splice), the check fails
	 * and the href falls through to the naked branch — producing
	 * `/wp-admin/<slug>` instead of the working `?page=<slug>` form.
	 *
	 * Register a no-op `__return_true` callback under the expected hookname
	 * (the one our `$_wp_real_parent_file` remap routes the rail-root slug
	 * to, i.e. `woocommerce_page_<slug>`) so the check passes. `add_action`
	 * is idempotent — if the real owner already registered a callback, the
	 * no-op piles on harmlessly; if not, ours is the only one and `has_action`
	 * still reports truthy.
	 *
	 * @param array $tree Final reconciled tree.
	 */
	private function register_rail_root_hooknames( array $tree ): void {
		foreach ( $tree as $slug => $node ) {
			if ( ( $node['parent'] ?? null ) !== 'woocommerce' ) {
				continue;
			}
			$hookname = 'woocommerce_page_' . $slug;
			if ( ! has_action( $hookname ) ) {
				add_action( $hookname, static function (): void {} );
			}
		}
	}

	/**
	 * Re-attach as `hide-if-js` any entries that WP originally registered under
	 * `$submenu['woocommerce']` whose tree slug is *not* a direct child of a
	 * rail root. WP needs these entries somewhere in `$submenu` so
	 * `user_can_access_admin_page()` resolves capability for direct page visits.
	 *
	 * @param array $tree                            Final tree.
	 * @param array $pre_splice_submenu_woocommerce  $submenu['woocommerce'] captured before mutations.
	 */
	private function preserve_access_check_entries( array $tree, array $pre_splice_submenu_woocommerce ): void {
		global $submenu;

		// Build a set of slugs already rendered as visible submenu items under
		// the rail roots. Excludes `$submenu['woocommerce']` itself — that
		// belongs to the (now hidden) `woocommerce` top-level entry and is the
		// bucket we're rebuilding for access checks here.
		$rendered = array();
		foreach ( $submenu as $parent => $entries ) {
			if ( 'woocommerce' === $parent ) {
				continue;
			}
			foreach ( $entries as $entry ) {
				if ( isset( $entry[2] ) ) {
					$rendered[ (string) $entry[2] ] = true;
				}
			}
		}

		$preserved = array();
		foreach ( $pre_splice_submenu_woocommerce as $entry ) {
			$slug = $entry[2] ?? null;
			if ( null === $slug || isset( $rendered[ (string) $slug ] ) ) {
				continue;
			}
			// Only preserve slugs that the tree actually knows about — anything
			// else was orphan registration we'd rather not surface.
			if ( ! isset( $tree[ (string) $slug ] ) ) {
				continue;
			}
			$existing_classes = isset( $entry[4] ) ? (string) $entry[4] : '';
			$entry[4]         = trim( $existing_classes . ' hide-if-js' );
			$preserved[]      = $entry;
		}

		// Replace `$submenu['woocommerce']` with the preserved entries so any
		// visible duplicates (rendered under a rail root) don't appear here
		// without `hide-if-js`. Drop the key entirely when nothing needs
		// preserving so the (hidden) top-level entry has no submenu.
		if ( ! empty( $preserved ) ) {
			$submenu['woocommerce'] = $preserved;
		} else {
			unset( $submenu['woocommerce'] );
		}
	}

	/**
	 * Resolve the current tree slug (via Context) and force WP's `parent_file`
	 * and `submenu_file` filters to emit it so the renderer applies `current`
	 * highlighting to the correct rail root and submenu item.
	 *
	 * `parent_file` returns the rail root (the ancestor whose parent is
	 * `woocommerce`). `submenu_file` returns the resolved slug itself when it
	 * is a first-level child; for grandchild pages the JS cascade applies
	 * `current` separately at render time.
	 *
	 * @param array $tree Final reconciled tree.
	 */
	private function force_current_highlight( array $tree ): void {
		$current = Context::resolve_current_slug( $tree );
		if ( null === $current ) {
			return;
		}

		$root = $this->ancestor_root_slug( $tree, $current );
		if ( null === $root ) {
			return;
		}

		add_filter(
			'parent_file',
			static fn( $_ ): string => $root,
			PHP_INT_MAX
		);
		// Must mirror how `populate_root_submenus` writes `$entry[2]`: the
		// url override (if any) then renderable_url(). Otherwise WP's
		// `$submenu_file === $sub_item[2]` highlight check never matches
		// for slugs whose tree node carries a `url` override (e.g.
		// action-scheduler → tools.php?page=action-scheduler).
		$current_url = self::renderable_url( (string) ( $tree[ $current ]['url'] ?? $current ) );
		add_filter(
			'submenu_file',
			static fn( $_ ): string => $current_url,
			PHP_INT_MAX
		);

		$this->mark_root_current( $root );
	}

	/**
	 * Add `wp-has-current-submenu wp-menu-open` to the active rail-root's
	 * $menu entry so its submenu renders inline (in-rail expansion).
	 *
	 * Normally WP adds these classes in `_wp_menu_output()` when
	 * `$parent_file === $item[2]`. We can't rely on that path: for
	 * descendant pages (`wc-status`, `action-scheduler`, `wcdn_page`...)
	 * `get_admin_page_parent()` runs after our `parent_file` filter and
	 * resets `$parent_file` — either via the inline `$_wp_real_parent_file`
	 * remap (`wc-settings` → `woocommerce`) when iterating $submenu, or via
	 * the original Tools-side registration (`tools.php` for action-scheduler).
	 * Neither matches the rail-root's $item[2], so the classes never get
	 * added. Setting them directly via $entry[4] sidesteps the comparison.
	 *
	 * WP will also append `wp-not-current-submenu` (its else branch), so the
	 * LI ends up with both — that's fine: `.wp-has-current-submenu .wp-submenu`
	 * (position: relative, top: auto) wins on cascade order over
	 * `.wp-not-current-submenu .wp-submenu` (which only sets border/width).
	 *
	 * @param string $root Active rail-root slug.
	 */
	private function mark_root_current( string $root ): void {
		global $menu;
		foreach ( $menu as $key => $entry ) {
			if ( ! isset( $entry[2] ) || $entry[2] !== $root ) {
				continue;
			}
			$existing        = isset( $entry[4] ) ? (string) $entry[4] : '';
			$menu[ $key ][4] = trim( $existing . ' wp-has-current-submenu wp-menu-open' );
		}
	}

	/**
	 * Walk the parent chain from `$slug` and return the slug whose parent is
	 * `woocommerce` (i.e. the rail root for that subtree). Returns null if the
	 * slug isn't in the tree or doesn't descend from a Woo root.
	 *
	 * @param array  $tree Tree.
	 * @param string $slug Current slug.
	 */
	private function ancestor_root_slug( array $tree, string $slug ): ?string {
		$walk = $slug;
		while ( isset( $tree[ $walk ] ) ) {
			$parent = $tree[ $walk ]['parent'] ?? null;
			if ( 'woocommerce' === $parent ) {
				return $walk;
			}
			if ( null === $parent ) {
				return null;
			}
			$walk = $parent;
		}
		return null;
	}

	/**
	 * For each Woo tree root, write `$submenu[$root_slug]` with that root's
	 * first-level children (grandchildren stay tree-only — the JS cascade
	 * picks them up at render time).
	 *
	 * Entry shape: `[ title, capability, slug, page_title, classes ]`. We
	 * write `page_title` = title and leave `classes` blank; WP appends to
	 * classes for `current` highlighting at render time.
	 *
	 * @param array $tree Final tree.
	 */
	private function populate_root_submenus( array $tree ): void {
		global $submenu;

		$by_parent = array();
		foreach ( $tree as $slug => $node ) {
			$parent = $node['parent'] ?? null;
			if ( null === $parent ) {
				continue;
			}
			$by_parent[ $parent ][ $slug ] = $node;
		}

		foreach ( $tree as $slug => $node ) {
			if ( ( $node['parent'] ?? null ) !== 'woocommerce' ) {
				continue;
			}
			if ( ! isset( $by_parent[ $slug ] ) ) {
				continue;
			}

			$children = $by_parent[ $slug ];
			uasort(
				$children,
				static fn( $a, $b ) => ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 )
			);

			$entries = array();
			foreach ( $children as $child_slug => $child ) {
				if ( ! empty( $child['hidden'] ) ) {
					continue;
				}
				$title     = (string) ( $child['title'] ?? $child_slug );
				$cap       = (string) ( $child['capability'] ?? 'read' );
				$url       = self::renderable_url( (string) ( $child['url'] ?? $child_slug ) );
				$entries[] = array( $title, $cap, $url, $title, '' );
			}

			if ( ! empty( $entries ) ) {
				$submenu[ $slug ] = $entries;
			}
		}
	}

	/**
	 * Mark the original `woocommerce` $menu entry as `hide-if-js`. We keep
	 * the entry so $submenu['woocommerce'] (which Menu_Reconciler already
	 * rebuilds for the hover cascade and access checks) stays valid; we just
	 * don't want WP to render it as a rail item.
	 */
	private function hide_woocommerce_top_level(): void {
		global $menu;
		foreach ( $menu as $key => $entry ) {
			if ( ! isset( $entry[2] ) || 'woocommerce' !== $entry[2] ) {
				continue;
			}
			$existing        = isset( $entry[4] ) ? (string) $entry[4] : '';
			$menu[ $key ][4] = trim( $existing . ' hide-if-js' );
		}
	}

	/**
	 * Splice each Woo tree root (child of the synthetic `woocommerce` root)
	 * into $menu as its own top-level entry. Numeric keys are derived from
	 * the tree node's `position` so the rail order matches the tree's
	 * declared order. Keys are offset so they sit after `index.php` (key 2)
	 * and never collide with the preserved Dashboard/woocommerce entries.
	 *
	 * Entry tuple shape (WP convention):
	 *   [ menu_title, capability, slug, page_title, css_class, hookname, icon ]
	 *
	 * @param array $tree Final reconciled tree.
	 */
	private function insert_woo_roots( array $tree ): void {
		global $menu;

		$roots = array();
		foreach ( $tree as $slug => $node ) {
			if ( ( $node['parent'] ?? null ) !== 'woocommerce' ) {
				continue;
			}
			if ( ! empty( $node['hidden'] ) ) {
				continue;
			}
			$roots[ $slug ] = $node;
		}

		uasort(
			$roots,
			static fn( $a, $b ) => ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 )
		);

		// Offset = 100 keeps us clear of Dashboard (2) and any preserved
		// non-Woo entries left in the [2..99] band. We add the tree position
		// directly so within the Woo group the visual order matches the tree.
		$base = 100;
		foreach ( $roots as $slug => $node ) {
			$key   = $base + (int) ( $node['position'] ?? 0 );
			$title = (string) ( $node['title'] ?? $slug );
			$cap   = (string) ( $node['capability'] ?? 'read' );
			$icon  = (string) ( $node['icon'] ?? 'dashicons-admin-generic' );

			// Avoid clobbering an existing key (e.g. if `position` collides).
			while ( isset( $menu[ $key ] ) ) {
				$key++;
			}
			// `menu-top` is the class WP keys positioning + hover styles off;
			// `menu-header.php` does NOT add it automatically. Without it the
			// rail LI loses `position: relative`, so the absolutely-positioned
			// `.wp-submenu` flyout anchors to `#adminmenu` instead of its row.
			$menu[ $key ] = array(
				$title,
				$cap,
				$slug,
				$title,
				'menu-top wc-nav-v2-item',
				'toplevel_page_' . self::css_slug( $slug ),
				$icon,
			);
		}
	}

	/**
	 * CSS-safe slug for menu IDs. Mirrors the JS `cssSlug()` so server- and
	 * client-rendered IDs match (the JS cascade reads `#toplevel_page_<slug>`).
	 *
	 * @param string $slug Tree slug.
	 */
	private static function css_slug( string $slug ): string {
		return (string) preg_replace( '/[^A-Za-z0-9_-]/', '-', $slug );
	}

	/**
	 * Convert a tree slug/url to a form WP's `menu-header.php` will render as
	 * a valid `<a href>` value.
	 *
	 * Compound bare slugs like `wc-settings&tab=tax` carry no registered page
	 * hookname (the tab itself isn't `add_submenu_page()`'d), so WP's URL gen
	 * falls through to its naked-fallback branch which echoes `$sub_item[2]`
	 * verbatim. That branch produces a broken relative `<a href='wc-settings&tab=tax'>`.
	 * The menu_hook branch isn't viable either — it pipes the value through
	 * `add_query_arg()`, which URL-encodes the `&` and corrupts the tab arg.
	 *
	 * The fix: prepend `admin.php?page=` so the naked fallback emits a valid
	 * relative admin URL. Bare plain slugs (`wc-admin`) and full URL overrides
	 * (`edit.php?post_type=product`) are left untouched — both already render
	 * correctly through their respective branches.
	 *
	 * @param string $slug_or_url Tree slug or url override.
	 */
	private static function renderable_url( string $slug_or_url ): string {
		if ( false !== strpos( $slug_or_url, '?' ) ) {
			return $slug_or_url;
		}
		if ( false !== strpos( $slug_or_url, '&' ) ) {
			return 'admin.php?page=' . $slug_or_url;
		}
		return $slug_or_url;
	}

	/**
	 * Remove every `$menu` top-level entry that isn't `index.php` (the relabeled
	 * Dashboard back link) or `woocommerce` (WC's own registration, used by the
	 * existing $submenu['woocommerce'] for access checks; the entry itself will
	 * be hidden via `hide-if-js` in Task 5).
	 *
	 * Preserves numeric keys (= WP-native position slots) by using `unset()`
	 * rather than `array_values()`. We are not interleaving with non-Woo items,
	 * so absolute positions don't matter visually — but keeping keys avoids
	 * disturbing any code that reads $menu by position later in the request.
	 */
	private function strip_non_woo_top_level(): void {
		global $menu, $submenu;

		$keep = array( 'index.php', 'woocommerce' );
		foreach ( $menu as $key => $entry ) {
			if ( ! isset( $entry[2] ) ) {
				continue;
			}
			if ( in_array( $entry[2], $keep, true ) ) {
				continue;
			}
			unset( $menu[ $key ] );
		}

		// Also drop separators — WP renders them as visual breaks; we don't
		// want stray separators between Dashboard and the Woo roots.
		foreach ( $menu as $key => $entry ) {
			if ( ! isset( $entry[2] ) ) {
				continue;
			}
			if ( 0 === strpos( (string) $entry[2], 'separator' ) ) {
				unset( $menu[ $key ] );
			}
		}
	}

	/**
	 * Relabel WP's `index.php` entry to act as the rail's back-to-Dashboard
	 * link. Replace its icon with `dashicons-arrow-left-alt` and clear its
	 * submenu (Home / Updates) so it renders as a single flat row.
	 *
	 * No-op if WP's Dashboard entry isn't present (e.g. user lacks `read`).
	 */
	private function relabel_dashboard(): void {
		global $menu, $submenu;

		foreach ( $menu as $key => $entry ) {
			if ( ! isset( $entry[2] ) || 'index.php' !== $entry[2] ) {
				continue;
			}
			$menu[ $key ][0] = __( 'Dashboard', 'woocommerce' );
			$menu[ $key ][3] = __( 'Dashboard', 'woocommerce' );
			$menu[ $key ][6] = 'dashicons-arrow-left-alt';
		}

		if ( isset( $submenu['index.php'] ) ) {
			$submenu['index.php'] = array();
		}
	}
}
