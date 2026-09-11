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

	/**
	 * @testdox A second signup with a different-case email should be reported as already joined.
	 */
	public function test_signup_dedupes_case_variants(): void {
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
}
