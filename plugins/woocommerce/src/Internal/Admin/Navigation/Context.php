<?php
/**
 * Woo-page context detection.
 *
 * Answers the question "is the current admin request inside the Woo tree?"
 * which drives rail replacement vs. hover cascade.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the current admin request to a tree slug.
 */
final class Context {

	/**
	 * Is the current request resolvable to any slug in the tree?
	 *
	 * @param array $tree Final tree.
	 * @return bool
	 */
	public static function is_woo_page( array $tree ): bool {
		return null !== self::resolve_current_slug( $tree );
	}

	/**
	 * Return the tree slug that best matches the current request, or null.
	 *
	 * @param array $tree Final tree.
	 * @return string|null
	 */
	public static function resolve_current_slug( array $tree ): ?string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page      = isset( $_GET['page'] )      ? sanitize_text_field( wp_unslash( $_GET['page'] ) )      : '';
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$path      = isset( $_GET['path'] )      ? sanitize_text_field( wp_unslash( $_GET['path'] ) )      : '';
		// phpcs:enable

		if ( '' !== $page ) {
			if ( isset( $tree[ $page ] ) ) {
				return $page;
			}
			if ( 'wc-admin' === $page && '' !== $path ) {
				$candidate = 'wc-admin&path=' . $path;
				if ( isset( $tree[ $candidate ] ) ) {
					return $candidate;
				}
			}
		}

		if ( '' !== $post_type ) {
			$candidate = 'edit.php?post_type=' . $post_type;
			if ( isset( $tree[ $candidate ] ) ) {
				return $candidate;
			}
		}

		return null;
	}
}
