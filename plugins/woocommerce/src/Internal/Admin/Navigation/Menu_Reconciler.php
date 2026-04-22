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

		// Attach WC Settings tabs as children of the Settings node before the
		// filter runs so extensions can mutate them too.
		$tree = $this->add_settings_tabs( $tree );

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
	 * Add WC Settings tabs (General / Products / Tax / Shipping / Payments /
	 * Accounts / Emails / Integrations / Advanced, plus conditionally-registered
	 * tabs like Site Visibility) as children of the `wc-settings` node.
	 *
	 * Slug format: `wc-settings&tab=<id>` so the renderer and Context
	 * resolver treat it as a compound wc-admin-style path.
	 *
	 * @param array $tree Tree being built.
	 * @return array Tree with settings tabs attached.
	 */
	private function add_settings_tabs( array $tree ): array {
		if ( ! isset( $tree['wc-settings'] ) ) {
			return $tree;
		}
		if ( ! class_exists( 'WC_Admin_Settings' ) ) {
			return $tree;
		}

		$pos = 30; // After default Payments (10) and WooPayments (20); before Status (99).
		foreach ( \WC_Admin_Settings::get_settings_pages() as $page ) {
			if ( ! is_object( $page ) || ! method_exists( $page, 'get_id' ) || ! method_exists( $page, 'get_label' ) ) {
				continue;
			}
			$id    = $page->get_id();
			$label = Tree_Builder::clean_title( (string) $page->get_label() );
			if ( '' === $id || '' === $label ) {
				continue;
			}
			$slug = 'wc-settings&tab=' . $id;
			if ( isset( $tree[ $slug ] ) ) {
				continue;
			}
			$tree[ $slug ] = array(
				'parent'     => 'wc-settings',
				'title'      => $label,
				'position'   => $pos,
				'source'     => 'settings-tab',
				'capability' => 'manage_woocommerce',
			);
			$pos += 5;
		}

		return $tree;
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

		// Index the original entries by slug so we can preserve them verbatim
		// (with their capability + hookname bindings that WP's access-check
		// logic depends on) when reordering.
		$original_by_slug = array();
		foreach ( $submenu['woocommerce'] as $entry ) {
			if ( isset( $entry[2] ) ) {
				$original_by_slug[ $entry[2] ] = $entry;
			}
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
			// A tree node can override its click-through URL via the `url`
			// field when the slug itself is a placeholder that won't load
			// directly (e.g. `woocommerce-marketing` → `wc-admin&path=/marketing`).
			$effective_slug = $node['url'] ?? $slug;

			if ( isset( $original_by_slug[ $effective_slug ] ) ) {
				$entry    = $original_by_slug[ $effective_slug ];
				$entry[0] = $node['title'];
				$submenu['woocommerce'][] = $entry;
			} elseif ( isset( $original_by_slug[ $slug ] ) && ! isset( $node['url'] ) ) {
				$entry    = $original_by_slug[ $slug ];
				$entry[0] = $node['title'];
				$submenu['woocommerce'][] = $entry;
			} else {
				// Synthesized entry. Use $effective_slug so the rendered link
				// points at a URL that actually loads.
				$submenu['woocommerce'][] = array(
					$node['title'],
					$node['capability'] ?? 'read',
					$effective_slug,
					$node['title'],
				);
			}
		}

		// Preserve access-check registration for any tree descendant (e.g.
		// Status, Settings tabs, Marketing Overview) whose slug WC originally
		// registered under `woocommerce` as a submenu. We don't want those
		// rendered in the top-level flyout, but WP's user_can_access_admin_page
		// iterates $submenu[parent] for the slug match, so dropping them
		// breaks direct page access. Append them with the `hide-if-js` class
		// so WP hides them from the rendered flyout but keeps them on the
		// access-check path.
		$visible_slugs = array();
		foreach ( $submenu['woocommerce'] as $entry ) {
			if ( isset( $entry[2] ) ) {
				$visible_slugs[ $entry[2] ] = true;
			}
		}
		foreach ( $tree as $slug => $node ) {
			if ( 'woocommerce' === $slug ) {
				continue;
			}
			if ( isset( $visible_slugs[ $slug ] ) ) {
				continue;
			}
			if ( ! isset( $original_by_slug[ $slug ] ) ) {
				continue;
			}
			$entry = $original_by_slug[ $slug ];
			// $submenu entries: [title, cap, slug, page_title, classes].
			// WP appends $classes to the rendered <li> when present.
			$existing_classes         = isset( $entry[4] ) ? (string) $entry[4] : '';
			$entry[4]                 = trim( $existing_classes . ' hide-if-js' );
			$submenu['woocommerce'][] = $entry;
		}
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
