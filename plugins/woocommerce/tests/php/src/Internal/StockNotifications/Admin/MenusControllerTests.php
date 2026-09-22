<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\MenusController;

/**
 * Tests for the Customer Stock Notifications admin menu controller.
 */
class MenusControllerTests extends \WC_Unit_Test_Case {

	/**
	 * The controller registered with the global screen option filter.
	 *
	 * @var MenusController
	 */
	private $controller;

	/**
	 * Register the controller's hooks.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->controller = new MenusController();
	}

	/**
	 * Remove the controller's hooks.
	 */
	public function tearDown(): void {
		remove_action( 'admin_menu', array( $this->controller, 'add_menu' ) );
		remove_filter( 'woocommerce_screen_ids', array( $this->controller, 'add_screen_ids' ) );
		remove_filter( 'set-screen-option', array( $this->controller, 'set_screen_option' ) );
		parent::tearDown();
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
