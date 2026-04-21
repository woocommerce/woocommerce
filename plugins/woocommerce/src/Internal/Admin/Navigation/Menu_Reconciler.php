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

		self::$tree = $tree;
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
