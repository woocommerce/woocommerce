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
