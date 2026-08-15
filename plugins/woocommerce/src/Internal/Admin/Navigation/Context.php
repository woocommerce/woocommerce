<?php
/**
 * Woo-page context detection.
 *
 * Answers the question "is the current admin request inside the Woo tree?"
 * which drives rail replacement vs. hover cascade.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

declare( strict_types = 1 );

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
	 *
	 * @since 10.9.0
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
	 *
	 * @since 10.9.0
	 */
	public static function resolve_current_slug( array $tree ): ?string {
		global $pagenow;

		$current_pagenow = ! empty( $pagenow ) ? $pagenow : 'admin.php';
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
			$match_target                   = $node['url'] ?? $slug;
			list( $path, $expected_params ) = self::decompose_slug( $match_target );

			// Pagenow check: only enforce when the slug declares an explicit
			// `.php` path (`edit.php?…`, `tools.php?…`). Bare slugs and bare
			// compound slugs (`wc-admin&path=/…`) implicitly mean admin.php,
			// but the same logical page can also be reached via its parent's
			// pagenow with `&page=<slug>` — e.g. a submenu of
			// `edit.php?post_type=product` is accessed at that pagenow, not
			// at admin.php. Skip the pagenow check in that case; the param
			// match below still ensures we only accept URLs whose
			// `page=<slug>` agrees.
			$has_explicit_path = ( false !== strpos( $match_target, '?' ) );
			if ( $has_explicit_path && $current_pagenow !== $path ) {
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

			// Score by number of matched expected params, plus a heavy boost
			// when the slug's `page=` value agrees with the request's `page=`.
			// The boost ensures a bare submenu slug (`product_attributes`)
			// wins over its parent rail-root slug (`edit.php?post_type=product`)
			// when both technically match the same URL.
			$specificity = count( $expected_params );
			if (
				isset( $expected_params['page'], $current_params['page'] )
				&& (string) $expected_params['page'] === (string) $current_params['page']
			) {
				$specificity += 100;
			}

			if ( $specificity > $best_specs ) {
				$best       = (string) $slug;
				$best_specs = $specificity;
			}
		}

		// `post.php?post=<id>&action=edit` and `post-new.php?post_type=<type>`
		// don't carry the post type in $pagenow, so the loop above can't match
		// the `edit.php?post_type=<type>` tree slug for the post's CPT. Look
		// up the post type and try that slug as a fallback so the rail stays
		// in the Woo context when editing / creating a product (or any other
		// CPT that maps to a Woo rail root).
		if ( null === $best ) {
			$post_type = null;
			if ( 'post.php' === $current_pagenow && isset( $current_params['post'] ) ) {
				$resolved_type = get_post_type( (int) $current_params['post'] );
				$post_type     = false !== $resolved_type ? $resolved_type : null;
			} elseif ( 'post-new.php' === $current_pagenow ) {
				$post_type = isset( $current_params['post_type'] ) ? (string) $current_params['post_type'] : 'post';
			}
			if ( null !== $post_type ) {
				$candidate = 'edit.php?post_type=' . $post_type;
				if ( isset( $tree[ $candidate ] ) ) {
					return $candidate;
				}
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
		if ( false !== strpos( $slug, '?' ) ) {
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
