<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\AsyncTasks;

use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\JobManager;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\CycleStateService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use WC_Product;
use WC_Helper_Product;

/**
 * Tests for NotificationsProcessor class
 */
class NotificationsProcessorTests extends \WC_Unit_Test_Case {

	/**
	 * @var NotificationsProcessor
	 */
	private $sut;

	/**
	 * Set up test case
	 */
	public function setUp(): void {
		parent::setUp();
		\WC()->queue()->cancel_all( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS );

		$eligibility_service = new EligibilityService();
		$eligibility_service->init( new StockManagementHelper() );
		$job_manager         = new JobManager();
		$cycle_state_service = new CycleStateService();
		$email_manager       = new EmailManager();
		$this->sut           = new NotificationsProcessor();
		$this->sut->init( $eligibility_service, $job_manager, $cycle_state_service, $email_manager );
	}

		/**
		 * Clean up after tests
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
	 * Test parse_args method.
	 */
	public function test_parse_args() {
		$product_id = 123;
		$method     = $this->get_private_method( $this->sut, 'parse_args' );
		$result     = $method->invokeArgs( $this->sut, array( $product_id ) );
		$this->assertEquals( 123, $result );

		$product_id = 0;
		$this->expectException( \Exception::class );
		$method->invokeArgs( $this->sut, array( $product_id ) );

		$product_id = 'test';
		$this->expectException( \Exception::class );
		$method->invokeArgs( $this->sut, array( $product_id ) );

		$product_id = array();
		$this->expectException( \Exception::class );
		$method->invokeArgs( $this->sut, array( $product_id ) );
	}

	/**
	 * Test parse_product method.
	 */
	public function test_parse_product() {
		$product = WC_Helper_Product::create_simple_product();
		$method  = $this->get_private_method( $this->sut, 'parse_product' );
		$result  = $method->invokeArgs( $this->sut, array( $product->get_id() ) );
		$this->assertInstanceOf( WC_Product::class, $result );

		$product->set_status( ProductStatus::TRASH );
		$product->save();
		$this->expectException( \Exception::class );
		$method->invokeArgs( $this->sut, array( $product->get_id() ) );

		$product_id = 0;
		$this->expectException( \Exception::class );
		$method->invokeArgs( $this->sut, array( $product_id ) );

		$product_id = 'test';
		$this->expectException( \Exception::class );
		$method->invokeArgs( $this->sut, array( $product_id ) );

		$product_id = array();
		$this->expectException( \Exception::class );
		$method->invokeArgs( $this->sut, array( $product_id ) );
	}

