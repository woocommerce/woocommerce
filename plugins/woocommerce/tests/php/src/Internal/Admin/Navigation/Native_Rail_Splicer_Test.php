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
}
