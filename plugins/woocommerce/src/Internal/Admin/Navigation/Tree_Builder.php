<?php
/**
 * Pure-logic tree builder. No side effects, no $menu/$submenu mutation.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the final nav tree from WP's raw $menu/$submenu and the default-tree map.
 */
class Tree_Builder {

	/**
	 * Build the tree.
	 *
	 * @param array $default_tree Default tree as loaded from default-tree.php.
	 * @param array $raw_menu     WP's $menu global.
	 * @param array $raw_submenu  WP's $submenu global.
	 * @return array Final tree, keyed by slug.
	 */
	public function build( array $default_tree, array $raw_menu, array $raw_submenu ): array {
		$registered_slugs = $this->collect_registered_slugs( $raw_menu, $raw_submenu );
		$tree             = array();

		foreach ( $default_tree as $slug => $node ) {
			if ( 'woocommerce' === $slug || isset( $registered_slugs[ $slug ] ) ) {
				$tree[ $slug ]           = $node;
				$tree[ $slug ]['source'] = 'default';
			}
		}

		return $tree;
	}

	/**
	 * Collect every slug that WP knows about (top-level + every submenu entry).
	 *
	 * @param array $raw_menu    WP's $menu.
	 * @param array $raw_submenu WP's $submenu.
	 * @return array Associative array, slug => true.
	 */
	private function collect_registered_slugs( array $raw_menu, array $raw_submenu ): array {
		$slugs = array();

		foreach ( $raw_menu as $entry ) {
			if ( isset( $entry[2] ) ) {
				$slugs[ $entry[2] ] = true;
			}
		}

		foreach ( $raw_submenu as $children ) {
			foreach ( $children as $entry ) {
				if ( isset( $entry[2] ) ) {
					$slugs[ $entry[2] ] = true;
				}
			}
		}

		return $slugs;
	}
}
