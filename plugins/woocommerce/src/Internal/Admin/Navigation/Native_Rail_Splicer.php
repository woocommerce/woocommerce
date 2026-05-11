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

		$this->relabel_dashboard();
		$this->strip_non_woo_top_level();
		$this->hide_woocommerce_top_level();
		$this->insert_woo_roots( $tree );
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
