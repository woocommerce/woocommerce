<?php
/**
 * Menu reconciler.
 *
 * Runs at admin_menu priority PHP_INT_MAX (after every other admin_menu
 * registration, so late/high-priority plugin menus are captured too).
 * Captures $menu and $submenu, loads the default tree, applies the
 * woocommerce_admin_menu_tree filter, removes rehomed top-level items from
 * $menu, stores the final tree (exposed via get_tree()), and hands it to
 * Native_Rail_Splicer for native rendering on Woo pages.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.Classes.ValidClassName.NotCamelCaps, WooCommerce.Commenting.CommentHooks -- Mutates the WP $menu/$submenu globals by design; underscore class name and hook re-application are intentional.

/**
 * Reconciles WP's admin menu against the Woo tree.
 */
class Menu_Reconciler {

	/**
	 * Top-level slugs that are removed from `$menu` and re-homed inside the
	 * Woo tree when the feature is enabled. Hard-coded — we control which
	 * items get rehomed, not the plugins that register them.
	 *
	 * The `woocommerce` slug itself is intentionally NOT in this list — we
	 * keep Woo's own top-level registration (with its native submenu of
	 * Home/Orders/Products/etc.) as the single consolidated rail item.
	 */
	public const REHOMED_TOP_LEVEL_SLUGS = array(
		'edit.php?post_type=product',
		'wc-admin&path=/analytics/overview',
		'woocommerce-marketing',
		'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
		'wc-admin&path=/payments/connect',
		'wc-admin&path=/payments/overview',
		'woocommerce-payments',
		'klaviyo_settings',
	);

	/**
	 * Splicer that mutates $menu/$submenu for native rail rendering on Woo pages.
	 *
	 * @var Native_Rail_Splicer
	 */
	private Native_Rail_Splicer $splicer;

	/**
	 * The computed tree. Static so Assets can read it without coupling.
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
		// PHP_INT_MAX so we run after every other admin_menu registration —
		// including ones that hook at high priorities (e.g. Klaviyo registers
		// its `woocommerce-marketing` submenu at priority 1000, well after
		// WP's default-priority plugins). If we ran earlier we'd build the
		// tree from a partial $menu/$submenu and miss those slugs entirely.
		add_action( 'admin_menu', array( $this, 'reconcile' ), PHP_INT_MAX );
		add_filter( 'menu_order', array( $this, 'strip_phantom_slugs' ), 20 );
		// Place Woo right after Dashboard. Runs after WC's own menu_order
		// filter (priority 10) and our phantom strip (priority 20).
		add_filter( 'menu_order', array( $this, 'place_woo_after_dashboard' ), 200 );
	}

	/**
	 * Inject dependencies.
	 *
	 * @internal
	 *
	 * @param Native_Rail_Splicer $splicer Splicer that mutates $menu/$submenu for native rail rendering on Woo pages.
	 */
	final public function init( Native_Rail_Splicer $splicer ): void {
		$this->splicer = $splicer;
	}

	/**
	 * Remove slugs from $menu_order that don't correspond to live $menu entries.
	 *
	 * @internal
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
	 * @internal
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
	 *
	 * @internal
	 */
	public function reconcile(): void {
		global $menu, $submenu;

		// Capture the pristine menu before any mutations so the WP-rail overlay
		// panel has access to all registered items (including third-party plugins
		// like Site Kit that live between WooCommerce and Jetpack).
		$this->splicer->set_original_menu( (array) $menu, (array) $submenu );

		// Snapshot top-level icons BEFORE remove_rehomed_top_level_items() wipes
		// their $menu entries — otherwise rehomed plugin icons are unrecoverable.
		$icon_map = $this->capture_top_level_icons( (array) $menu );

		$default_tree = require __DIR__ . '/default-tree.php';
		$builder      = new Tree_Builder();
		$tree         = $builder->build( $default_tree, (array) $menu, (array) $submenu );

		// Attach WC Settings tabs as children of the Settings node before the
		// filter runs so extensions can mutate them too.
		$tree = $this->add_settings_tabs( $tree );
		$tree = $this->apply_title_overrides( $tree );

		/**
		 * Filter the navigation_v2 tree before the renderer consumes it.
		 *
		 * @param array $tree        Flat tree keyed by slug.
		 * @param array $raw_menu    WP's $menu at the time of reconciliation.
		 * @param array $raw_submenu WP's $submenu at the time of reconciliation.
		 *
		 * @since 10.9.0
		 */
		$tree = apply_filters( 'woocommerce_admin_menu_tree', $tree, (array) $menu, (array) $submenu );

		// Carry third-party top-level icons onto their tree nodes. Runs after
		// the filter so plugins that rehome themselves via the filter get their
		// icon attached automatically. Nodes with an explicit icon (default
		// tree, filter override) are left alone.
		$tree = $this->apply_captured_icons( $tree, $icon_map );

		$tree = $builder->apply_capability_filter( $tree );

		$this->remove_rehomed_top_level_items();
		$this->replace_woocommerce_submenu( $tree );
		$this->hide_non_woo_relocated_items();

		self::$tree = $tree;

		// Splice into $menu/$submenu for native rendering on Woo pages.
		$this->splicer->splice( $tree );
	}

