<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\NotificationManagementService;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupRateLimiter;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Helper_Product;

/**
 * Tests for SignupService email dispatch.
 */
class SignupServiceTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * The System Under Test.
	 *
	 * @var SignupService
	 */
	private $sut;

	/**
	 * Mock email manager.
	 *
	 * @var EmailManager&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $email_manager;

	/**
	 * The remote address seen before the test replaced it.
	 *
	 * @var string|null
	 */
	private $original_remote_addr;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();

		$this->original_remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null;
		$_SERVER['REMOTE_ADDR']     = '192.0.2.10';

		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );

		$eligibility_service = new EligibilityService();
		$eligibility_service->init( new StockManagementHelper() );

		$this->email_manager = $this->createMock( EmailManager::class );

		$notification_management_service = new NotificationManagementService();
		$notification_management_service->init( $this->email_manager );

		$this->sut = new SignupService();
		$this->sut->init( $eligibility_service, $notification_management_service, $this->email_manager, new SignupRateLimiter() );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( null === $this->original_remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->original_remote_addr;
		}

		delete_option( 'woocommerce_customer_stock_notifications_allow_signups' );
		delete_option( 'woocommerce_customer_stock_notifications_require_double_opt_in' );

		// DELETE rather than TRUNCATE so the outer WP_UnitTestCase transaction can still roll back.
		// TRUNCATE is DDL and implicitly commits the surrounding transaction.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notificationmeta" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notifications" );

		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * @testdox Should send the verify email when double opt-in is required and a new pending notification is created.
	 */
	public function test_verify_email_sent_when_double_opt_in_required() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );

		$product = $this->create_out_of_stock_product();

		$this->email_manager
			->expects( $this->once() )
			->method( 'send_verify_email' )
			->with(
				$this->callback(
					static function ( $arg ) {
						return $arg instanceof Notification
							&& NotificationStatus::PENDING === $arg->get_status();
					}
				)
			);

		$this->sut->signup( $product->get_id(), 0, 'guest@example.com' );
	}

	/**
	 * @testdox Should not send the verify email when double opt-in is disabled.
	 */
	public function test_verify_email_not_sent_when_double_opt_in_disabled() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();

		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verify_email' );

		$this->sut->signup( $product->get_id(), 0, 'guest@example.com' );
	}

	/**
	 * @testdox Should reject a second signup made within the rate limit window.
	 */
	public function test_second_signup_is_rate_limited() {
		$product       = $this->create_out_of_stock_product();
		$other_product = $this->create_out_of_stock_product();

		$this->sut->signup( $product->get_id(), 0, 'guest@example.com' );
		$result = $this->sut->signup( $other_product->get_id(), 0, 'guest@example.com' );

		$this->assertWPError( $result, 'A signup within the rate limit window should fail' );
		$this->assertEquals( SignupService::ERROR_RATE_LIMITED, $result->get_error_code(), 'The failure should be reported as rate limited' );
	}

	/**
	 * @testdox Should not create a notification or send an email for a rate limited signup.
	 */
	public function test_rate_limited_signup_creates_nothing() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );

		$product       = $this->create_out_of_stock_product();
		$other_product = $this->create_out_of_stock_product();

		$this->sut->signup( $product->get_id(), 0, 'guest@example.com' );

		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verify_email' );

		$result = $this->sut->signup( $other_product->get_id(), 0, 'guest@example.com' );

		$this->assertWPError( $result, 'A signup within the rate limit window should fail' );
		$this->assertNull( $this->sut->is_already_signed_up( $other_product->get_id(), 0, 'guest@example.com' ), 'A rate limited signup should not have created a notification' );
	}

	/**
	 * @testdox Should rate limit a logged-in customer on the account email address.
	 */
	public function test_logged_in_signup_is_rate_limited_on_the_account_email() {
		add_filter(
			'woocommerce_customer_stock_notifications_signup_rate_limit_options',
			static function () {
				return array(
					'client_delay' => 0,
					'email_delay'  => 600,
				);
			}
		);

		$user_id       = wp_insert_user(
			array(
				'user_login' => 'stock_notifications_shopper',
				'user_pass'  => wp_generate_password(),
				'user_email' => 'shopper@example.com',
			)
		);
		$product       = $this->create_out_of_stock_product();
		$other_product = $this->create_out_of_stock_product();

		$this->sut->signup( $product->get_id(), $user_id, 'shopper@example.com' );
		$result = $this->sut->signup( $other_product->get_id(), $user_id, 'shopper@example.com' );

		$this->assertWPError( $result, 'A second signup from the same account should fail' );
		$this->assertEquals( SignupService::ERROR_RATE_LIMITED, $result->get_error_code(), 'The failure should be reported as rate limited' );
	}

	/**
	 * @testdox Should not consume the rate limit window when the customer had already joined the waitlist.
	 */
	public function test_already_joined_does_not_consume_the_rate_limit_window() {
		$product       = $this->create_out_of_stock_product();
		$other_product = $this->create_out_of_stock_product();

		$notification = new Notification();
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'guest@example.com' );
		$notification->save();

		$already_joined = $this->sut->signup( $product->get_id(), 0, 'guest@example.com' );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED, $already_joined->get_code(), 'The signup should report that the waitlist was already joined' );

		$result = $this->sut->signup( $other_product->get_id(), 0, 'guest@example.com' );

		$this->assertNotWPError( $result, 'An attempt that only found an existing signup should not consume the rate limit window' );
	}

	/**
	 * @testdox Should not consume the rate limit window when a pending double opt-in signup is already awaiting confirmation.
	 */
	public function test_pending_double_opt_in_signup_does_not_consume_the_rate_limit_window() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );

		$product       = $this->create_out_of_stock_product();
		$other_product = $this->create_out_of_stock_product();

		$notification = new Notification();
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'guest@example.com' );
		$notification->save();

		$result = $this->sut->signup( $product->get_id(), 0, 'guest@example.com' );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED_DOUBLE_OPT_IN, $result->get_code(), 'The signup should report that the waitlist was already joined pending confirmation' );

		$second = $this->sut->signup( $other_product->get_id(), 0, 'guest@example.com' );
		$this->assertNotWPError( $second, 'A pending double opt-in signup should not consume the rate limit window' );
	}

	/**
	 * @testdox Should activate a pending notification without consuming the rate limit window when double opt-in is disabled.
	 */
	public function test_pending_signup_is_activated_and_does_not_consume_the_rate_limit_window_when_double_opt_in_disabled() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product       = $this->create_out_of_stock_product();
		$other_product = $this->create_out_of_stock_product();

		$notification = new Notification();
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'guest@example.com' );
		$notification->save();

		$signup_fired_count = 0;
		add_action(
			'woocommerce_customer_stock_notifications_signup',
			static function () use ( &$signup_fired_count ) {
				++$signup_fired_count;
			}
		);

		$result = $this->sut->signup( $product->get_id(), 0, 'guest@example.com' );
		$this->assertNotWPError( $result, 'Activating an existing pending signup should succeed' );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS, $result->get_code(), 'The signup should report success' );
		$this->assertEquals( 1, $signup_fired_count, 'The signup action should have fired exactly once' );

		$reloaded = $this->sut->is_already_signed_up( $product->get_id(), 0, 'guest@example.com' );
		$this->assertInstanceOf( Notification::class, $reloaded, 'The notification should still exist' );
		$this->assertEquals( NotificationStatus::ACTIVE, $reloaded->get_status(), 'The notification should now be active' );

		$second = $this->sut->signup( $other_product->get_id(), 0, 'guest@example.com' );
		$this->assertNotWPError( $second, 'Activating an existing pending signup should not consume the rate limit window' );
	}

	/**
	 * Create an out-of-stock simple product for signup.
	 *
	 * @return \WC_Product_Simple
	 */
	private function create_out_of_stock_product(): \WC_Product_Simple {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'outofstock' );
		$product->save();

		return $product;
	}
}
