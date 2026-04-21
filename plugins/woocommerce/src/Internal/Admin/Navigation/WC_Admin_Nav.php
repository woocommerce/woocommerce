<?php
/**
 * Ergonomic helpers for filter callbacks so extension authors don't have to
 * hand-mutate the tree array.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Pure-function helpers operating on the flat tree by reference.
 */
final class WC_Admin_Nav {

	/**
	 * Add (or overwrite) a node.
	 *
	 * @param array  $tree Tree, mutated by reference.
	 * @param string $slug Slug of the node to add.
	 * @param array  $args Node fields. `parent`, `title`, optional `position` (default 10) and `capability`.
	 */
	public static function add( array &$tree, string $slug, array $args ): void {
		$tree[ $slug ] = array(
			'parent'   => $args['parent']   ?? null,
			'title'    => $args['title']    ?? $slug,
			'position' => $args['position'] ?? 10,
			'source'   => 'helper',
		);
		if ( isset( $args['capability'] ) ) {
			$tree[ $slug ]['capability'] = $args['capability'];
		}
	}

	/**
	 * Change a node's parent.
	 *
	 * @param array  $tree   Tree, mutated by reference.
	 * @param string $slug   Slug to move.
	 * @param string $parent New parent slug.
	 */
	public static function move( array &$tree, string $slug, string $parent ): void {
		if ( ! isset( $tree[ $slug ] ) ) {
			return;
		}
		$tree[ $slug ]['parent'] = $parent;
	}

	/**
	 * Remove a node.
	 *
	 * @param array  $tree Tree, mutated by reference.
	 * @param string $slug Slug to remove.
	 */
	public static function remove( array &$tree, string $slug ): void {
		unset( $tree[ $slug ] );
	}

	/**
	 * Rename a node's title.
	 *
	 * @param array  $tree  Tree, mutated by reference.
	 * @param string $slug  Slug to rename.
	 * @param string $title New title.
	 */
	public static function rename( array &$tree, string $slug, string $title ): void {
		if ( ! isset( $tree[ $slug ] ) ) {
			return;
		}
		$tree[ $slug ]['title'] = $title;
	}
}
