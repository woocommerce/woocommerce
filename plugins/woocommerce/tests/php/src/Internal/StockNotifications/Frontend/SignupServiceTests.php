<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\NotificationManagementService;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use WC_Helper_Product;

/**
 * Tests for SignupService email dispatch.
 */
class SignupServiceTests extends \WC_Unit_Test_Case {

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
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );

		$eligibility_service = new EligibilityService();
		$eligibility_service->init( new StockManagementHelper() );

		$this->email_manager = $this->createMock( EmailManager::class );

		$notification_management_service = new NotificationManagementService();
		$notification_management_service->init( $this->email_manager );

		$this->sut = new SignupService();
		$this->sut->init( $eligibility_service, $notification_management_service, $this->email_manager );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_customer_stock_notifications_allow_signups' );
		delete_option( 'woocommerce_customer_stock_notifications_require_double_opt_in' );

		// DELETE rather than TRUNCATE so the outer WP_UnitTestCase transaction can still roll back.
		// TRUNCATE is DDL and implicitly commits the surrounding transaction.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notificationmeta" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notifications" );

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
	 * @testdox Should detect an existing guest signup when the same email later signs up as a logged-in user.
	 */
	public function test_guest_signup_detected_for_logged_in_user_with_same_email() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create( array( 'user_email' => 'customer@example.com' ) );

		$guest_result = $this->sut->signup( $product->get_id(), 0, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $guest_result->get_code() );

		$user_result = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_ALREADY_JOINED, $user_result->get_code() );
		$this->assertSame( $guest_result->get_notification()->get_id(), $user_result->get_notification()->get_id() );

		$found = $this->sut->is_already_signed_up( $product->get_id(), $user_id, 'customer@example.com' );
		$this->assertInstanceOf( Notification::class, $found );
		$this->assertSame( $guest_result->get_notification()->get_id(), $found->get_id() );
	}

	/**
	 * @testdox Should detect an existing logged-in signup when the same email later signs up as a guest.
	 */
	public function test_logged_in_signup_detected_for_guest_with_same_email() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create( array( 'user_email' => 'customer@example.com' ) );

		$user_result = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $user_result->get_code() );

		$guest_result = $this->sut->signup( $product->get_id(), 0, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_ALREADY_JOINED, $guest_result->get_code() );
		$this->assertSame( $user_result->get_notification()->get_id(), $guest_result->get_notification()->get_id() );
	}

	/**
	 * @testdox Should not treat a different email as a duplicate when the user ID has no signup.
	 */
	public function test_no_false_duplicate_for_different_email() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create( array( 'user_email' => 'customer@example.com' ) );

		$this->sut->signup( $product->get_id(), 0, 'guest@example.com' );

		$this->assertNull( $this->sut->is_already_signed_up( $product->get_id(), $user_id, 'customer@example.com' ) );
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