	/**
	 * Hide items in other WP admin menus (like Tools) that our tree relocates
	 * into the Woo rail. We keep the original $submenu entries intact so WP's
	 * access check still resolves the slug — just add a `hide-if-js` class
	 * to the entry so the rendered rail doesn't show it twice.
	 *
	 * Tuple format: `[ parent_slug, child_slug ]`.
	 */
	private function hide_non_woo_relocated_items(): void {
		global $submenu;

		$relocations = array(
			array( 'tools.php', 'action-scheduler' ),
		);

		foreach ( $relocations as list( $parent, $child ) ) {
			if ( ! isset( $submenu[ $parent ] ) ) {
				continue;
			}
			foreach ( $submenu[ $parent ] as $key => $entry ) {
				if ( ! isset( $entry[2] ) || $entry[2] !== $child ) {
					continue;
				}
				$existing                      = isset( $entry[4] ) ? (string) $entry[4] : '';
				$submenu[ $parent ][ $key ][4] = trim( $existing . ' hide-if-js' );
			}
		}
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

		// Instantiate the settings pages so each one registers its
		// `woocommerce_settings_tabs_array` callback. Then ask WC which tabs
		// are actually displayable — that's what WC itself uses to decide
		// which tab to render on `?page=wc-settings&tab=X`. Pages can opt
		// out conditionally (e.g. WC_Settings_Tax only registers when
		// `wc_tax_enabled()` is true). Iterating the raw page objects would
		// list Tax in the rail on a tax-disabled store, but clicking it
		// would fall through to General because WC doesn't recognise the
		// tab.
		\WC_Admin_Settings::get_settings_pages();
		$tabs = (array) apply_filters( 'woocommerce_settings_tabs_array', array() );

		$pos = 30;
		// After WooPayments (20); before Status (99). Checkout/Payments tab is placed on the rail directly via default-tree.php and skipped here.
		foreach ( $tabs as $id => $label ) {
			$id    = (string) $id;
			$label = Tree_Builder::clean_title( (string) $label );
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
			$pos          += 5;
		}

		return $tree;
	}

	/**
	 * Rename specific tree nodes to drop noun prefixes that are redundant in
	 * context (e.g. under Products, "Product Import" becomes just "Import").
	 *
	 * Keyed by tree slug so we only touch the intended nodes.
	 *
	 * @param array $tree Tree.
	 * @return array
	 */
	private function apply_title_overrides( array $tree ): array {
		$overrides = array(
			'product_importer' => __( 'Import', 'woocommerce' ),
			'product_exporter' => __( 'Export', 'woocommerce' ),
		);
		foreach ( $overrides as $slug => $title ) {
			if ( isset( $tree[ $slug ] ) ) {
				$tree[ $slug ]['title'] = $title;
			}
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
				$entry                    = $original_by_slug[ $effective_slug ];
				$entry[0]                 = $node['title'];
				$submenu['woocommerce'][] = $entry;
			} elseif ( isset( $original_by_slug[ $slug ] ) && ! isset( $node['url'] ) ) {
				$entry                    = $original_by_slug[ $slug ];
				$entry[0]                 = $node['title'];
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

		// Single pass: drop rehomed top-level slugs and Woo's separator together.
		// unset() preserves the numeric position keys (see the method docblock);
		// do not array_values() here.
		foreach ( $menu as $key => $entry ) {
			if ( ! isset( $entry[2] ) ) {
				continue;
			}
			if ( in_array( $entry[2], self::REHOMED_TOP_LEVEL_SLUGS, true ) || 'separator-woocommerce' === $entry[2] ) {
				unset( $menu[ $key ] );
			}
		}
	}

	/**
	 * Read the icon slot (index 6) from each top-level $menu entry and index
	 * it by slug (index 2). WP stores icons in one of four shapes: a
	 * `dashicons-*` class string, a URL to an image, a `data:image/svg+xml;
	 * base64,...` data URI, or the sentinels `none`/`div` (meaning no icon).
	 * We filter the sentinels out here so the apply step treats "no icon
	 * captured" uniformly.
	 *
	 * @param array $raw_menu WP's $menu global.
	 * @return array<string,string> Map of slug → icon value.
	 */
	private function capture_top_level_icons( array $raw_menu ): array {
		$icons = array();
		foreach ( $raw_menu as $entry ) {
			if ( ! isset( $entry[2], $entry[6] ) ) {
				continue;
			}
			$slug = (string) $entry[2];
			$icon = (string) $entry[6];
			if ( '' === $icon || 'none' === $icon || 'div' === $icon ) {
				continue;
			}
			$icons[ $slug ] = $icon;
		}
		return $icons;
	}

	/**
	 * Fill in `icon` on tree nodes that don't have one, using icons captured
	 * from $menu. An explicit icon (from default-tree.php or from a filter
	 * callback) always wins — we only fill gaps.
	 *
	 * @param array $tree     Tree.
	 * @param array $icon_map Map of slug → icon value.
	 * @return array
	 */
	private function apply_captured_icons( array $tree, array $icon_map ): array {
		foreach ( $tree as $slug => $node ) {
			if ( ! empty( $node['icon'] ) ) {
				continue;
			}
			if ( ! isset( $icon_map[ $slug ] ) ) {
				continue;
			}
			$tree[ $slug ]['icon'] = $icon_map[ $slug ];
		}
		return $tree;
	}

	/**
	 * Expose the computed tree for Assets. Static because the tree is stored
	 * in a static property — callers must not instantiate Menu_Reconciler
	 * just to read it, or they'd double-register the admin_menu hook.
	 *
	 * @return array|null Tree, or null if reconcile() hasn't run.
	 *
	 * @since 10.9.0
	 */
	public static function get_tree(): ?array {
		return self::$tree;
	}
}
