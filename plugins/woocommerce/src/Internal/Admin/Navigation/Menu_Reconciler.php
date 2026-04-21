<?php
/**
 * Menu reconciler.
 *
 * Runs at admin_menu priority 999 (after Woo's own menu registration at 9
 * and WP's default at 10). Captures $menu and $submenu, loads the default
 * tree, applies the woocommerce_admin_menu_tree filter, removes rehomed
 * top-level items from $menu, stores the final tree for the renderer.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Reconciles WP's admin menu against the Woo tree.
 */
class Menu_Reconciler {

	/**
	 * The computed tree. Static so Renderer/Assets can read it without coupling.
	 *
	 * @var array|null
	 */
	private static $tree = null;

	/**
	 * Register hooks.
	 *
	 * We add a menu_order filter at priority 20 (after WC_Admin_Menus's
	 * default-priority filter) to strip phantom slugs from the order.
	 * Reason: WC_Admin_Menus::menu_order() unconditionally pushes
	 * `separator-woocommerce` and `edit.php?post_type=product` into its
	 * output, even if those slugs have been removed from $menu. When they
	 * aren't present, its `array_search` returns false, and
	 * `unset( $menu_order[ false ] )` silently removes index 0 (Dashboard),
	 * which cascades into visible reordering of unrelated items (Posts
	 * shifting down, etc.). Our filter removes slugs from $menu_order that
	 * no longer exist in $menu, leaving native WP ordering intact.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'reconcile' ), 999 );
		add_filter( 'menu_order', array( $this, 'strip_phantom_slugs' ), 20 );
		// Place Woo right after Dashboard. Runs after WC's own menu_order
		// filter (priority 10) and our phantom strip (priority 20).
		add_filter( 'menu_order', array( $this, 'place_woo_after_dashboard' ), 200 );
	}

	/**
	 * Remove slugs from $menu_order that don't correspond to live $menu entries.
	 *
	 * @param array $menu_order Menu order array.
	 * @return array
	 */
	public function strip_phantom_slugs( array $menu_order ): array {
		global $menu;
		if ( ! is_array( $menu ) ) {
			return $menu_order;
		}
		$live_slugs = array_column( $menu, 2 );
		return array_values(
			array_filter(
				$menu_order,
				static fn( $slug ) => in_array( $slug, $live_slugs, true )
			)
		);
	}

	/**
	 * Move the `woocommerce` slug to sit directly after `index.php` (Dashboard)
	 * in the rail. Spec §5.1 / §8.
	 *
	 * @param array $menu_order Menu slugs in current order.
	 * @return array
	 */
	public function place_woo_after_dashboard( array $menu_order ): array {
		// Remove woocommerce from wherever it currently is.
		$menu_order = array_values(
			array_filter(
				$menu_order,
				static fn( $slug ) => 'woocommerce' !== $slug
			)
		);

		// Re-insert immediately after index.php.
		$new_order = array();
		$inserted  = false;
		foreach ( $menu_order as $slug ) {
			$new_order[] = $slug;
			if ( 'index.php' === $slug && ! $inserted ) {
				$new_order[] = 'woocommerce';
				$inserted    = true;
			}
		}

		// Fallback: if index.php wasn't in the order (shouldn't happen), append.
		if ( ! $inserted ) {
			$new_order[] = 'woocommerce';
		}

		return $new_order;
	}

	/**
	 * Run the reconciliation.
	 */
	public function reconcile(): void {
		global $menu, $submenu;

		$default_tree = require __DIR__ . '/default-tree.php';
		$builder      = new Tree_Builder();
		$tree         = $builder->build( $default_tree, (array) $menu, (array) $submenu );

		/**
		 * Filter the navigation_v2 tree before the renderer consumes it.
		 *
		 * @param array $tree        Flat tree keyed by slug.
		 * @param array $raw_menu    WP's $menu at the time of reconciliation.
		 * @param array $raw_submenu WP's $submenu at the time of reconciliation.
		 */
		$tree = apply_filters( 'woocommerce_admin_menu_tree', $tree, (array) $menu, (array) $submenu );
		$tree = $builder->apply_capability_filter( $tree );

		$this->remove_rehomed_top_level_items();
		$this->replace_woocommerce_submenu( $tree );

		self::$tree = $tree;
	}

	/**
	 * Rebuild `$submenu['woocommerce']` so the native flyout (shown when the
	 * user hovers the WooCommerce rail item) reflects our curated tree
	 * rather than WP's organic registration order.
	 *
	 * Only top-level children of `woocommerce` appear here — second-level
	 * items (e.g. Payments under Settings) are rendered as a nested cascade
	 * by admin-navigation-v2.js after DOM load.
	 *
	 * @param array $tree The final computed tree.
	 */
	private function replace_woocommerce_submenu( array $tree ): void {
		global $submenu;

		// The `woocommerce` top-level must be registered (Woo's own admin_menu at
		// priority 9 does this) before we can replace its submenu.
		if ( ! isset( $submenu['woocommerce'] ) ) {
			return;
		}

		$children = array_filter(
			$tree,
			static fn( $node ) => 'woocommerce' === ( $node['parent'] ?? null ) && empty( $node['hidden'] )
		);
		uasort(
			$children,
			static fn( $a, $b ) => ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 )
		);

		$submenu['woocommerce'] = array();
		foreach ( $children as $slug => $node ) {
			$submenu['woocommerce'][] = array(
				$node['title'],                    // menu title.
				$node['capability'] ?? 'read',     // capability.
				$this->slug_to_menu_url( $slug ),  // slug / URL.
				$node['title'],                    // page title.
			);
		}
	}

	/**
	 * Convert a tree slug into a URL suitable for WP's $submenu[2] field.
	 *
	 * Tree slugs can be plain (`wc-settings`), query fragments
	 * (`edit.php?post_type=product`), or WC-Admin paths
	 * (`wc-admin&path=/analytics/overview`).
	 *
	 * @param string $slug Tree slug.
	 * @return string
	 */
	private function slug_to_menu_url( string $slug ): string {
		if ( str_contains( $slug, '?' ) ) {
			return $slug;
		}
		return 'admin.php?page=' . $slug;
	}

	/**
	 * Remove every rehomed-top-level slug from the $menu global.
	 *
	 * $menu's keys encode menu item positions (e.g. Dashboard = 2, Posts = 5,
	 * Media = 10). We unset() in place and preserve those keys — do NOT call
	 * array_values() here, or WP's positional ordering collapses to insertion
	 * order and unrelated items (Posts, Media, etc.) visibly reshuffle.
	 */
	private function remove_rehomed_top_level_items(): void {
		global $menu;

		foreach ( Rehomed_Slugs::ALL as $slug ) {
			foreach ( $menu as $key => $entry ) {
				if ( isset( $entry[2] ) && $entry[2] === $slug ) {
					unset( $menu[ $key ] );
				}
			}
		}

		// Also strip Woo's menu separator.
		foreach ( $menu as $key => $entry ) {
			if ( isset( $entry[2] ) && 'separator-woocommerce' === $entry[2] ) {
				unset( $menu[ $key ] );
			}
		}
	}

	/**
	 * Expose the computed tree for the renderer. Static because the tree is
	 * stored in a static property — callers (Renderer, Assets) must not
	 * instantiate Menu_Reconciler just to read it, or they'd double-register
	 * the admin_menu hook.
	 *
	 * @return array|null Tree, or null if reconcile() hasn't run.
	 */
	public static function get_tree(): ?array {
		return self::$tree;
	}
}
