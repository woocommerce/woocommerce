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

		$tree = $this->auto_attach_woocommerce_children( $tree, $default_tree, $raw_submenu );
		$tree = $this->break_cycles( $tree );
		$tree = $this->drop_unknown_parents( $tree );

		return $tree;
	}

	/**
	 * Detect cycles in parent chains and break them by demoting the lowest-position
	 * node in each cycle to the Woo root.
	 *
	 * @param array $tree Tree.
	 * @return array Tree with cycles broken.
	 */
	private function break_cycles( array $tree ): array {
		foreach ( array_keys( $tree ) as $slug ) {
			$cycle = $this->find_cycle( $tree, $slug );
			if ( null === $cycle ) {
				continue;
			}

			$demote = $cycle[0];
			foreach ( $cycle as $node ) {
				if ( $tree[ $node ]['position'] < $tree[ $demote ]['position'] ) {
					$demote = $node;
				}
			}

			$tree[ $demote ]['parent'] = 'woocommerce';

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf(
						'[woocommerce] navigation_v2: cycle detected in admin menu tree (%s); demoted %s to root.',
						implode( ' -> ', $cycle ),
						$demote
					)
				);
			}
		}

		return $tree;
	}

	/**
	 * Walk the parent chain starting from $slug. If it revisits any node,
	 * return the cycle (array of slugs). Otherwise return null.
	 *
	 * @param array  $tree Tree.
	 * @param string $slug Start slug.
	 * @return array|null
	 */
	private function find_cycle( array $tree, string $slug ): ?array {
		$visited = array();
		$current = $slug;
		while ( null !== $current ) {
			if ( isset( $visited[ $current ] ) ) {
				return array_keys( array_slice( $visited, array_search( $current, array_keys( $visited ), true ) ) );
			}
			$visited[ $current ] = true;
			$parent              = $tree[ $current ]['parent'] ?? null;
			if ( null === $parent || ! isset( $tree[ $parent ] ) ) {
				return null;
			}
			$current = $parent;
		}
		return null;
	}

	/**
	 * Drop nodes whose parent is set but is not in the tree.
	 * Logs to debug.log when WP_DEBUG is enabled.
	 *
	 * @param array $tree Tree.
	 * @return array Tree with orphans removed.
	 */
	private function drop_unknown_parents( array $tree ): array {
		foreach ( $tree as $slug => $node ) {
			if ( null === $node['parent'] ) {
				continue;
			}
			if ( isset( $tree[ $node['parent'] ] ) ) {
				continue;
			}

			unset( $tree[ $slug ] );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf(
						'[woocommerce] navigation_v2: dropped node %s: unknown parent %s',
						$slug,
						$node['parent']
					)
				);
			}
		}

		return $tree;
	}

	/**
	 * Attach any submenu items registered under 'woocommerce' that aren't
	 * already in the tree as children of the Woo root, preserving registration order.
	 *
	 * @param array $tree         Tree being built.
	 * @param array $default_tree Default tree (used to decide "already present").
	 * @param array $raw_submenu  WP's $submenu.
	 * @return array Tree with auto items appended.
	 */
	private function auto_attach_woocommerce_children( array $tree, array $default_tree, array $raw_submenu ): array {
		if ( ! isset( $raw_submenu['woocommerce'] ) ) {
			return $tree;
		}

		$auto_position = 1000;
		foreach ( $raw_submenu['woocommerce'] as $entry ) {
			$slug = $entry[2] ?? null;
			if ( null === $slug || isset( $default_tree[ $slug ] ) || isset( $tree[ $slug ] ) ) {
				continue;
			}

			$tree[ $slug ] = array(
				'parent'     => 'woocommerce',
				'title'      => $entry[0] ?? $slug,
				'position'   => $auto_position,
				'source'     => 'auto',
				'capability' => $entry[1] ?? 'read',
			);
			$auto_position += 10;
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
