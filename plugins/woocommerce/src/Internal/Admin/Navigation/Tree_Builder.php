<?php
/**
 * Pure-logic tree builder. No side effects, no $menu/$submenu mutation.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps -- Underscore class name is the feature convention.

/**
 * Builds the final nav tree from WP's raw $menu/$submenu and the default-tree map.
 */
class Tree_Builder {

	/**
	 * WC-internal submenu slugs that should NOT be auto-attached into the
	 * Woo tree — either legacy redirects or duplicates of items already
	 * declared in the default tree.
	 */
	private const AUTO_ATTACH_EXCLUDE = array(
		// Pre-HPOS Orders — HPOS (`wc-orders`) is the default tree entry.
		'edit.php?post_type=shop_order',
		// Legacy redirect page.
		'coupons-moved',
		// Legacy reports page (deprecated).
		'wc-reports',
		// Legacy Extensions page — the default tree uses the modern
		// `wc-admin&path=/extensions` Marketplace path instead.
		'wc-addons',
		// "Add Product" is explicitly declared in default-tree.php at
		// position 2 under Products. Keep it excluded here so auto-attach
		// doesn't also hoist it and create a duplicate.
		'post-new.php?post_type=product',
		// Product Import / Export — the All Products list view exposes
		// these actions in its toolbar already, so a duplicate menu entry
		// is noise.
		'product_importer',
		'product_exporter',
	);

	/**
	 * Build the tree.
	 *
	 * @param array $default_tree Default tree as loaded from default-tree.php.
	 * @param array $raw_menu     WP's $menu global.
	 * @param array $raw_submenu  WP's $submenu global.
	 * @return array Final tree, keyed by slug.
	 *
	 * @since 10.9.0
	 */
	public function build( array $default_tree, array $raw_menu, array $raw_submenu ): array {
		$registered_slugs = $this->collect_registered_slugs( $raw_menu, $raw_submenu );
		$tree             = array();

		foreach ( $default_tree as $slug => $node ) {
			// Synthetic nodes with an explicit `url` override don't need to
			// match a WP-registered slug — the URL itself is the click
			// target, and the slug acts purely as a unique tree key.
			$is_synthetic_url = isset( $node['url'] );
			if ( 'woocommerce' === $slug || isset( $registered_slugs[ $slug ] ) || $is_synthetic_url ) {
				$tree[ $slug ]           = $node;
				$tree[ $slug ]['source'] = 'default';
			}
		}

		$tree = $this->auto_attach_woocommerce_children( $tree, $default_tree, $raw_submenu );
		$tree = $this->attach_rehomed_submenu_children( $tree, $raw_submenu );
		$tree = $this->drop_unknown_parents( $tree );

		return $tree;
	}

