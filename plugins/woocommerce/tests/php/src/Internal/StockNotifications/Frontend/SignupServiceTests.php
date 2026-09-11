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
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();

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
	 * @testdox Should detect an existing guest signup when the same email later signs up as a logged-in user.
	 */
	public function test_guest_signup_detected_for_logged_in_user_with_same_email() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create( array( 'user_email' => 'customer@example.com' ) );

		$guest_result = $this->sut->signup( $product->get_id(), 0, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $guest_result->get_code() );
		$this->assertSame( 0, $guest_result->get_notification()->get_user_id() );

		$user_result = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_ALREADY_JOINED, $user_result->get_code() );
		$this->assertSame( $guest_result->get_notification()->get_id(), $user_result->get_notification()->get_id() );

		$found = $this->sut->is_already_signed_up( $product->get_id(), $user_id, 'customer@example.com' );
		$this->assertInstanceOf( Notification::class, $found );
		$this->assertSame( $guest_result->get_notification()->get_id(), $found->get_id() );
	}

	/**
	 * @testdox Should detect an existing pending guest signup when the same email later signs up as a logged-in user with double opt-in enabled.
	 */
	public function test_pending_guest_signup_detected_for_logged_in_user_with_double_opt_in() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create( array( 'user_email' => 'customer@example.com' ) );

		$guest_result = $this->sut->signup( $product->get_id(), 0, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_SUCCESS_DOUBLE_OPT_IN, $guest_result->get_code() );
		$this->assertSame( NotificationStatus::PENDING, $guest_result->get_notification()->get_status() );

		$user_result = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_ALREADY_JOINED_DOUBLE_OPT_IN, $user_result->get_code() );
		$this->assertSame( $guest_result->get_notification()->get_id(), $user_result->get_notification()->get_id() );
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
	 * @testdox Should detect an existing guest signup when the same email later signs up as a logged-in user with the same attributes.
	 */
	public function test_guest_signup_detected_for_logged_in_user_with_same_attributes() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create( array( 'user_email' => 'customer@example.com' ) );

		$guest_result = $this->sut->signup( $product->get_id(), 0, 'customer@example.com', array( 'attribute_pa_color' => 'blue' ) );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $guest_result->get_code() );
		$this->assertSame( 0, $guest_result->get_notification()->get_user_id() );

		$user_result = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com', array( 'attribute_pa_color' => 'blue' ) );
		$this->assertSame( SignupService::SIGNUP_ALREADY_JOINED, $user_result->get_code() );
		$this->assertSame( $guest_result->get_notification()->get_id(), $user_result->get_notification()->get_id() );
	}

	/**
	 * @testdox Should allow a second signup for the same variation with different posted attributes.
	 */
	public function test_different_posted_attributes_are_not_a_duplicate() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create( array( 'user_email' => 'customer@example.com' ) );

		$guest_result = $this->sut->signup( $product->get_id(), 0, 'customer@example.com', array( 'attribute_pa_color' => 'blue' ) );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $guest_result->get_code() );
		$this->assertSame( 0, $guest_result->get_notification()->get_user_id() );

		$user_result = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com', array( 'attribute_pa_color' => 'red' ) );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $user_result->get_code() );
		$this->assertNotSame( $guest_result->get_notification()->get_id(), $user_result->get_notification()->get_id() );

		$found = $this->sut->is_already_signed_up( $product->get_id(), $user_id, 'customer@example.com', array( 'attribute_pa_color' => 'blue' ) );
		$this->assertInstanceOf( Notification::class, $found );
		$this->assertSame( $guest_result->get_notification()->get_id(), $found->get_id() );
	}

	/**
	 * @testdox Should not let a cancelled notification hide a later active one.
	 */
	public function test_cancelled_notification_does_not_hide_active_one() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create( array( 'user_email' => 'customer@example.com' ) );

		$first = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com' )->get_notification();
		$first->set_status( NotificationStatus::CANCELLED );
		$first->save();

		$second = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $second->get_code() );
		$this->assertNotSame( $first->get_id(), $second->get_notification()->get_id() );

		// The cancelled notification is older, so it must not be the one the third signup finds.
		$third = $this->sut->signup( $product->get_id(), $user_id, 'customer@example.com' );
		$this->assertSame( SignupService::SIGNUP_ALREADY_JOINED, $third->get_code() );
		$this->assertSame( $second->get_notification()->get_id(), $third->get_notification()->get_id() );
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
