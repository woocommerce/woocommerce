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
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Product;
use WC_Helper_Product;

/**
 * Tests for NotificationsProcessor class
 */
class NotificationsProcessorTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * @var NotificationsProcessor
	 */
	private $sut;

	/**
	 * What the short-circuited `wp_mail()` reports back.
	 *
	 * @var bool
	 */
	private $wp_mail_result = true;

	/**
	 * Set up test case
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
		\WC()->queue()->cancel_all( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS );

		// Short-circuit `wp_mail()` so the tests never attempt a real send, while still
		// letting each test choose what the mailer reports back.
		$this->wp_mail_result = true;
		add_filter( 'pre_wp_mail', array( $this, 'short_circuit_wp_mail' ) );

		$eligibility_service = new EligibilityService();
		$eligibility_service->init( new StockManagementHelper() );
		$job_manager         = new JobManager();
		$cycle_state_service = new CycleStateService();
		$email_manager       = new EmailManager();
		$email_manager->init();
		$this->sut = new NotificationsProcessor();
		$this->sut->init( $eligibility_service, $job_manager, $cycle_state_service, $email_manager );

		// Rebuild the `WC_Emails` singleton so the BIS email registered above is part of it.
		$this->reset_email_singleton();
		WC()->mailer();
	}

		/**
		 * Clean up after tests
		 */
	public function tearDown(): void {
		unset( $this->sut );
		$this->reset_email_singleton();
		// Clean up all notifications.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notificationmeta" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notifications" );
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * Reset the `WC_Emails` singleton so the next `WC()->mailer()` call rebuilds it.
	 */
	private function reset_email_singleton(): void {
		$instance_property = ( new \ReflectionClass( \WC_Emails::class ) )->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );
	}

	/**
	 * `pre_wp_mail` filter: skip the actual send and report the configured result.
	 *
	 * @return bool
	 */
	public function short_circuit_wp_mail(): bool {
		return $this->wp_mail_result;
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
		$notification->set_user_email( 'test@test.com' );
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
		$notification->set_user_email( 'test@test.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$notification_on_parent = new Notification();
		$notification_on_parent->set_product_id( $product->get_id() );
		$notification_on_parent->set_user_id( 1 );
		$notification_on_parent->set_user_email( 'test@test.com' );
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
		$notification->set_user_email( 'test@test.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$notification_on_variation = new Notification();
		$notification_on_variation->set_product_id( $last_variation->get_id() );
		$notification_on_variation->set_user_id( 1 );
		$notification_on_variation->set_user_email( 'test@test.com' );
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
	 * Test process_batch on a parent that does not manage stock leaves out-of-stock variations alone.
	 */
	public function test_process_batch_variable_without_parent_stock_skips_out_of_stock_siblings() {
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = array_map( 'wc_get_product', $variable->get_children() );
		$this->assertFalse( $variable->get_manage_stock() );

		$restocked = $variations[0];
		$restocked->set_stock_status( ProductStockStatus::IN_STOCK );
		$restocked->save();
		$sibling = $variations[1];
		$sibling->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$sibling->save();

		\WC_Product_Variable::sync( $variable->get_id() );
		$variable = wc_get_product( $variable->get_id() );
		$this->assertEquals( ProductStockStatus::IN_STOCK, $variable->get_stock_status() );

		$notification = new Notification();
		$notification->set_product_id( $sibling->get_id() );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->sut->process_batch( $variable->get_id() );

		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertEmpty( $notification->get_date_notified() );
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
	 * @testdox Should mark a sign-up Sent only when the email actually goes out, and count it as failed otherwise.
	 *
	 * @dataProvider send_outcome_provider
	 *
	 * @param bool   $email_enabled   Whether the back-in-stock email is enabled.
	 * @param bool   $wp_mail_result  What `wp_mail()` reports back.
	 * @param string $expected_status The status the sign-up should end up in.
	 * @param int    $expected_sent   Expected `sent_count` in the cycle state.
	 * @param int    $expected_failed Expected `failed_count` in the cycle state.
	 */
	public function test_process_batch_marks_sent_only_when_email_is_sent( bool $email_enabled, bool $wp_mail_result, string $expected_status, int $expected_sent, int $expected_failed ): void {
		// Batch size 1 keeps the cycle state around after the first run, so the counters can be inspected.
		add_filter( 'woocommerce_customer_stock_notifications_batch_size', fn() => 1 );
		add_filter( 'woocommerce_email_enabled_customer_stock_notification', fn() => $email_enabled );
		$this->wp_mail_result = $wp_mail_result;

		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'test@test.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );

		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( $expected_status, $notification->get_status() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 5 );
		if ( NotificationStatus::SENT === $expected_status ) {
			$this->assertEqualsWithDelta( time(), $notification->get_date_notified()->getTimestamp(), 5 );
		} else {
			$this->assertEmpty( $notification->get_date_notified(), 'A sign-up that was not emailed must not record a notified date.' );
		}

		$cycle_state = get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() );
		$this->assertSame( $expected_sent, $cycle_state['sent_count'] );
		$this->assertSame( $expected_failed, $cycle_state['failed_count'] );

		WC()->queue()->cancel_all( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS );
	}

	/**
	 * Data provider for test_process_batch_marks_sent_only_when_email_is_sent.
	 *
	 * @return array[]
	 */
	public function send_outcome_provider(): array {
		return array(
			'email sent'     => array( true, true, NotificationStatus::SENT, 1, 0 ),
			'email disabled' => array( false, true, NotificationStatus::CANCELLED, 0, 1 ),
			'wp_mail failed' => array( true, false, NotificationStatus::CANCELLED, 0, 1 ),
		);
	}

	/**
	 * @testdox Should mark a sign-up Sent and fire the sent action when the pre-send filter takes over delivery, and take the failure branch when it reports a failure.
	 *
	 * @dataProvider pre_send_filter_provider
	 *
	 * @param mixed  $handled         What the `woocommerce_customer_stock_notification_pre_send` filter returns.
	 * @param string $expected_status The status the sign-up should end up in.
	 */
	public function test_process_batch_honours_pre_send_filter( $handled, string $expected_status ): void {
		// Batch size 1 keeps the cycle state around after the first run, so the counters can be inspected.
		add_filter( 'woocommerce_customer_stock_notifications_batch_size', fn() => 1 );
		add_filter( 'woocommerce_customer_stock_notification_pre_send', fn() => $handled );
		$sent_action_args = array();
		add_action(
			'woocommerce_customer_stock_notification_sent',
			function ( $notification, $product ) use ( &$sent_action_args ) {
				$sent_action_args[] = array( $notification->get_id(), $product->get_id() );
			},
			10,
			2
		);

		$product      = WC_Helper_Product::create_simple_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'test@test.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->sut->process_batch( $product->get_id() );

		$notification = new Notification( $notification->get_id() );
		$cycle_state  = get_option( CycleStateService::STATE_OPTION_PREFIX . $product->get_id() );
		$this->assertEquals( $expected_status, $notification->get_status() );
		$this->assertEqualsWithDelta( time(), $notification->get_date_last_attempt()->getTimestamp(), 5 );
		if ( NotificationStatus::SENT === $expected_status ) {
			$this->assertEqualsWithDelta( time(), $notification->get_date_notified()->getTimestamp(), 5 );
			$this->assertSame( array( array( $notification->get_id(), $product->get_id() ) ), $sent_action_args );
			$this->assertSame( 1, $cycle_state['sent_count'] );
			$this->assertSame( 0, $cycle_state['failed_count'] );
		} else {
			$this->assertEmpty( $notification->get_date_notified() );
			$this->assertSame( NotificationCancellationSource::SYSTEM, $notification->get_cancellation_source() );
			$this->assertSame( array(), $sent_action_args, 'The sent action must not fire for a failed delivery.' );
			$this->assertSame( 0, $cycle_state['sent_count'] );
			$this->assertSame( 1, $cycle_state['failed_count'] );
		}

		WC()->queue()->cancel_all( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS );
	}

	/**
	 * Data provider for test_process_batch_honours_pre_send_filter.
	 *
	 * @return array[]
	 */
	public function pre_send_filter_provider(): array {
		return array(
			'true: handled externally' => array( true, NotificationStatus::SENT ),
			'false: failed'            => array( false, NotificationStatus::CANCELLED ),
			'WP_Error: failed'         => array( new \WP_Error( 'delivery_failed', 'Provider unreachable.' ), NotificationStatus::CANCELLED ),
		);
	}

	/**
	 * @testdox A throwing `woocommerce_customer_stock_notification_sent` callback is logged and does not stop the batch.
	 */
	public function test_process_batch_survives_throwing_sent_action_callback(): void {
		add_filter( 'woocommerce_customer_stock_notification_pre_send', '__return_true' );
		add_action(
			'woocommerce_customer_stock_notification_sent',
			function () {
				throw new \RuntimeException( 'Integration exploded.' );
			}
		);

		$product = WC_Helper_Product::create_simple_product();
		$ids     = array();
		foreach ( array( 'first@test.com', 'second@test.com' ) as $email ) {
			$notification = new Notification();
			$notification->set_product_id( $product->get_id() );
			$notification->set_user_email( $email );
			$notification->set_status( NotificationStatus::ACTIVE );
			$ids[] = $notification->save();
		}

		$this->sut->process_batch( $product->get_id() );

		foreach ( $ids as $id ) {
			$this->assertSame( NotificationStatus::SENT, ( new Notification( $id ) )->get_status() );
		}
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
