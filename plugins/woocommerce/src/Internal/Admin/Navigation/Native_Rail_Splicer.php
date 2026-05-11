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
		$this->force_current_highlight( $tree );
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
		add_filter(
			'submenu_file',
			static fn( $_ ): string => $current,
			PHP_INT_MAX
		);
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
				$url       = (string) ( $child['url'] ?? $child_slug );
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
			$menu[ $key ] = array(
				$title,
				$cap,
				$slug,
				$title,
				'wc-nav-v2-item',
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
