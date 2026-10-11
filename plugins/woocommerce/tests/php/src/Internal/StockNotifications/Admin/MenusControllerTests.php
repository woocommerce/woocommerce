<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\MenusController;

/**
 * Tests for the Customer Stock Notifications admin menu controller.
 */
class MenusControllerTests extends \WC_Unit_Test_Case {

	/**
	 * Register the controller's hooks.
	 */
	public function setUp(): void {
		parent::setUp();
		new MenusController();
	}

	/**
	 * @testdox An unrelated screen option leaves WordPress's false status unchanged.
	 */
	public function test_unrelated_screen_option_preserves_false_status(): void {
		$this->assertFalse( apply_filters( 'set-screen-option', false, 'edit_approved_directories_per_page', 25 ) );
	}

	/**
	 * @testdox The stock notifications screen option saves its submitted page size.
	 */
	public function test_stock_notifications_screen_option_returns_page_size(): void {
		$this->assertSame( 25, apply_filters( 'set-screen-option', false, 'stock_notifications_per_page', '25' ) );
	}
}
