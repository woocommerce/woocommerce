<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsRenderer;

/**
 * Tests for FulfillmentsRenderer hooks.
 */
class FulfillmentsRendererHooksTest extends \WC_Unit_Test_Case {

	/**
	 * FulfillmentsRenderer instance.
	 *
	 * @var FulfillmentsRenderer
	 */
	private FulfillmentsRenderer $renderer;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDownAfterClass();
	}

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
	}

	/**
	 * Test hooks.
	 */
	public function test_hooks() {
		/**
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$cot_mock  = $this->createMock( CustomOrdersTableController::class );
		$cot_mock->method( 'custom_orders_table_usage_is_enabled' )->willReturn( true );
		$container->replace( CustomOrdersTableController::class, $cot_mock );

		$this->renderer->register();

		$this->assertNotFalse( has_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this->renderer, 'add_fulfillment_columns' ) ) );
		$this->assertNotFalse( has_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this->renderer, 'render_fulfillment_column_row_data' ) ) );
		$this->assertNotFalse( has_action( 'admin_footer', array( $this->renderer, 'render_fulfillment_drawer_slot' ) ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( $this->renderer, 'load_components' ) ) );
		$this->assertNotFalse( has_action( 'admin_init', array( $this->renderer, 'init_admin_hooks' ) ) );
		$container->reset_replacement( CustomOrdersTableController::class );
	}

	/**
	 * Test hooks when HPOS isn't enabled.
	 */
	public function test_hooks_legacy() {
		/**
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$cot_mock  = $this->createMock( CustomOrdersTableController::class );
		$cot_mock->method( 'custom_orders_table_usage_is_enabled' )->willReturn( false );
		$container->replace( CustomOrdersTableController::class, $cot_mock );

		$this->renderer->register();

		$this->assertNotFalse( has_filter( 'manage_edit-shop_order_columns', array( $this->renderer, 'add_fulfillment_columns' ) ) );
		$this->assertNotFalse( has_action( 'manage_shop_order_posts_custom_column', array( $this->renderer, 'render_fulfillment_column_row_data_legacy' ) ) );
		$this->assertNotFalse( has_action( 'admin_footer', array( $this->renderer, 'render_fulfillment_drawer_slot' ) ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( $this->renderer, 'load_components' ) ) );
		$this->assertNotFalse( has_action( 'admin_init', array( $this->renderer, 'init_admin_hooks' ) ) );
		$container->reset_replacement( CustomOrdersTableController::class );
	}

	/**
	 * Test that the admin_init hooks are registered.
	 */
	public function test_admin_init_hooks() {
		/**
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$cot_mock  = $this->createMock( CustomOrdersTableController::class );
		$cot_mock->method( 'custom_orders_table_usage_is_enabled' )->willReturn( true );
		$container->replace( CustomOrdersTableController::class, $cot_mock );

		$this->renderer->register();

		$this->renderer->init_admin_hooks();
		$this->assertNotFalse( has_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this->renderer, 'define_fulfillment_bulk_actions' ) ) );
		$this->assertNotFalse( has_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this->renderer, 'handle_fulfillment_bulk_actions' ) ) );
		$container->reset_replacement( CustomOrdersTableController::class );
	}

	/**
	 * Test that the admin_init hooks are registered when HPOS isn't enabled.
	 */
	public function test_admin_init_hooks_legacy() {
		/**
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$cot_mock  = $this->createMock( CustomOrdersTableController::class );
		$cot_mock->method( 'custom_orders_table_usage_is_enabled' )->willReturn( false );
		$container->replace( CustomOrdersTableController::class, $cot_mock );

		$this->renderer->register();

		$this->renderer->init_admin_hooks();
		$this->assertNotFalse( has_filter( 'bulk_actions-edit-shop_order', array( $this->renderer, 'define_fulfillment_bulk_actions' ) ) );
		$this->assertNotFalse( has_filter( 'handle_bulk_actions-edit-shop_order', array( $this->renderer, 'handle_fulfillment_bulk_actions' ) ) );
		$container->reset_replacement( CustomOrdersTableController::class );
	}
}