	/**
	 * For every non-root node in the tree, hoist any entries registered under
	 * that slug in $raw_submenu as grandchildren.
	 *
	 * This handles the common case where a rehomed top-level item (e.g.
	 * `woocommerce-marketing`, `edit.php?post_type=product`) came with its
	 * own submenu of sub-pages. When the top-level is stripped from $menu,
	 * those submenu entries would otherwise be orphaned; hoisting them into
	 * the tree preserves the original hierarchy under the consolidated
	 * WooCommerce root.
	 *
	 * @param array $tree        Tree being built.
	 * @param array $raw_submenu WP's $submenu.
	 * @return array Tree with rehomed grandchildren attached.
	 */
	private function attach_rehomed_submenu_children( array $tree, array $raw_submenu ): array {
		foreach ( array_keys( $tree ) as $slug ) {
			// 'woocommerce' is handled separately in auto_attach_woocommerce_children.
			if ( 'woocommerce' === $slug ) {
				continue;
			}
			if ( ! isset( $raw_submenu[ $slug ] ) ) {
				continue;
			}

			$auto_pos = 2000;
			foreach ( $raw_submenu[ $slug ] as $entry ) {
				$child_slug = isset( $entry[2] ) ? self::normalize_slug( $entry[2] ) : null;
				if ( null === $child_slug ) {
					continue;
				}
				if ( in_array( $child_slug, self::AUTO_ATTACH_EXCLUDE, true ) ) {
					continue;
				}
				// WP's CPT submenus include the parent slug as the first "self" entry
				// (e.g. 'edit.php?post_type=product' inside $submenu['edit.php?post_type=product']).
				// We can't add it under its own slug (tree-key collision with the
				// rail-root), so hoist it under a synthetic key with the
				// rail-root URL as `url` and a low position so it sorts first.
				// The synthetic key (`<slug>--all`) is never a real URL — it's
				// just a unique tree identifier; populate_root_submenus reads
				// the URL from the `url` override. (Must run BEFORE the
				// "already in tree" check below — the rail-root itself
				// shares this slug, so that check would always short-circuit
				// the self-ref entry).
				if ( $child_slug === $slug ) {
					$self_key = $child_slug . '--all';
					if ( ! isset( $tree[ $self_key ] ) ) {
						$tree[ $self_key ] = array(
							'parent'     => $slug,
							'title'      => self::clean_title( $entry[0] ?? $child_slug ),
							'position'   => 1,
							'source'     => 'rehomed-self',
							'capability' => $entry[1] ?? 'read',
							'url'        => $child_slug,
						);
					}
					continue;
				}
				if ( isset( $tree[ $child_slug ] ) ) {
					continue;
				}

				$tree[ $child_slug ] = array(
					'parent'     => $slug,
					'title'      => self::clean_title( $entry[0] ?? $child_slug ),
					'position'   => $auto_pos,
					'source'     => 'rehomed',
					'capability' => $entry[1] ?? 'read',
				);
				$auto_pos           += 10;
			}
		}
		return $tree;
	}

