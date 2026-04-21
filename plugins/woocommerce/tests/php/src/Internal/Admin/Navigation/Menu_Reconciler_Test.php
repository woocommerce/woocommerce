<?php

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
	 * The woocommerce_admin_menu_tree filter is applied and receives the
	 * raw $menu and $submenu.
	 */
	public function test_filter_receives_raw_menu_and_submenu() {
		global $menu, $submenu;
		$menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$submenu = array();

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
	}
}