	/**
	 * Test process_batch method on a simple product.
	 */
	public function test_process_batch_simple_product() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );
		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() ) );

		// Test that the notification is sent.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification->get_status() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_notified()->getTimestamp(), 10 );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 10 );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test process_batch method on a variation product.
	 */
	public function test_process_batch_variation_product() {
		$product   = WC_Helper_Product::create_variation_product();
		$variation = $product->get_children()[0];
		$variation = wc_get_product( $variation );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 10 );
		$variation->save();

		$notification = new Notification();
		$notification->set_product_id( $variation->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$notification_on_parent = new Notification();
		$notification_on_parent->set_product_id( $product->get_id() );
		$notification_on_parent->set_user_id( 1 );
		$notification_on_parent->set_status( NotificationStatus::ACTIVE );
		$notification_on_parent->save();

		$this->sut->process_batch( $variation->get_id() );

		// Refetch notification.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification->get_status() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification_on_parent->get_status() );
	}

	/**
	 * Test process_batch method on a variable product.
	 */
	public function test_process_batch_variable() {
		$variable = WC_Helper_Product::create_variation_product();
		$variable->set_manage_stock( true );
		$variable->set_stock_quantity( 10 );
		$variable->save();
		$this->assertEquals( true, $variable->get_manage_stock() );
		$this->assertEquals( 'instock', $variable->get_stock_status() );

		$variations     = $variable->get_children();
		$last_variation = null;
		foreach ( $variations as $variation ) {
			$variation = wc_get_product( $variation );
			$variation->set_manage_stock( true );
			$this->assertEquals( 'parent', $variation->get_manage_stock() );
			$last_variation = $variation;
		}

		$notification = new Notification();
		$notification->set_product_id( $variable->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$notification_on_variation = new Notification();
		$notification_on_variation->set_product_id( $last_variation->get_id() );
		$notification_on_variation->set_user_id( 1 );
		$notification_on_variation->set_status( NotificationStatus::ACTIVE );
		$notification_on_variation->save();

		$this->sut->process_batch( $variable->get_id() );

		// Refetch notifications.
		$notification              = new Notification( $notification->get_id() );
		$notification_on_variation = new Notification( $notification_on_variation->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification->get_status() );
		$this->assertEquals( NotificationStatus::SENT, $notification_on_variation->get_status() );
	}

	/**
	 * Test process_batch method bail out when product is not in stock.
	 */
	public function test_process_batch_bail_out_when_product_is_not_in_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );
		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() ) );

		// Test that the notification is not sent.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertEmpty( $notification->get_date_notified() );
		$this->assertEmpty( $notification->get_date_last_attempt() );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test process_batch method bail out when product is not published.
	 */
	public function test_process_batch_bail_out_when_product_is_not_published() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'test@test.com' ); // Signup as guest.
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );
		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() ) );

		// Test that the notification is not sent.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertEmpty( $notification->get_date_notified() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 5 );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test process_batch method on a product with a pending notification.
	 */
	public function test_process_batch_pending() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );
		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() ) );

		// Test that the notification is not sent.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::PENDING, $notification->get_status() );
		$this->assertEmpty( $notification->get_date_notified() );
		$this->assertEmpty( $notification->get_date_last_attempt() );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test process_batch method on a product with a notification that is cancelled.
	 */
	public function test_process_batch_cancelled() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::CANCELLED );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );

		// Test that the notification is not sent.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::CANCELLED, $notification->get_status() );
		$this->assertEmpty( $notification->get_date_notified() );
		$this->assertEmpty( $notification->get_date_last_attempt() );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test process_batch method on a product with a notification that is throttled.
	 */
	public function test_process_batch_throttled() {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'test@test.com' ); // Signup as guest.
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );

		// Test that the notification is sent the first time.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification->get_status() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_notified()->getTimestamp(), 5 );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 5 );

		// Manual Re-activation.
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );

		// Test that the notification is throttled for the second time.
		$notification = new Notification( $notification->get_id() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 5 );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test process_batch method with multiple batches running in sequence.
	 */
	public function test_process_batch_multiple_batches() {
		tests_add_filter(
			'woocommerce_customer_stock_notifications_batch_size',
			function () {
				return 1;
			}
		);

		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'test@test.com' ); // Signup as guest.
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();
		$notification2 = new Notification();
		$notification2->set_product_id( $product->get_id() );
		$notification2->set_user_email( 'test2@test.com' );
		$notification2->set_status( NotificationStatus::ACTIVE );
		$notification2->save();

		$this->sut->process_batch( $product->get_id() );

		// Test that the notification is sent.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification->get_status() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_notified()->getTimestamp(), 5 );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 5 );

		// Check the second notification is not sent.
		$notification2 = new Notification( $notification2->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification2->get_status() );
		$this->assertEmpty( $notification2->get_date_notified() );
		$this->assertEmpty( $notification2->get_date_last_attempt() );

		// Test there is a next job.
		$this->assertNotEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
		WC()->queue()->cancel_all( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS );

		// Test that state is saved.
		$this->assertNotEmpty( get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() ) );

		// Run the next job.
		$this->sut->process_batch( $product->get_id() );

		// Test that the notification is sent.
		$notification2 = new Notification( $notification2->get_id() );
		$this->assertEquals( NotificationStatus::SENT, $notification2->get_status() );
		$this->assertEqualsWithDelta( time(), $notification2->get_date_notified()->getTimestamp(), 5 );

		// Since max_batch_size is 1, there will be another job scheduled to wrap up the cycle.
		$this->assertNotEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
		WC()->queue()->cancel_all( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS );

		// Run the next job.
		$this->sut->process_batch( $product->get_id() );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() ) );
	}

	/**
	 * Test process_batch method on a product with a notification that is skipped.
	 */
	public function test_process_batch_skipped() {

		tests_add_filter(
			'woocommerce_customer_stock_notifications_batch_size',
			function () {
				return 1;
			}
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'test@test.com' ); // Signup as guest.
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->assertTrue( $product->is_in_stock() );
		$this->sut->process_batch( $product->get_id() );

		// Test that the notification is not sent.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertEmpty( $notification->get_date_notified() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 5 );

		// Considering batch size is 1, there will be another job scheduled to wrap up the cycle.
		$this->assertNotEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
		WC()->queue()->cancel_all( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS );

		// Run the next job.
		$this->sut->process_batch( $product->get_id() );

		// Test that the notification is not sent.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertEmpty( $notification->get_date_notified() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 5 );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Test for unknown/deleted product.
	 */
	public function test_process_batch_unknown_product() {
		$product_id = 123;
		$this->sut->process_batch( $product_id );
		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product_id ) );
	}

	/**
	 * Test process_batch method on a product with no notifications.
	 */
	public function test_process_batch_no_notifications() {
		$product = WC_Helper_Product::create_simple_product();

		$this->sut->process_batch( $product->get_id() );

		$this->assertFalse( get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() ) );

		// Test there is no next job.
		$this->assertEmpty(
			WC()->queue()->get_next(
				JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				array( 'product_id' => $product->get_id() ),
				JobManager::AS_JOB_GROUP
			)
		);
	}

	/**
	 * Get private method.
	 *
	 * @param object $instance The object.
	 * @param string $method_name The method name.
	 * @return \ReflectionMethod
	 */
	private function get_private_method( $instance, $method_name ) {
		$method = new \ReflectionMethod( $instance, $method_name );
		$method->setAccessible( true );
		return $method;
	}
}