	/**
	 * Normalize a menu slug: decode HTML entities (so `&amp;` becomes `&`)
	 * and trim whitespace. WP internals sometimes store submenu slugs with
	 * `&amp;` encoded — we want clean raw slugs in the tree so Context
	 * matching and JS rendering work consistently.
	 *
	 * @param string $slug Raw slug.
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function normalize_slug( string $slug ): string {
		return trim( html_entity_decode( $slug, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * Strip WP's update-count / awaiting-mod badge spans from a menu title
	 * (including their inner numeric content), then strip any remaining HTML.
	 * Leaves just the human-readable label.
	 *
	 * @param string $raw Raw title, possibly containing `<span class="update-plugins count-N">N</span>` etc.
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function clean_title( string $raw ): string {
		$stripped = preg_replace( '/\s*<span class=["\'](?:update-plugins|awaiting-mod|menu-counter)[^"\']*["\'][^>]*>.*?<\/span>\s*/i', '', $raw );
		return trim( html_entity_decode( wp_strip_all_tags( $stripped ?? $raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
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
	 * Slug we nest third-party `add_submenu_page('woocommerce', …)` items
	 * under so the top level of the cascade stays curated.
	 */
	private const AUTO_ATTACH_PARENT = 'wc-admin&path=/extensions';

	/**
	 * Attach any submenu items registered under 'woocommerce' that aren't
	 * already in the tree, preserving registration order.
	 *
	 * Third-party items get nested under Extensions so the top level of the
	 * cascade stays curated. If Extensions isn't in the tree (e.g. a filter
	 * removed it) we fall back to parenting at the Woo root so items aren't
	 * dropped silently.
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

		$auto_parent = isset( $tree[ self::AUTO_ATTACH_PARENT ] ) ? self::AUTO_ATTACH_PARENT : 'woocommerce';

		// Normalize titles of items already attached to the chosen parent so
		// we can skip auto-attach candidates with the same visible label
		// (prevents "Orders/Orders" / "Extensions/Extensions" from surfacing
		// WC's internal legacy redirects alongside default-tree entries).
		$existing_titles = array();
		foreach ( $tree as $existing_slug => $node ) {
			if ( ( $node['parent'] ?? null ) === $auto_parent ) {
				$key = strtolower( trim( (string) ( $node['title'] ?? '' ) ) );
				if ( '' !== $key ) {
					$existing_titles[ $key ] = true;
				}
			}
		}

		$auto_position = 1000;
		foreach ( $raw_submenu['woocommerce'] as $entry ) {
			$slug = isset( $entry[2] ) ? self::normalize_slug( $entry[2] ) : null;
			if ( null === $slug || isset( $default_tree[ $slug ] ) || isset( $tree[ $slug ] ) ) {
				continue;
			}

			// Known WC-internal legacy / redirect / duplicate slugs — never surface.
			if ( in_array( $slug, self::AUTO_ATTACH_EXCLUDE, true ) ) {
				continue;
			}

			$title     = self::clean_title( $entry[0] ?? $slug );
			$title_key = strtolower( trim( $title ) );
			if ( '' !== $title_key && isset( $existing_titles[ $title_key ] ) ) {
				// Another item with the same title is already attached — skip dupe.
				continue;
			}

			$tree[ $slug ]                 = array(
				'parent'     => $auto_parent,
				'title'      => $title,
				'position'   => $auto_position,
				'source'     => 'auto',
				'capability' => $entry[1] ?? 'read',
			);
			$existing_titles[ $title_key ] = true;
			$auto_position                += 10;
		}

		return $tree;
	}

	/**
	 * Apply per-node capability checks. Nodes the user can't access are either
	 * removed, or marked breadcrumb if they have visible descendants (so the
	 * child chain remains reachable via non-clickable labels).
	 *
	 * Called separately from build() because tests can construct the tree
	 * without a user context and then apply the filter under a specific user.
	 *
	 * @param array $tree Tree.
	 * @return array Tree with capability-filtered nodes.
	 *
	 * @since 10.9.0
	 */
	public function apply_capability_filter( array $tree ): array {
		// Pass 1: mark every node's visibility based on capability.
		foreach ( $tree as $slug => &$node ) {
			$cap            = $node['capability'] ?? 'read';
			$node['hidden'] = ! current_user_can( $cap );
		}
		unset( $node );

		// Pass 2: compute visible-descendant flag bottom-up so breadcrumbs know
		// when to stay.
		$has_visible_descendant = array();
		foreach ( array_keys( $tree ) as $slug ) {
			$has_visible_descendant[ $slug ] = false;
		}
		foreach ( $tree as $slug => $node ) {
			if ( ! empty( $node['hidden'] ) ) {
				continue;
			}
			$ancestor = $node['parent'];
			// Guard against a parent cycle (an extension could introduce one via
			// the woocommerce_admin_menu_tree filter); a visited set keeps the
			// walk terminating instead of hanging the request.
			$seen = array();
			while ( null !== $ancestor && isset( $tree[ $ancestor ] ) && ! isset( $seen[ $ancestor ] ) ) {
				$seen[ $ancestor ]                   = true;
				$has_visible_descendant[ $ancestor ] = true;
				$ancestor                            = $tree[ $ancestor ]['parent'];
			}
		}

		// Pass 3: resolve hidden nodes — either breadcrumb or remove.
		foreach ( $tree as $slug => $node ) {
			if ( empty( $node['hidden'] ) ) {
				continue;
			}
			if ( $has_visible_descendant[ $slug ] ) {
				$tree[ $slug ]['breadcrumb'] = true;
				$tree[ $slug ]['hidden']     = false;
			} else {
				unset( $tree[ $slug ] );
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
				$slugs[ self::normalize_slug( (string) $entry[2] ) ] = true;
			}
		}

		foreach ( $raw_submenu as $children ) {
			foreach ( $children as $entry ) {
				if ( isset( $entry[2] ) ) {
					$slugs[ self::normalize_slug( (string) $entry[2] ) ] = true;
				}
			}
		}

		return $slugs;
	}
}
