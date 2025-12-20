<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\MenuCompatibilityController;

/**
 * Tests for the MenuCompatibilityController class.
 *
 * Tests backwards compatibility when the Orders menu is promoted from a submenu
 * under "WooCommerce" to a top-level menu item.
 */
class MenuCompatibilityControllerTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MenuCompatibilityController
	 */
	private $sut;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new MenuCompatibilityController();
	}

	/**
	 * @testdox Hook compatibility registers prefixed hooks for each mapping.
	 */
	public function test_register_hook_compatibility_registers_prefixed_hooks(): void {
		$hook_mappings = array(
			'toplevel_page_wc-orders' => 'woocommerce_page_wc-orders',
		);

		$this->sut->register_hook_compatibility( $hook_mappings );

		$expected_hooks = array(
			'load-toplevel_page_wc-orders',
			'admin_print_styles-toplevel_page_wc-orders',
			'admin_print_scripts-toplevel_page_wc-orders',
			'admin_head-toplevel_page_wc-orders',
			'admin_footer-toplevel_page_wc-orders',
			'admin_print_footer_scripts-toplevel_page_wc-orders',
			'toplevel_page_wc-orders',
		);

		foreach ( $expected_hooks as $hook ) {
			$this->assertTrue(
				has_action( $hook ) !== false,
				"Expected hook '{$hook}' to be registered"
			);
		}
	}

	/**
	 * @testdox Hook compatibility fires expected hooks when actual hooks fire.
	 */
	public function test_register_hook_compatibility_fires_expected_hooks(): void {
		$hook_mappings = array(
			'toplevel_page_wc-orders' => 'woocommerce_page_wc-orders',
		);

		$this->sut->register_hook_compatibility( $hook_mappings );

		$expected_hook_fired = false;
		add_action(
			'load-woocommerce_page_wc-orders',
			function () use ( &$expected_hook_fired ) {
				$expected_hook_fired = true;
			}
		);

		do_action( 'load-toplevel_page_wc-orders' );

		$this->assertTrue( $expected_hook_fired, 'Expected hook should fire when actual hook fires' );
	}

	/**
	 * @testdox Hook compatibility fires base hook without prefix.
	 */
	public function test_register_hook_compatibility_fires_base_hook(): void {
		$hook_mappings = array(
			'toplevel_page_wc-orders' => 'woocommerce_page_wc-orders',
		);

		$this->sut->register_hook_compatibility( $hook_mappings );

		$expected_hook_fired = false;
		add_action(
			'woocommerce_page_wc-orders',
			function () use ( &$expected_hook_fired ) {
				$expected_hook_fired = true;
			}
		);

		do_action( 'toplevel_page_wc-orders' );

		$this->assertTrue( $expected_hook_fired, 'Expected base hook should fire when actual hook fires' );
	}

	/**
	 * @testdox Hook compatibility does not cause infinite loops.
	 */
	public function test_register_hook_compatibility_prevents_infinite_loops(): void {
		$hook_mappings = array(
			'toplevel_page_wc-orders' => 'woocommerce_page_wc-orders',
		);

		$this->sut->register_hook_compatibility( $hook_mappings );

		$call_count = 0;
		add_action(
			'woocommerce_page_wc-orders',
			function () use ( &$call_count ) {
				++$call_count;
			}
		);

		do_action( 'toplevel_page_wc-orders' );

		$this->assertSame( 1, $call_count, 'Hook should only fire once, not infinitely' );
	}

	/**
	 * @testdox Screen ID compatibility modifies screen properties on current_screen action.
	 */
	public function test_register_screen_id_compatibility_modifies_screen(): void {
		$hook_mappings = array(
			'toplevel_page_wc-orders' => 'woocommerce_page_wc-orders',
		);

		$this->sut->register_hook_compatibility( $hook_mappings );

		set_current_screen( 'toplevel_page_wc-orders' );
		$screen = get_current_screen();

		$this->assertSame( 'woocommerce_page_wc-orders', $screen->id );
		$this->assertSame( 'woocommerce_page_wc-orders', $screen->base );
		$this->assertSame( 'toplevel_page_wc-orders', $screen->original_id );
		$this->assertSame( 'toplevel_page_wc-orders', $screen->original_base );
	}

	/**
	 * @testdox Screen ID compatibility does not modify unrelated screens.
	 */
	public function test_register_screen_id_compatibility_ignores_unrelated_screens(): void {
		$hook_mappings = array(
			'toplevel_page_wc-orders' => 'woocommerce_page_wc-orders',
		);

		$this->sut->register_hook_compatibility( $hook_mappings );

		set_current_screen( 'dashboard' );
		$screen = get_current_screen();

		$this->assertSame( 'dashboard', $screen->id );
		$this->assertSame( 'dashboard', $screen->base );
		$this->assertObjectNotHasProperty( 'original_id', $screen );
	}

	/**
	 * @testdox Hook compatibility handles multiple mappings.
	 */
	public function test_register_hook_compatibility_handles_multiple_mappings(): void {
		$hook_mappings = array(
			'toplevel_page_wc-orders'                  => 'woocommerce_page_wc-orders',
			'toplevel_page_wc-orders--shop_subscription' => 'woocommerce_page_wc-orders--shop_subscription',
		);

		$this->sut->register_hook_compatibility( $hook_mappings );

		$this->assertTrue(
			has_action( 'load-toplevel_page_wc-orders' ) !== false,
			'First mapping should be registered'
		);
		$this->assertTrue(
			has_action( 'load-toplevel_page_wc-orders--shop_subscription' ) !== false,
			'Second mapping should be registered'
		);
	}
}
