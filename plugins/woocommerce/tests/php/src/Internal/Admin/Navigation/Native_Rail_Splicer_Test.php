<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Native_Rail_Splicer;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Native_Rail_Splicer
 */
class Native_Rail_Splicer_Test extends \WC_Unit_Test_Case {

	/** @var array|null */
	private $menu_backup;
	/** @var array|null */
	private $submenu_backup;

	public function setUp(): void {
		parent::setUp();
		global $menu, $submenu;
		$this->menu_backup    = is_array( $menu ) ? $menu : null;
		$this->submenu_backup = is_array( $submenu ) ? $submenu : null;
		$menu                 = array();
		$submenu              = array();
	}

	public function tearDown(): void {
		global $menu, $submenu;
		$menu    = null === $this->menu_backup ? array() : $this->menu_backup;
		$submenu = null === $this->submenu_backup ? array() : $this->submenu_backup;
		parent::tearDown();
	}

	public function test_class_exists(): void {
		$this->assertTrue( class_exists( Native_Rail_Splicer::class ) );
	}

	public function test_splice_relabels_dashboard_and_swaps_icon(): void {
		global $menu, $submenu;
		// WP's typical index.php registration: position 2, title "Dashboard",
		// cap "read", slug "index.php", page_title "Dashboard", empty class,
		// hookname "menu-dashboard", icon "dashicons-dashboard".
		$menu[2] = array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' );
		$submenu['index.php'] = array(
			array( 'Home', 'read', 'index.php' ),
			array( 'Updates', 'read', 'update-core.php' ),
		);

		$tree = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10, 'capability' => 'manage_woocommerce' ),
		);

		// Force is_woo_page to true by visiting wc-admin.
		$_GET['page']               = 'wc-admin';
		$GLOBALS['pagenow']         = 'admin.php';

		( new Native_Rail_Splicer() )->splice( $tree );

		$this->assertSame( 'Dashboard', $menu[2][0] );
		$this->assertSame( 'index.php', $menu[2][2] );
		$this->assertSame( 'dashicons-arrow-left-alt', $menu[2][6] );
		$this->assertSame( array(), $submenu['index.php'] ?? array(), 'Dashboard submenu cleared.' );
	}
}
