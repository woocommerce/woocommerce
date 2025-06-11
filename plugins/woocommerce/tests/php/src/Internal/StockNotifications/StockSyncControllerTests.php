<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\StockSyncController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\NotificationEligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;

/**
 * StockSyncControllerTests data tests.
 */
class StockSyncControllerTests extends \WC_Unit_Test_Case {

	/**
	 * @var StockSyncController
	 */
	private $sut;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut           = new StockSyncController();
		$eligibility_service = new NotificationEligibilityService();
		$eligibility_service->init( new StockManagementHelper() );
		$this->sut->init( $eligibility_service );
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		unset( $this->sut );
		// Clean up all notifications.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );
	}

	/**
	 * Test simple product stock status changes.
	 */
	public function test_simple_product_goes_in_stock() {
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
		$run_product_id = false;
		\tests_add_filter(
			'woocommerce_customer_stock_notifications_product_sync',
			function ( $product_ids ) use ( &$run_product_id ) {
				$run_product_id = $product_ids[0];
			},
			100,
			3
		);
		$this->sut->process_queue();
		$this->assertEquals( $product->get_id(), $run_product_id );

		// Check the timestamp is updated.
		$this->assertEqualsWithDelta(
			time(),
			get_post_meta( $product->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY, true ),
			5
		);
	}

	/**
	 * Test simple product stock status changes to backorder.
	 */
	public function test_simple_product_goes_on_backorder() {

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
	 * Test simple product stock status goes out of stock.
	 */
	public function test_simple_product_goes_out_of_stock() {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'stock_status' => ProductStockStatus::IN_STOCK,
			)
		);

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$this->assertArrayNotHasKey( $product->get_id(), $this->get_private_property( $this->sut, 'queue' ) );
		$this->assertEmpty( get_post_meta( $product->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY ) );
	}

	/**
	 * Test variable product stock status changes to in stock.
	 */
	public function test_variable_product_goes_in_stock() {
		$product = \WC_Helper_Product::create_variation_product();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertEquals( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->save();

		// Check that the product is in the queue.
		$this->assertArrayHasKey( $product->get_id(), $this->get_private_property( $this->sut, 'queue' ) );

		$this->sut->process_queue();
		$this->assertEqualsWithDelta(
			time(),
			get_post_meta( $product->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY, true ),
			5
		);
	}

	/**
	 * Test variation that manages stock and goes in stock.
	 */
	public function test_variation_manages_stock_and_goes_in_stock() {
		$product   = \WC_Helper_Product::create_variation_product();
		$variation = $product->get_children()[0];
		$variation = wc_get_product( $variation );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 0 );
		$variation->save();

		$notification = new Notification();
		$notification->set_product_id( $variation->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		// Refetch the variation.
		$variation = wc_get_product( $variation->get_id() );
		$this->assertEquals( ProductStockStatus::OUT_OF_STOCK, $variation->get_stock_status() );
		$variation->set_stock_quantity( 10 );
		$variation->save();

		// Check that the product is in the queue.
		$this->assertArrayHasKey( $variation->get_id(), $this->get_private_property( $this->sut, 'queue' ) );
		$this->sut->process_queue();
		$this->assertEqualsWithDelta(
			time(),
			get_post_meta( $variation->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY, true ),
			5
		);
	}

	/**
	 * Test variation that manages stock and the parent goes in stock.
	 */
	public function test_variation_manages_stock_and_parent_goes_in_stock() {
		$product = \WC_Helper_Product::create_variation_product();

		$variation_id = $product->get_children()[0];
		$variation    = wc_get_product( $variation_id );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 0 );
		$variation->save();

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
		$this->assertArrayNotHasKey( $product->get_id(), $this->get_private_property( $this->sut, 'queue' ) );

		$this->sut->process_queue();
		$this->assertEmpty( get_post_meta( $product->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY ) );
		$this->assertEmpty( get_post_meta( $variation->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY ) );
	}

	/**
	 * Test variation that doesn't manage stock and the parent goes in stock.
	 */
	public function test_variation_does_not_manage_stock_and_parent_goes_in_stock() {
		$product   = \WC_Helper_Product::create_variation_product();
		$variation = $product->get_children()[0];
		$variation = wc_get_product( $variation );
		$variation->set_manage_stock( false );
		$variation->save();

		$notification = new Notification();
		$notification->set_product_id( $variation->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertEquals( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );
		$product->set_stock_quantity( 10 );
		$product->save();

		// Check that the product is in the queue.
		$this->assertArrayHasKey( $product->get_id(), $this->get_private_property( $this->sut, 'queue' ) );

		$this->sut->process_queue();
		$this->assertEqualsWithDelta(
			time(),
			get_post_meta( $product->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY, true ),
			5
		);
		$this->assertEqualsWithDelta(
			time(),
			get_post_meta( $variation->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY, true ),
			5
		);
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
