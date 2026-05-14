<?php

declare( strict_types = 1 );


namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Menu_Reconciler;
use Automattic\WooCommerce\Internal\Admin\Navigation\Rehomed_Slugs;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Menu_Reconciler
 */
class Menu_Reconciler_Test extends \WC_Unit_Test_Case {

	/**
	 * @var array|null
	 */
	private $menu_backup;
	/**
	 * @var array|null
	 */
	private $submenu_backup;

	/**
	 * @var int
	 */
	private $admin_user_id;

	public function setUp(): void {
		parent::setUp();
		global $menu, $submenu;
		$this->menu_backup    = $menu;
		$this->submenu_backup = $submenu;
		// Create an admin user so capability checks in apply_capability_filter() pass.
		$this->admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );
	}

	public function tearDown(): void {
		global $menu, $submenu;
		$menu    = $this->menu_backup;
		$submenu = $this->submenu_backup;
		wp_set_current_user( 0 );
		wp_delete_user( $this->admin_user_id );
		// Anonymous closures attached to woocommerce_admin_menu_tree by these
		// tests can't be individually unhooked; clear them all so they don't
		// leak into other suites.
		remove_all_filters( 'woocommerce_admin_menu_tree' );
		unset( $_GET['page'], $GLOBALS['pagenow'] );
		remove_all_filters( 'parent_file' );
		remove_all_filters( 'submenu_file' );
		parent::tearDown();
	}

	/**
	 * After reconciliation, every rehomed top-level slug is removed from the
	 * WP $menu global.
	 */
	public function test_rehomed_top_level_items_are_removed() {
		global $menu;
		$menu = array(
			array( 'WooCommerce', 'read', 'woocommerce',                        '', '' ),
			array( 'Products',    'read', 'edit.php?post_type=product',         '', '' ),
			array( 'Marketing',   'read', 'woocommerce-marketing',              '', '' ),
			// Non-Woo item — must NOT be removed.
			array( 'Plugins',     'read', 'plugins.php',                        '', '' ),
		);
		global $submenu;
		$submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$remaining_slugs = array_column( $menu, 2 );

		$this->assertNotContains( 'edit.php?post_type=product', $remaining_slugs );
		$this->assertNotContains( 'woocommerce-marketing', $remaining_slugs );
		// Non-Woo items survive.
		$this->assertContains( 'plugins.php', $remaining_slugs );
	}

	/**
	 * After reconciliation, a single `woocommerce` top-level entry remains
	 * (re-registered), and the computed tree is available via get_tree().
	 */
	public function test_tree_is_stored_after_reconciliation() {
		global $menu, $submenu;
		$menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$tree = Menu_Reconciler::get_tree();
		$this->assertIsArray( $tree );
		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertArrayHasKey( 'wc-admin', $tree );
	}

	/**
	 * When a third-party plugin registers its own top-level menu and a filter
	 * callback rehomes it under `woocommerce`, the reconciler captures the
	 * plugin's top-level icon (from $menu[key][6]) onto the tree node so the
	 * rail renders the native icon rather than falling back to generic.
	 */
	public function test_third_party_top_level_icon_is_carried_onto_tree_node() {
		global $menu, $submenu;
		$menu = array(
			array( 'WooCommerce', 'read', 'woocommerce',  '', '', '', 'dashicons-cart' ),
			array( 'My Plugin',   'read', 'my-plugin',    '', '', '', 'dashicons-cloud' ),
		);
		$submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		// Filter: graft `my-plugin` under woocommerce so it ends up in the tree.
		add_filter(
			'woocommerce_admin_menu_tree',
			function ( $tree ) {
				$tree['my-plugin'] = array(
					'parent'     => 'woocommerce',
					'title'      => 'My Plugin',
					'position'   => 500,
					'capability' => 'read',
				);
				return $tree;
			}
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$tree = Menu_Reconciler::get_tree();
		$this->assertArrayHasKey( 'my-plugin', $tree );
		$this->assertSame( 'dashicons-cloud', $tree['my-plugin']['icon'] );
	}

	/**
	 * An explicit icon (from default-tree.php or set by a filter callback) is
	 * preserved — captured $menu icons only fill gaps, they don't overwrite.
	 */
	public function test_explicit_tree_icon_wins_over_captured_menu_icon() {
		global $menu, $submenu;
		$menu = array(
			array( 'WooCommerce', 'read', 'woocommerce', '', '', '', 'dashicons-cart' ),
			array( 'Products',    'read', 'edit.php?post_type=product', '', '', '', 'dashicons-admin-post' ),
		);
		$submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$tree = Menu_Reconciler::get_tree();
		$this->assertArrayHasKey( 'edit.php?post_type=product', $tree );
		// default-tree.php declares dashicons-products for this slug; the
		// captured $menu icon (dashicons-admin-post) must NOT overwrite it.
		$this->assertSame( 'dashicons-products', $tree['edit.php?post_type=product']['icon'] );
	}

	/**
	 * The `none` and `div` sentinels (WP's way of saying "no icon") are
	 * skipped during capture so tree nodes without an explicit icon fall back
	 * to the JS-side default rather than rendering an empty slot.
	 */
	public function test_none_and_div_icon_sentinels_are_not_applied() {
		global $menu, $submenu;
		$menu = array(
			array( 'WooCommerce', 'read', 'woocommerce',  '', '', '', 'dashicons-cart' ),
			array( 'Plugin None', 'read', 'plugin-none',  '', '', '', 'none' ),
			array( 'Plugin Div',  'read', 'plugin-div',   '', '', '', 'div' ),
		);
		$submenu = array(
			'woocommerce' => array( array( 'Home', 'read', 'wc-admin' ) ),
		);

		add_filter(
			'woocommerce_admin_menu_tree',
			function ( $tree ) {
				$tree['plugin-none'] = array( 'parent' => 'woocommerce', 'title' => 'None', 'position' => 500, 'capability' => 'read' );
				$tree['plugin-div']  = array( 'parent' => 'woocommerce', 'title' => 'Div',  'position' => 510, 'capability' => 'read' );
				return $tree;
			}
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$tree = Menu_Reconciler::get_tree();
		$this->assertArrayHasKey( 'plugin-none', $tree );
		$this->assertArrayHasKey( 'plugin-div', $tree );
		$this->assertArrayNotHasKey( 'icon', $tree['plugin-none'] );
		$this->assertArrayNotHasKey( 'icon', $tree['plugin-div'] );
	}

	/**
	 * The woocommerce_admin_menu_tree filter is applied and receives the
	 * raw $menu and $submenu.
	 */
	public function test_filter_receives_raw_menu_and_submenu() {
		global $menu, $submenu;
		// Mirror what WC's own `add_menu_page('woocommerce', …)` produces in real admin:
		// a top-level entry plus a `$submenu['woocommerce']` bucket. The filter is
		// supposed to receive both verbatim, so the fixture has to populate them.
		$menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$captured_raw_menu    = null;
		$captured_raw_submenu = null;
		add_filter(
			'woocommerce_admin_menu_tree',
			function ( $tree, $raw_menu, $raw_submenu ) use ( &$captured_raw_menu, &$captured_raw_submenu ) {
				$captured_raw_menu    = $raw_menu;
				$captured_raw_submenu = $raw_submenu;
				return $tree;
			},
			10,
			3
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$this->assertIsArray( $captured_raw_menu );
		$this->assertSame( 'woocommerce', $captured_raw_menu[0][2] );
		$this->assertIsArray( $captured_raw_submenu );
		$this->assertArrayHasKey( 'woocommerce', $captured_raw_submenu );
	}

	public function test_reconcile_invokes_native_rail_splicer_on_woo_pages(): void {
		global $menu, $submenu;
		$menu = array(
			2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
			5  => array( 'Posts', 'edit_posts', 'edit.php', 'Posts', '', 'menu-posts', 'dashicons-admin-post' ),
			55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
		);
		$submenu['woocommerce'] = array(
			array( 'Home', 'manage_woocommerce', 'wc-admin' ),
		);

		$_GET['page']       = 'wc-admin';
		$GLOBALS['pagenow'] = 'admin.php';

		wp_set_current_user( $this->admin_user_id );
		( new Menu_Reconciler() )->reconcile();

		$top_slugs = array_values( array_filter( array_map(
			static fn( $entry ) => is_array( $entry ) && isset( $entry[2] ) ? $entry[2] : null,
			$menu
		) ) );
		$this->assertNotContains( 'edit.php', $top_slugs, 'Posts should be stripped on Woo pages.' );
		$this->assertContains( 'wc-admin', $top_slugs, 'Home root should be spliced in.' );
	}
}
