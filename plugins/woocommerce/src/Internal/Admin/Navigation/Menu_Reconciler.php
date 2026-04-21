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
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'reconcile' ), 999 );
		// Spec §5.1 / §8: Woo root sits right after Dashboard (position 2).
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', array( $this, 'place_woo_root' ), 200 );
	}

	/**
	 * Reorder the rail so `woocommerce` sits directly after `index.php`.
	 *
	 * @param array $menu_order Slugs in current order.
	 * @return array
	 */
	public function place_woo_root( array $menu_order ): array {
		$new_order  = array();
		$menu_order = array_values( array_filter( $menu_order, fn( $item ) => 'woocommerce' !== $item ) );

		foreach ( $menu_order as $item ) {
			$new_order[] = $item;
			if ( 'index.php' === $item ) {
				$new_order[] = 'woocommerce';
			}
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

		self::$tree = $tree;
	}

	/**
	 * Remove every rehomed-top-level slug from the $menu global.
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

		$menu = array_values( $menu );
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
