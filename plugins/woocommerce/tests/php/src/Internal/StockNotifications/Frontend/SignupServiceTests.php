<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Enums\ProductStockStatus;
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
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;

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
		$this->sut->init( $eligibility_service, $notification_management_service, $this->email_manager );
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
		delete_option( 'woocommerce_customer_stock_notifications_create_account_on_signup' );

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
		$this->disable_signup_rate_limiting();
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
		$this->disable_signup_rate_limiting();
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
		$this->disable_signup_rate_limiting();
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
		$this->disable_signup_rate_limiting();
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
		$this->disable_signup_rate_limiting();
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
		$this->disable_signup_rate_limiting();
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
	 * @testdox Should let the signup through when the rate limit window cannot be claimed.
	 */
	public function test_signup_proceeds_when_the_rate_limit_cannot_be_claimed(): void {
		global $wpdb;

		$product = $this->create_out_of_stock_product();

		// Break the rate limit write the same way SignupRateLimiterTests does, so apply() fails
		// for a reason the shopper cannot fix and signup() has to decide whether to fail open.
		$suppress = $wpdb->suppress_errors( true );
		$filter   = static function ( $query ) {
			if ( false !== strpos( $query, 'stock_notifications_signup_email_' ) ) {
				return 'SELECT 1 FROM a_table_that_does_not_exist';
			}

			return $query;
		};

		add_filter( 'query', $filter );

		try {
			$result = $this->sut->signup( $product->get_id(), 0, 'guest@example.com' );
		} finally {
			remove_filter( 'query', $filter );
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertNotWPError( $result, 'A signup should not fail because the rate limit window could not be claimed' );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS, $result->get_code(), 'The signup should report success' );
		$this->assertInstanceOf( Notification::class, $this->sut->is_already_signed_up( $product->get_id(), 0, 'guest@example.com' ), 'The notification should have been created' );
		$this->assertFalse( SignupRateLimiter::is_rate_limited( 'guest@example.com' ), 'No partial rate limit window should be left behind' );
	}

	/**
	 * Switch the sign-up rate limiter off, for tests that legitimately create two sign-ups in a row.
	 */
	private function disable_signup_rate_limiting(): void {
		add_filter(
			'woocommerce_customer_stock_notifications_signup_rate_limit_options',
			static function ( $options ) {
				$options['enabled'] = false;
				return $options;
			}
		);
	}

	/**
	 * Create an out-of-stock simple product for signup.
	 *
	 * @return \WC_Product_Simple
	 */
	private function create_out_of_stock_product(): \WC_Product_Simple {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		return $product;
	}

	/**
	 * @testdox A second signup with a different-case email should be reported as already joined.
	 */
	public function test_signup_dedupes_case_variants(): void {
		$this->disable_signup_rate_limiting();
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();

		$first = $this->sut->signup( $product->get_id(), 0, 'guest@example.com' );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $first->get_code() );
		$this->assertSame( 'guest@example.com', $first->get_notification()->get_user_email() );

		$second = $this->sut->signup( $product->get_id(), 0, ' Guest@Example.COM ' );
		$this->assertSame( SignupService::SIGNUP_ALREADY_JOINED, $second->get_code() );
		$this->assertSame( $first->get_notification()->get_id(), $second->get_notification()->get_id() );
	}

	/**
	 * @testdox parse() should return a lowercase email for mixed-case guest input.
	 */
	public function test_parse_normalizes_guest_email(): void {
		$product = $this->create_out_of_stock_product();

		$data = $this->sut->parse(
			array(
				'wc_bis_product_id' => $product->get_id(),
				'wc_bis_email'      => ' Guest@Example.COM ',
			)
		);

		$this->assertIsArray( $data );
		$this->assertSame( 'guest@example.com', $data['user_email'] );
	}

	/**
	 * @testdox parse() should leave a guest sign-up unlinked even when an account with that email exists.
	 */
	public function test_parse_does_not_link_guest_to_existing_account(): void {
		$product = $this->create_out_of_stock_product();
		$this->factory()->user->create( array( 'user_email' => 'existing@example.com' ) );

		$data = $this->sut->parse(
			array(
				'wc_bis_product_id' => $product->get_id(),
				'wc_bis_email'      => 'existing@example.com',
			)
		);

		$this->assertIsArray( $data );
		$this->assertSame( 0, $data['user_id'] );
		$this->assertSame( 'existing@example.com', $data['user_email'] );
	}

	/**
	 * @testdox A guest signup should never create an account, even with the legacy option enabled.
	 */
	public function test_guest_signup_does_not_create_account(): void {
		update_option( 'woocommerce_customer_stock_notifications_create_account_on_signup', 'yes' );
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );

		$product = $this->create_out_of_stock_product();

		$result = $this->sut->signup( $product->get_id(), 0, 'newguest@example.com' );

		$this->assertSame( SignupService::SIGNUP_SUCCESS, $result->get_code() );
		$this->assertSame( 0, $result->get_notification()->get_user_id() );
		$this->assertFalse( get_user_by( 'email', 'newguest@example.com' ) );
	}

	/**
	 * @testdox Deprecated account-creation codes should still map to a message.
	 */
	public function test_deprecated_account_created_codes_still_resolve(): void {
		$product      = $this->create_out_of_stock_product();
		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );

		$this->assertStringContainsString( 'a new account has been created', $this->sut->get_signup_user_message( 'success_account_created', $notification ) );
		$this->assertStringContainsString( 'An account has been created', $this->sut->get_signup_user_message( 'success_account_created_double_opt_in', $notification ) );
		$this->assertStringContainsString( 'consent to the creation of a new account', $this->sut->get_error_message( 'invalid_opt_in' ) );
	}

	/**
	 * @testdox parse() should reject a guest email that is not a valid address.
	 *
	 * @testWith ["not an email"]
	 *           [["guest@example.com"]]
	 *           [42]
	 *
	 * @param mixed $posted_email The submitted email value.
	 */
	public function test_parse_rejects_invalid_guest_email( $posted_email ): void {
		$product = $this->create_out_of_stock_product();

		$data = $this->sut->parse(
			array(
				'wc_bis_product_id' => $product->get_id(),
				'wc_bis_email'      => $posted_email,
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $data );
		$this->assertSame( SignupService::ERROR_INVALID_EMAIL, $data->get_error_code() );
	}

	/**
	 * @testdox parse() should reject a posted variation attribute value the store does not offer.
	 */
	public function test_parse_rejects_value_not_in_attribute_options(): void {
		$variation = $this->create_out_of_stock_variation_with_any_attribute();

		$data = $this->sut->parse(
			array(
				'wc_bis_product_id' => $variation->get_id(),
				'wc_bis_email'      => 'guest@example.com',
				'attribute_finish'  => 'chrome',
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $data );
		$this->assertSame( SignupService::ERROR_INVALID_REQUEST, $data->get_error_code() );
	}

	/**
	 * @testdox parse() should reject a posted variation attribute value over 255 characters.
	 */
	public function test_parse_rejects_oversized_attribute_value(): void {
		$value     = str_repeat( 'a', 256 );
		$variation = $this->create_out_of_stock_variation_with_any_attribute( array( $value ) );

		$data = $this->sut->parse(
			array(
				'wc_bis_product_id' => $variation->get_id(),
				'wc_bis_email'      => 'guest@example.com',
				'attribute_finish'  => $value,
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $data );
		$this->assertSame( SignupService::ERROR_INVALID_REQUEST, $data->get_error_code() );
	}

	/**
	 * @testdox parse() should accept a posted variation attribute value at the 255 character limit.
	 */
	public function test_parse_accepts_attribute_value_at_the_limit(): void {
		$value     = str_repeat( 'a', 255 );
		$variation = $this->create_out_of_stock_variation_with_any_attribute( array( $value ) );

		$data = $this->sut->parse(
			array(
				'wc_bis_product_id' => $variation->get_id(),
				'wc_bis_email'      => 'guest@example.com',
				'attribute_finish'  => $value,
			)
		);

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'posted_attributes', $data );
		$this->assertSame( $value, $data['posted_attributes']['attribute_finish'] );
	}

	/**
	 * @testdox Should block an already-joined product by an existing rate limit window rather than report already-joined.
	 */
	public function test_already_joined_is_blocked_by_an_existing_rate_limit_window(): void {
		$email     = 'guest@example.com';
		$product_a = $this->create_out_of_stock_product();
		$product_b = $this->create_out_of_stock_product();

		$notification = new Notification();
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_product_id( $product_a->get_id() );
		$notification->set_user_email( $email );
		$notification->save();

		$claims_window = $this->sut->signup( $product_b->get_id(), 0, $email );
		$this->assertSame( SignupService::SIGNUP_SUCCESS, $claims_window->get_code(), 'Signing up for a different product should succeed and claim the rate limit window' );

		$result = $this->sut->signup( $product_a->get_id(), 0, $email );
		$this->assertWPError( $result, 'A request within the rate limit window should fail even for a product the customer already joined' );
		$this->assertSame( SignupService::ERROR_RATE_LIMITED, $result->get_error_code(), 'The rate limit should be reported before the duplicate lookup runs' );
	}

	/**
	 * @testdox parse() should ignore a posted value for an attribute the variation fixes.
	 */
	public function test_parse_ignores_posted_values_for_fixed_attributes(): void {
		$variation = $this->create_out_of_stock_variation_with_fixed_and_any_attributes();

		$data = $this->sut->parse(
			array(
				'wc_bis_product_id' => $variation->get_id(),
				'wc_bis_email'      => 'guest@example.com',
				'attribute_color'   => 'blue',
				'attribute_finish'  => 'gloss',
			)
		);

		$this->assertIsArray( $data );
		$this->assertSame( array( 'attribute_finish' => 'gloss' ), $data['posted_attributes'], 'The variation ID already identifies the attributes it fixes, so a posted value for one should be dropped' );
	}

	/**
	 * @testdox Should find an existing sign-up stored with the same posted attributes.
	 */
	public function test_lookup_matches_the_same_posted_attributes(): void {
		$product = $this->create_out_of_stock_product();
		$email   = 'guest@example.com';

		$stored = $this->create_active_notification_with_attributes( $product->get_id(), $email, array( 'attribute_size' => 'large' ) );

		$found = $this->sut->is_already_signed_up( $product->get_id(), 0, $email, array( 'attribute_size' => 'large' ) );

		$this->assertInstanceOf( Notification::class, $found );
		$this->assertSame( $stored->get_id(), $found->get_id() );
	}

	/**
	 * @testdox Should not find a sign-up stored with different posted attributes.
	 */
	public function test_lookup_does_not_match_different_posted_attributes(): void {
		$product = $this->create_out_of_stock_product();
		$email   = 'guest@example.com';

		$this->create_active_notification_with_attributes( $product->get_id(), $email, array( 'attribute_size' => 'large' ) );

		$this->assertNull( $this->sut->is_already_signed_up( $product->get_id(), 0, $email, array( 'attribute_size' => 'small' ) ), 'A different value should not match' );
		$this->assertNull(
			$this->sut->is_already_signed_up(
				$product->get_id(),
				0,
				$email,
				array(
					'attribute_size'  => 'large',
					'attribute_color' => 'blue',
				)
			),
			'A superset of the stored attributes should not match'
		);
	}

	/**
	 * @testdox Should match any sign-up for the identity when no attributes are posted.
	 */
	public function test_lookup_without_posted_attributes_matches_any_signup(): void {
		$product = $this->create_out_of_stock_product();
		$email   = 'guest@example.com';

		$stored = $this->create_active_notification_with_attributes( $product->get_id(), $email, array( 'attribute_size' => 'large' ) );

		$found = $this->sut->is_already_signed_up( $product->get_id(), 0, $email );

		$this->assertInstanceOf( Notification::class, $found );
		$this->assertSame( $stored->get_id(), $found->get_id() );
	}

	/**
	 * @testdox Should match posted attributes case sensitively.
	 */
	public function test_lookup_matches_posted_attributes_case_sensitively(): void {
		$product = $this->create_out_of_stock_product();
		$email   = 'guest@example.com';

		$this->create_active_notification_with_attributes( $product->get_id(), $email, array( 'attribute_size' => 'Large' ) );

		$this->assertNull( $this->sut->is_already_signed_up( $product->get_id(), 0, $email, array( 'attribute_size' => 'large' ) ), 'A value differing only in case should not match' );
		$this->assertInstanceOf( Notification::class, $this->sut->is_already_signed_up( $product->get_id(), 0, $email, array( 'attribute_size' => 'Large' ) ) );
	}

	/**
	 * Create a variable product with one fixed and one "any" attribute, and an out-of-stock variation for it.
	 *
	 * @return WC_Product_Variation
	 */
	private function create_out_of_stock_variation_with_fixed_and_any_attributes(): WC_Product_Variation {
		$color = new WC_Product_Attribute();
		$color->set_id( 0 );
		$color->set_name( 'color' );
		$color->set_options( array( 'red', 'blue' ) );
		$color->set_visible( true );
		$color->set_variation( true );

		$finish = new WC_Product_Attribute();
		$finish->set_id( 0 );
		$finish->set_name( 'finish' );
		$finish->set_options( array( 'gloss', 'matte' ) );
		$finish->set_visible( true );
		$finish->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Variable Product' );
		$product->set_attributes( array( $color, $finish ) );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes(
			array(
				'color'  => 'red',
				'finish' => '',
			)
		);
		$variation->set_regular_price( '10' );
		$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$variation->save();

		return $variation;
	}

	/**
	 * Create one active notification with the given posted attributes.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $email The user email.
	 * @param array  $posted_attributes The posted attributes to store.
	 * @return Notification
	 */
	private function create_active_notification_with_attributes( int $product_id, string $email, array $posted_attributes ): Notification {
		$notification = new Notification();
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_product_id( $product_id );
		$notification->set_user_email( $email );
		$notification->update_meta_data( 'posted_attributes', $posted_attributes );
		$notification->save();

		return $notification;
	}

	/**
	 * Create a variable product with a custom "any" attribute, and one out-of-stock variation for it.
	 *
	 * @param array $extra_options Additional values to declare for the attribute.
	 * @return WC_Product_Variation
	 */
	private function create_out_of_stock_variation_with_any_attribute( array $extra_options = array() ): WC_Product_Variation {
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'finish' );
		$attribute->set_options( array_merge( array( 'gloss', 'matte' ), $extra_options ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Variable Product' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'finish' => '' ) );
		$variation->set_regular_price( '10' );
		$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$variation->save();

		return $variation;
	}
}
