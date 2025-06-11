<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\AsyncTasks;

use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsBatchProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\StockSyncController;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\NotificationEligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductStatus;

/**
 * Class NotificationsBatchProcessorTests.
 */
class NotificationsBatchProcessorTests extends \WC_Unit_Test_Case {

	/**
	 * @var NotificationsBatchProcessor
	 */
	private $sut;

	/**
	 * @before
	 */
	public function setUp(): void {
		parent::setUp();
		$eligibility_service         = new NotificationEligibilityService();
		$batch_processing_controller = new BatchProcessingController();
		$this->sut                   = new NotificationsBatchProcessor();
		$this->sut->init( new EmailManager(), $eligibility_service, $batch_processing_controller );
	}

	/**
	 * @after
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
	 * @testdox get_total_pending_count returns 0 for no active notifications
	 */
	public function test_get_total_pending_count(): void {
		$this->assertEquals( 0, $this->sut->get_total_pending_count() );
		$this->create_product_with_active_notification();
		$this->assertEquals( 1, $this->sut->get_total_pending_count() );
		$this->create_product_with_active_notification();
		$this->assertEquals( 2, $this->sut->get_total_pending_count() );
	}

	/**
	 * @testdox get_next_batch_to_process returns empty array for no active notifications
	 */
	public function test_get_next_batch_to_process(): void {
		$this->assertEquals( array(), $this->sut->get_next_batch_to_process( 10 ) );

		list( $product, $notification ) = $this->create_product_with_active_notification();

		$batch = $this->sut->get_next_batch_to_process( 10 );
		$this->assertEquals( array( $notification->get_id() ), $batch );

		// Delete the meta and try again.
		delete_post_meta( $product->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY );
		$batch = $this->sut->get_next_batch_to_process( 10 );
		$this->assertEquals( array(), $batch );

		// Set the meta to a future date and try again.
		// Since the notification should have null "Date_last_attempt" it should be included again.
		update_post_meta( $product->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY, time() + 10 );
		$batch = $this->sut->get_next_batch_to_process( 10 );
		$this->assertEquals( array( $notification->get_id() ), $batch );
	}

	/**
	 * @testdox process_batch sends notification for product with active notification
	 */
	public function test_process_batch(): void {
		list( $product, $notification ) = $this->create_product_with_active_notification();
		$batch                          = $this->sut->get_next_batch_to_process( 10 );

		$this->assertEquals( array( $notification->get_id() ), $batch );
		$this->sut->process_batch( $batch );

		// Check the notification.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification->get_status() );
		$this->assertNotEmpty( $notification->get_date_notified() );
		$this->assertNotEmpty( $notification->get_date_last_attempt() );

		$batch = $this->sut->get_next_batch_to_process( 10 );
		$this->assertEquals( array(), $batch );
	}

	/**
	 * @testdox process_batch prevents notification for product with out of stock
	 */
	public function test_process_batch_with_out_of_stock_product(): void {
		list( $product, $notification ) = $this->create_product_with_active_notification();
		$batch                          = $this->sut->get_next_batch_to_process( 10 );

		$this->assertEquals( array( $notification->get_id() ), $batch );

		// Make the product out-of-stock after calculating the batch.
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		// Process the batch.
		$this->sut->process_batch( $batch );

		// Check the notification.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertNotEmpty( $notification->get_date_last_attempt() );
	}

	/**
	 * @testdox process_batch_with_throttled_notification sends notification for product with active notification
	 */
	public function test_process_batch_with_throttled_notification(): void {
		list( $product, $notification ) = $this->create_product_with_active_notification();
		// Set the notification email only so it doesn't get bypassed due to a privileged user.
		$notification->set_user_email( 'test@test.com' );
		$notification->set_user_id( 0 );
		$notification->save();

		$batch = $this->sut->get_next_batch_to_process( 10 );
		$this->assertEquals( array( $notification->get_id() ), $batch );
		$this->sut->process_batch( $batch );

		// Check the notification.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification->get_status() );
		$this->assertNotEmpty( $notification->get_date_last_attempt() );
		$this->assertNotEmpty( $notification->get_date_notified() );

		// Set the notification to active again.
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		// Process the batch again.
		$batch = $this->sut->get_next_batch_to_process( 10 );
		$this->assertEquals( array( $notification->get_id() ), $batch );
		$this->sut->process_batch( $batch );

		// Check the notification.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertNotEmpty( $notification->get_date_last_attempt() );
	}

	/**
	 * @testdox process_batch_with_skipped_filter prevents notification for product with active notification
	 */
	public function test_process_batch_with_skipped_filter() {
		list( $product, $notification ) = $this->create_product_with_active_notification();
		$batch                          = $this->sut->get_next_batch_to_process( 10 );
		$this->assertEquals( array( $notification->get_id() ), $batch );

		// Add the filter.
		$skip_callback = function ( $should_skip, $notification_id ) use ( $notification ) {
			return $notification_id === $notification->get_id();
		};
		\add_filter(
			'woocommerce_customer_stock_notification_should_skip_sending',
			$skip_callback,
			10,
			2
		);

		$this->sut->process_batch( $batch );

		// Refetch.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertNotEmpty( $notification->get_date_last_attempt() );

		// Remove the filter.
		\remove_filter(
			'woocommerce_customer_stock_notification_should_skip_sending',
			$skip_callback
		);

		// Process the batch again.
		$this->sut->process_batch( $batch );

		// Check the notification.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification->get_status() );
		$this->assertNotEmpty( $notification->get_date_last_attempt() );
	}

	/**
	 * @testdox process_batch_with_unpublished_product prevents notification for product with active notification
	 */
	public function test_process_batch_with_unpublished_product(): void {
		list( $product, $notification ) = $this->create_product_with_active_notification();
		// Set the user email only so it doesn't get bypassed due to a privileged user.
		$notification->set_user_email( 'test@test.com' );
		$notification->set_user_id( 0 );
		$notification->save();

		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		$batch = $this->sut->get_next_batch_to_process( 10 );
		$this->assertEquals( array( $notification->get_id() ), $batch );
		$this->sut->process_batch( $batch );

		// Check the notification.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertNotEmpty( $notification->get_date_last_attempt() );
	}

	/**
	 * @testdox process_batch_with_multiple_batches processes multiple batches
	 */
	public function test_process_batch_with_multiple_batches(): void {
		list( $product, $notification )   = $this->create_product_with_active_notification();
		list( $product2, $notification2 ) = $this->create_product_with_active_notification();
		list( $product3, $notification3 ) = $this->create_product_with_active_notification();

		$batch = $this->sut->get_next_batch_to_process( 1 );
		$this->assertEquals( array( $notification->get_id() ), $batch );
		$this->sut->process_batch( $batch );

		$batch = $this->sut->get_next_batch_to_process( 1 );
		$this->assertEquals( array( $notification2->get_id() ), $batch );
		$this->sut->process_batch( $batch );

		$batch = $this->sut->get_next_batch_to_process( 1 );
		$this->assertEquals( array( $notification3->get_id() ), $batch );
		$this->sut->process_batch( $batch );

		$batch = $this->sut->get_next_batch_to_process( 1 );
		$this->assertEquals( array(), $batch );
	}

	/**
	 * Creates a product with an active notification.
	 *
	 * @return array
	 */
	private function create_product_with_active_notification(): array {
		$product = \WC_Helper_Product::create_simple_product();
		// Replicate the stock sync controller behavior.
		update_post_meta( $product->get_id(), StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY, time() - 10 );

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		return array( $product, $notification );
	}
}
