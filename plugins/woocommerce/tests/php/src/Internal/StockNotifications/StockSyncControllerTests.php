<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\StockSyncController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\JobManager;

/**
 * StockSyncControllerTests data tests.
 */
class StockSyncControllerTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * @var StockSyncController
	 */
	private $sut;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
		$this->sut           = new StockSyncController();
		$eligibility_service = new EligibilityService();
		$eligibility_service->init( new StockManagementHelper() );
		$job_manager = new JobManager();
		$this->sut->init( $eligibility_service, $job_manager );
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
		unset( $this->sut );
	}

	/**
	 * Test that the controller handles product stock status changes.
	 */
	public function test_handle_product_stock_status_change_to_in_stock() {

		// Create a product with out of stock status and a notification.
		$product      = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		// Change the product stock status to in stock.
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		// Check that the product is in the queue.
		$this->assertArrayHasKey( $product->get_id(), $this->get_private_property( $this->sut, 'queue' ) );

		// Check that the product runs the sync.
		$run_product_ids = array();
		\add_action(
			'woocommerce_customer_stock_notifications_product_sync',
			function ( $product_ids ) use ( &$run_product_ids ) {
				$run_product_ids = $product_ids;
			},
			100,
			3
		);
		$this->sut->process_queue();
		$this->assertContains( $product->get_id(), $run_product_ids );
	}

	/**
	 * Test that the controller handles product stock status changes.
	 */
	public function test_handle_product_stock_status_change_to_on_backorder() {

		// Create a product with on backorder status and a notification.
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		// Change the product stock status to on backorder.
		$product->set_stock_status( ProductStockStatus::ON_BACKORDER );
		$product->save();

		// Check that the product is in the queue.
		$this->assertArrayHasKey( $product->get_id(), $this->get_private_property( $this->sut, 'queue' ) );
	}

	/**
	 * Test that the controller handles variable product stock status changes.
	 */
	public function test_handle_variable_product_stock_status_change_to_in_stock() {
		$product = \WC_Helper_Product::create_variation_product();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		// Check that the product is in the queue.
		$this->assertArrayHasKey( $product->get_id(), $this->get_private_property( $this->sut, 'queue' ) );
	}

	/**
	 * Test that the controller handles variation products stock status changes.
	 */
	public function test_handle_variation_product_stock_status_change_to_in_stock() {
		$product   = \WC_Helper_Product::create_variation_product();
		$variation = $product->get_children()[0];
		$variation = wc_get_product( $variation );
		$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$variation->save();

		$notification = new Notification();
		$notification->set_product_id( $variation->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		// Refetch the variation.
		$variation = wc_get_product( $variation->get_id() );
		$this->assertEquals( ProductStockStatus::OUT_OF_STOCK, $variation->get_stock_status() );
		$variation->set_stock_status( ProductStockStatus::IN_STOCK );
		$variation->save();

		// Check that the product is in the queue.
		$this->assertArrayHasKey( $variation->get_id(), $this->get_private_property( $this->sut, 'queue' ) );
	}

	/**
	 * Test that the controller handles a variation that manages stock from the parent while the parent goes in stock.
	 */
	public function test_handle_variation_notifications_when_parent_manages_stock_and_is_in_stock() {
		$product = \WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->save();

		$variation_id = $product->get_children()[0];

		// Create a notification for the variation.
		$notification = new Notification();
		$notification->set_product_id( $variation_id );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertEquals( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );
		$product->set_stock_quantity( 10 );
		$product->save();

		// Check that the product is in the queue.
		$this->assertArrayHasKey( $product->get_id(), $this->get_private_property( $this->sut, 'queue' ) );
	}

	/**
	 * Test that the controller doesn't handle a variation that manages stock and the parent goes in stock.
	 */
	public function test_handle_variation_manages_stock_and_parent_goes_in_stock() {
		$product = \WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->save();

		$variation_id = $product->get_children()[0];
		$variation    = wc_get_product( $variation_id );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 10 );
		$variation->save();

		$notification = new Notification();
		$notification->set_product_id( $variation_id );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$product->set_stock_quantity( 10 );
		$product->save();

		$this->assertArrayNotHasKey( $variation_id, $this->get_private_property( $this->sut, 'queue' ) );
	}

	/**
	 * Test that the admin notice names the restocked products, accumulating across requests and capping at three names.
	 */
	public function test_admin_notice_names_restocked_products() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		set_current_screen( 'dashboard' );

		$products = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			$product = \WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'         => 'Restocked product ' . $i,
					'stock_status' => ProductStockStatus::OUT_OF_STOCK,
				)
			);

			$notification = new Notification();
			$notification->set_product_id( $product->get_id() );
			$notification->set_user_id( 1 );
			$notification->set_status( NotificationStatus::ACTIVE );
			$notification->save();

			$products[] = $product;
		}

		// First request restocks one product, second request (e.g. the next CSV import batch) restocks three.
		$products[0]->set_stock_status( ProductStockStatus::IN_STOCK );
		$products[0]->save();
		$this->sut->process_queue();

		foreach ( array_slice( $products, 1 ) as $product ) {
			$product->set_stock_status( ProductStockStatus::IN_STOCK );
			$product->save();
		}
		$this->sut->process_queue();

		ob_start();
		$this->sut->output_admin_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Restocked product 1', $output );
		$this->assertStringContainsString( 'Restocked product 2', $output );
		$this->assertStringContainsString( 'Restocked product 3', $output );
		$this->assertStringNotContainsString( 'Restocked product 4', $output );
		$this->assertStringContainsString( '1 more product', $output );
		$this->assertStringContainsString( 'page=wc-customer-stock-notifications', $output );
		$this->assertStringNotContainsString( 'customer_stock_notifications_product_filter', $output );

		// The notice is shown once.
		ob_start();
		$this->sut->output_admin_notice();
		$this->assertSame( '', ob_get_clean() );

		set_current_screen( 'front' );
		wp_set_current_user( 0 );
	}

	/**
	 * Test that the admin notice is only shown to the user who restocked the product.
	 */
	public function test_admin_notice_is_per_user() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		set_current_screen( 'dashboard' );

		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();
		$this->sut->process_queue();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		ob_start();
		$this->sut->output_admin_notice();
		$this->assertSame( '', ob_get_clean() );

		wp_set_current_user( $user_id );
		ob_start();
		$this->sut->output_admin_notice();
		$output = ob_get_clean();
		$this->assertStringContainsString( $product->get_name(), $output );
		$this->assertStringContainsString( 'customer_stock_notifications_product_filter=' . $product->get_id(), $output );

		set_current_screen( 'front' );
		wp_set_current_user( 0 );
	}

	/**
	 * Test that no notice is stored for restocks made outside wp-admin, without a user, or by a user who cannot manage the store.
	 *
	 * @testWith [ "front", "administrator" ]
	 *           [ "dashboard", null ]
	 *           [ "dashboard", "customer" ]
	 *
	 * @param string      $screen The current screen during the restock.
	 * @param string|null $role   The role of the user making the restock, or null for no user.
	 */
	public function test_admin_notice_is_not_stored_outside_manager_admin_requests( string $screen, ?string $role ) {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$actor_id = null === $role ? 0 : ( 'administrator' === $role ? $admin_id : $this->factory->user->create( array( 'role' => $role ) ) );
		wp_set_current_user( $actor_id );
		set_current_screen( $screen );

		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();
		$this->sut->process_queue();

		$this->assertFalse( get_transient( 'wc_customer_stock_notifications_product_sync_notice_' . $actor_id ) );

		wp_set_current_user( $admin_id );
		set_current_screen( 'dashboard' );
		ob_start();
		$this->sut->output_admin_notice();
		$this->assertSame( '', ob_get_clean() );

		set_current_screen( 'front' );
		wp_set_current_user( 0 );
	}

	/**
	 * Get a private property of an object.
	 *
	 * @param object $instance The object to get the property from.
	 * @param string $property The name of the property to get.
	 * @return mixed The value of the property.
	 */
	private function get_private_property( $instance, $property ) {
		$reflection = new \ReflectionClass( $instance );
		$property   = $reflection->getProperty( $property );
		$property->setAccessible( true );
		return $property->getValue( $instance );
	}
}
