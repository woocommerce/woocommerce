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
	 * @testdox Should NOT send the verify email when the signup is from an authenticated user session, even with double opt-in enabled site-wide.
	 */
	public function test_verify_email_not_sent_for_logged_in_user_even_when_double_opt_in_required() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );

		$product = $this->create_out_of_stock_product();
		$user_id = $this->factory->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'logged-in@example.com',
			)
		);
		// Establish the auth context — `should_require_double_opt_in()` reads
		// `is_user_logged_in()`, not the `$user_id` arg.
		wp_set_current_user( $user_id );

		$this->email_manager
			->expects( $this->never() )
			->method( 'send_verify_email' );

		$result = $this->sut->signup( $product->get_id(), $user_id, 'logged-in@example.com' );

		$this->assertInstanceOf( \Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupResult::class, $result );
		$this->assertSame( \Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService::SIGNUP_SUCCESS, $result->get_code() );
		$notification = $result->get_notification();
		$this->assertInstanceOf( Notification::class, $notification );
		$this->assertSame( NotificationStatus::ACTIVE, $notification->get_status() );

		wp_set_current_user( 0 );
	}

	/**
	 * @testdox Should still send the verify email for an anonymous account-on-signup flow even though `$user_id` becomes non-zero mid-flow.
	 *
	 * Regression guard for the auth-context check: `Config::creates_account_on_signup()`
	 * mints a user mid-flow, leaving `$user_id > 0` by the time the double-opt-in
	 * decision is made — but the request is still anonymous (`is_user_logged_in()`
	 * is false), so the signup must still go through the verify-email round-trip.
	 */
	public function test_verify_email_still_sent_for_account_on_signup_anonymous_flow() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );
		update_option( 'woocommerce_customer_stock_notifications_create_account_on_signup', 'yes' );

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

		// Anonymous: no auth context, no $user_id passed in.
		$this->sut->signup( $product->get_id(), 0, 'fresh-account@example.com' );

		delete_option( 'woocommerce_customer_stock_notifications_create_account_on_signup' );
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
