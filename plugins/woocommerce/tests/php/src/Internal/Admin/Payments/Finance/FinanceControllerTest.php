<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceController;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceDataSchemas;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceMenu;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceRestController;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceService;
use WC_Unit_Test_Case;

/**
 * Tests for the FinanceController class.
 */
class FinanceControllerTest extends WC_Unit_Test_Case {

	/**
	 * The feature option name.
	 *
	 * @var string
	 */
	private const OPTION = 'woocommerce_feature_payments_finance_enabled';

	/**
	 * The System Under Test.
	 *
	 * @var FinanceController
	 */
	private FinanceController $sut;

	/**
	 * The REST controller injected into the SUT.
	 *
	 * @var FinanceRestController
	 */
	private FinanceRestController $rest_controller;

	/**
	 * The admin menu injected into the SUT.
	 *
	 * @var FinanceMenu
	 */
	private FinanceMenu $menu;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->rest_controller = new FinanceRestController();
		$this->rest_controller->init( $this->getMockBuilder( FinanceService::class )->getMock(), new FinanceDataSchemas() );

		$this->menu = new FinanceMenu();

		$this->sut = new FinanceController();
		$this->sut->init( $this->rest_controller, $this->menu );
	}

	/**
	 * Drop any REST server built by a test.
	 */
	public function tearDown(): void {
		$this->clear_rest_server();
		parent::tearDown();
	}

	/**
	 * @testdox Should be hooked on init by the plugin bootstrap.
	 */
	public function test_is_hooked_on_init_by_the_plugin(): void {
		global $wp_filter;

		$hooked = false;
		foreach ( $wp_filter['init']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];
				if ( is_array( $function ) && $function[0] instanceof FinanceController && 'handle_init' === $function[1] ) {
					$hooked = true;
				}
			}
		}

		$this->assertTrue( $hooked, 'FinanceController::handle_init should be attached to init by WooCommerce::init_hooks().' );
	}

	/**
	 * @testdox Should not register the REST controller while the feature is disabled.
	 */
	public function test_does_not_register_rest_controller_when_disabled(): void {
		update_option( self::OPTION, 'no' );

		$this->sut->handle_init();

		$this->assertFalse( $this->sut->is_enabled() );
		$this->assertFalse( has_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this->rest_controller, 'handle_woocommerce_rest_api_get_rest_namespaces' ) ) );
		$this->assertFalse( has_action( 'admin_menu', array( $this->menu, 'handle_admin_menu' ) ) );
	}

	/**
	 * @testdox Should register the REST controller, its routes and the admin menu when the feature is enabled.
	 */
	public function test_registers_rest_controller_and_routes_when_enabled(): void {
		update_option( self::OPTION, 'yes' );

		$this->sut->handle_init();

		$this->assertTrue( $this->sut->is_enabled() );
		$this->assertSame( 10, has_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this->rest_controller, 'handle_woocommerce_rest_api_get_rest_namespaces' ) ) );
		$this->assertSame( 10, has_action( 'admin_menu', array( $this->menu, 'handle_admin_menu' ) ) );

		$this->clear_rest_server();
		$routes = rest_get_server()->get_routes( 'wc-admin' );

		$this->assertArrayHasKey( '/wc-admin/payments/finance/providers', $routes );
		$this->assertArrayHasKey( '/wc-admin/payments/finance/providers/(?P<gateway_id>[a-zA-Z0-9_-]+)/balance', $routes );
		$this->assertArrayHasKey( '/wc-admin/payments/finance/providers/(?P<gateway_id>[a-zA-Z0-9_-]+)/payouts', $routes );
	}
}
