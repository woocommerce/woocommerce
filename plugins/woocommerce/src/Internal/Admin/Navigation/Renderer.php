<?php
/**
 * Renderer for navigation_v2.
 *
 * Two surfaces, one tree:
 *   1. Hover cascade — the WP rail item `woocommerce` has its native L2 flyout
 *      replaced by a multi-level flyout. This is done entirely in CSS (aliased
 *      admin-menu.css) + JS (opensub class toggling).
 *   2. Rail replacement — on Woo pages, the native rail is hidden and a Woo
 *      rail (same 160px) takes its place. This outputs the rail HTML via
 *      admin_footer using WP-native CSS class names so the aliased CSS applies.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs the navigation_v2 rail and body class.
 */
class Renderer {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );
		add_action( 'admin_footer', array( $this, 'render_rail' ) );
	}

	/**
	 * Add .wc-nav-v2-active to body on Woo pages. The CSS keys off this class
	 * to swap the rail.
	 *
	 * @param string $classes Existing classes.
	 * @return string
	 */
	public function add_body_class( string $classes ): string {
		$tree = $this->get_tree();
		if ( null !== $tree && Context::is_woo_page( $tree ) ) {
			$classes .= ' wc-nav-v2-active';
		}
		return $classes;
	}

	/**
	 * Output the Woo rail into the DOM. Always emitted on admin pages — shown
	 * only when the body class is present (CSS-controlled). This avoids a
	 * layout-timing flicker on SPA-style Woo Admin pages.
	 */
	public function render_rail(): void {
		$tree = $this->get_tree();
		if ( null === $tree ) {
			return;
		}

		$current   = Context::resolve_current_slug( $tree );
		$by_parent = $this->index_by_parent( $tree );

		// Spec §6.4: rail replacement uses <nav aria-label="WooCommerce"> with role="tree".
		echo '<nav id="wc-nav-v2" aria-label="' . esc_attr__( 'WooCommerce', 'woocommerce' ) . '">';
		echo '<div id="wc-nav-v2-header">';
		echo '<a href="' . esc_url( admin_url( 'index.php' ) ) . '" id="wc-nav-v2-back">';
		echo is_rtl() ? '&rarr; ' : '&larr; ';
		echo esc_html__( 'WordPress', 'woocommerce' );
		echo '</a>';
		echo '</div>';
		echo '<ul id="wc-nav-v2-adminmenu" role="tree">';

		// Root's children are the top-level rail items.
		$roots = $by_parent['woocommerce'] ?? array();
		usort( $roots, array( $this, 'sort_by_position' ) );
		foreach ( $roots as $node ) {
			$this->render_node( $node, $by_parent, $current );
		}

		echo '</ul>';
		echo '</nav>';
	}

	/**
	 * Render one <li> for a given node, recursing into children.
	 *
	 * @param array       $node      Node with 'slug' added.
	 * @param array       $by_parent Children indexed by parent slug.
	 * @param string|null $current   Current slug or null.
	 */
	private function render_node( array $node, array $by_parent, ?string $current ): void {
		$slug       = $node['slug'];
		$children   = $by_parent[ $slug ] ?? array();
		usort( $children, array( $this, 'sort_by_position' ) );
		$is_current = ( $slug === $current );
		$has_kids   = ! empty( $children );

		$classes = array( 'menu-top' );
		if ( $is_current ) {
			$classes[] = 'current';
			$classes[] = 'wp-has-current-submenu';
			$classes[] = 'wp-menu-open';
		} elseif ( $has_kids ) {
			$classes[] = 'wp-has-submenu';
			$classes[] = 'wp-not-current-submenu';
		}
		if ( ! empty( $node['breadcrumb'] ) ) {
			$classes[] = 'wc-nav-v2-breadcrumb';
		}

		echo '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		echo '<a href="' . esc_url( $this->slug_to_url( $slug ) ) . '" class="menu-top">';
		if ( ! empty( $node['icon'] ) ) {
			echo '<div class="wp-menu-image dashicons-before ' . esc_attr( $node['icon'] ) . '" aria-hidden="true"><br></div>';
		}
		echo '<div class="wp-menu-name">' . esc_html( $node['title'] ) . '</div>';
		echo '</a>';

		if ( $has_kids ) {
			echo '<ul class="wp-submenu wp-submenu-wrap">';
			echo '<li class="wp-submenu-head" aria-hidden="true">' . esc_html( $node['title'] ) . '</li>';
			foreach ( $children as $child ) {
				$child_current = ( $child['slug'] === $current ) ? ' class="current"' : '';
				echo '<li' . $child_current . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<a href="' . esc_url( $this->slug_to_url( $child['slug'] ) ) . '">' . esc_html( $child['title'] ) . '</a>';
				echo '</li>';
			}
			echo '</ul>';
		}

		echo '</li>';
	}

	/**
	 * Group tree entries by parent.
	 *
	 * @param array $tree Tree.
	 * @return array Array of parent-slug => list-of-child-nodes. Each node has
	 *               'slug' key added for convenience.
	 */
	private function index_by_parent( array $tree ): array {
		$by_parent = array();
		foreach ( $tree as $slug => $node ) {
			// Root-level nodes (parent === null) are not rendered as children
			// of anything. The Woo root in particular is never rendered as a
			// rail item itself — its children are the rail's top-level items.
			if ( null === ( $node['parent'] ?? null ) ) {
				continue;
			}
			$node['slug']                   = $slug;
			$by_parent[ $node['parent'] ] ??= array();
			$by_parent[ $node['parent'] ][] = $node;
		}
		return $by_parent;
	}

	/**
	 * Sort callback.
	 *
	 * @param array $a Node.
	 * @param array $b Node.
	 * @return int
	 */
	private function sort_by_position( array $a, array $b ): int {
		return ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 );
	}

	/**
	 * Turn a tree slug back into an admin URL. The slug itself is typically
	 * already a full query-string fragment (`edit.php?post_type=product`,
	 * `wc-admin&path=/analytics/overview`).
	 *
	 * @param string $slug Slug.
	 * @return string
	 */
	private function slug_to_url( string $slug ): string {
		if ( str_contains( $slug, '?' ) || str_contains( $slug, '&' ) ) {
			return admin_url( str_contains( $slug, '?' ) ? $slug : 'admin.php?page=' . $slug );
		}
		return admin_url( 'admin.php?page=' . $slug );
	}

	/**
	 * Fetch the tree from Menu_Reconciler's static store.
	 *
	 * @return array|null
	 */
	private function get_tree(): ?array {
		return Menu_Reconciler::get_tree();
	}
}
