<?php

declare( strict_types = 1 );

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
	 * Tree slugs take several forms — a plain page slug (`wc-settings`), a
	 * query-string fragment (`wc-settings&tab=general`, `wc-admin&path=/...`)
	 * or a full URL path+query (`edit-tags.php?taxonomy=product_brand&post_type=product`,
	 * `post-new.php?post_type=product`). For each slug we parse out its
	 * expected `$pagenow` and required query-parameter map, then find the
	 * tree node whose expectations all match the current request. When
	 * multiple slugs match, the most specific (most required params) wins.
	 *
	 * @param array $tree Final tree.
	 * @return string|null
	 */
	public static function resolve_current_slug( array $tree ): ?string {
		global $pagenow;

		$current_pagenow = $pagenow ?: 'admin.php';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current_params = wp_unslash( $_GET );
		// phpcs:enable

		$best       = null;
		$best_specs = -1;

		foreach ( $tree as $slug => $node ) {
			if ( null === ( $node['parent'] ?? null ) ) {
				// Skip the Woo root itself — it isn't a navigable page.
				continue;
			}

			// If the node declares a `url` override, match against that URL
			// (the slug may be a bare handle like `action-scheduler` while
			// the page actually lives at `tools.php?page=action-scheduler`).
			$match_target = $node['url'] ?? $slug;
			list( $path, $expected_params ) = self::decompose_slug( $match_target );
			if ( $current_pagenow !== $path ) {
				continue;
			}

			$matched = true;
			foreach ( $expected_params as $key => $value ) {
				if ( ! isset( $current_params[ $key ] ) || (string) $current_params[ $key ] !== (string) $value ) {
					$matched = false;
					break;
				}
			}
			if ( ! $matched ) {
				continue;
			}

			$specificity = count( $expected_params );
			if ( $specificity > $best_specs ) {
				$best       = (string) $slug;
				$best_specs = $specificity;
			}
		}

		return $best;
	}

	/**
	 * Split a tree slug into ( pagenow, params ).
	 *
	 * - `wc-settings`            → ( 'admin.php', [ 'page' => 'wc-settings' ] )
	 * - `wc-settings&tab=general`→ ( 'admin.php', [ 'page' => 'wc-settings', 'tab' => 'general' ] )
	 * - `edit.php?post_type=X`   → ( 'edit.php',  [ 'post_type' => 'X' ] )
	 *
	 * @param string $slug Tree slug.
	 * @return array{0:string,1:array<string,string>}
	 */
	private static function decompose_slug( string $slug ): array {
		if ( str_contains( $slug, '?' ) ) {
			list( $path, $query ) = explode( '?', $slug, 2 );
		} else {
			$path  = 'admin.php';
			$query = 'page=' . $slug;
		}

		$params = array();
		parse_str( $query, $params );

		// Tree slugs only carry flat scalar params; flatten any nested arrays
		// `parse_str` may produce (`a[b]=c`) to satisfy the documented return type.
		$flat_params = array();
		foreach ( $params as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$flat_params[ (string) $key ] = (string) $value;
			}
		}
		return array( $path, $flat_params );
	}
}
