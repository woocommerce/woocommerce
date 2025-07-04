<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\NotificationManagementService;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupResult;
use WC_Customer;
use WC_Helper_Product;
use WP_Error;
use WC_Helper_Customer;

/**
 * SignupServiceTests data tests.
 */
class SignupServiceTests extends \WC_Unit_Test_Case {

	/**
	 * The signup service instance.
	 *
	 * @var SignupService
	 */
	private $sut;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$eligibility_service             = new EligibilityService();
		$notification_management_service = new NotificationManagementService();
		$this->sut                       = new SignupService();
		$this->sut->init( $eligibility_service, $notification_management_service );
	}

	/**
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_signups();
		$this->disable_double_opt_in();
		$this->disable_account_creation();

		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );
	}

	/**
	 * Test the signup method.
	 */
	public function test_signup_simple_product() {
		// Test initial state.
		$this->assertInstanceOf( WP_Error::class, $this->sut->signup( 1, 0, 'test@test.com' ) );

		$this->enable_signups();

		// Test with instock product.
		$product = WC_Helper_Product::create_simple_product();
		$this->assertInstanceOf( WP_Error::class, $this->sut->signup( 1, 0, 'test@test.com' ) );

		// Test with product on backorder.
		$product->set_stock_status( 'onbackorder' );
		$product->save();
		$this->assertInstanceOf( WP_Error::class, $this->sut->signup( $product->get_id(), 0, 'test@test.com' ) );

		$product->set_stock_status( 'outofstock' );
		$product->save();

		// Test with out of stock product.
		$signup_result = $this->sut->signup( $product->get_id(), 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS, $signup_result->get_code() );
		$this->assertInstanceOf( Notification::class, $signup_result->get_notification() );
		$this->assertEquals( $product->get_id(), $signup_result->get_notification()->get_product_id() );
		$this->assertEquals( 0, $signup_result->get_notification()->get_user_id() );
		$this->assertEquals( 'test@test.com', $signup_result->get_notification()->get_user_email() );

		// Test with no email.
		$signup_result = $this->sut->signup( $product->get_id(), 0, '' );
		$this->assertInstanceOf( WP_Error::class, $signup_result );
		$this->assertEquals( SignupService::ERROR_INVALID_REQUEST, $signup_result->get_error_code() );

		// Test with no user id.
		$signup_result = $this->sut->signup( $product->get_id(), 0, 'test2@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS, $signup_result->get_code() );
		$this->assertInstanceOf( Notification::class, $signup_result->get_notification() );
		$this->assertEquals( $product->get_id(), $signup_result->get_notification()->get_product_id() );
		$this->assertEquals( 0, $signup_result->get_notification()->get_user_id() );
		$this->assertEquals( 'test2@test.com', $signup_result->get_notification()->get_user_email() );

		// Test with a user id only.
		$signup_result = $this->sut->signup( $product->get_id(), 1, '' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS, $signup_result->get_code() );
		$this->assertInstanceOf( Notification::class, $signup_result->get_notification() );
		$this->assertEquals( $product->get_id(), $signup_result->get_notification()->get_product_id() );
		$this->assertEquals( 1, $signup_result->get_notification()->get_user_id() );
		$this->assertEquals( '', $signup_result->get_notification()->get_user_email() );

		// Test already signed up.
		$signup_result = $this->sut->signup( $product->get_id(), 1, '' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED, $signup_result->get_code() );
		$this->assertInstanceOf( Notification::class, $signup_result->get_notification() );
		$this->assertEquals( $product->get_id(), $signup_result->get_notification()->get_product_id() );
		$this->assertEquals( 1, $signup_result->get_notification()->get_user_id() );
		$this->assertEquals( '', $signup_result->get_notification()->get_user_email() );
	}

	/**
	 * Test the signup method with a variable product.
	 */
	public function test_signup_variable_product() {
		$this->enable_signups();

		$product = WC_Helper_Product::create_variation_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->save();

		$signup_result = $this->sut->signup( $product->get_id(), 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS, $signup_result->get_code() );
		$this->assertInstanceOf( Notification::class, $signup_result->get_notification() );
		$this->assertEquals( $product->get_id(), $signup_result->get_notification()->get_product_id() );
		$this->assertEquals( 0, $signup_result->get_notification()->get_user_id() );
		$this->assertEquals( 'test@test.com', $signup_result->get_notification()->get_user_email() );

		// Test already signed up.
		$signup_result = $this->sut->signup( $product->get_id(), 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED, $signup_result->get_code() );
	}

	/**
	 * Test the signup method with a variation product.
	 */
	public function test_signup_variation_product() {
		$this->enable_signups();

		$product      = WC_Helper_Product::create_variation_product();
		$variation_id = $product->get_children()[0];
		$variation    = wc_get_product( $variation_id );

		$signup_result = $this->sut->signup( $variation_id, 0, 'test@test.com' );
		$this->assertInstanceOf( WP_Error::class, $signup_result );

		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 0 );
		$variation->save();

		$signup_result = $this->sut->signup( $variation_id, 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS, $signup_result->get_code() );
		$this->assertInstanceOf( Notification::class, $signup_result->get_notification() );
		$this->assertEquals( $variation_id, $signup_result->get_notification()->get_product_id() );
		$this->assertEquals( 0, $signup_result->get_notification()->get_user_id() );
		$this->assertEquals( 'test@test.com', $signup_result->get_notification()->get_user_email() );

		// Test already signed up.
		$signup_result = $this->sut->signup( $variation_id, 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED, $signup_result->get_code() );

		// Test with posted attributes.
		$signup_result = $this->sut->signup( $variation_id, 0, 'test@test.com', array( 'color' => 'red' ) );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS, $signup_result->get_code() );
		$this->assertInstanceOf( Notification::class, $signup_result->get_notification() );
		$this->assertEquals( $variation_id, $signup_result->get_notification()->get_product_id() );
		$this->assertEquals( 0, $signup_result->get_notification()->get_user_id() );
		$this->assertEquals( 'test@test.com', $signup_result->get_notification()->get_user_email() );
		$this->assertEquals( array( 'color' => 'red' ), $signup_result->get_notification()->get_meta( 'posted_attributes' ) );

		// Test already signed up with posted attributes.
		$signup_result = $this->sut->signup( $variation_id, 0, 'test@test.com', array( 'color' => 'red' ) );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED, $signup_result->get_code() );
		$this->assertEquals( array( 'color' => 'red' ), $signup_result->get_notification()->get_meta( 'posted_attributes' ) );
	}

	/**
	 * Test the is_already_signed_up method.
	 */
	public function test_is_already_signed_up() {
		$this->assertNull( $this->sut->is_already_signed_up( 1, 1, 'test@test.com' ) );

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$this->assertInstanceOf( Notification::class, $this->sut->is_already_signed_up( 1, 1, 'test@test.com' ) );

		// Test with guest user.
		$this->assertNull( $this->sut->is_already_signed_up( 1, 0, 'test2@test.com' ) );

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( 'test2@test.com' );
		$notification->save();

		$this->assertInstanceOf( Notification::class, $this->sut->is_already_signed_up( 1, 0, 'test2@test.com' ) );
	}

	/**
	 * Test the is_already_signed_up method with a variation with "any" attributes.
	 */
	public function test_is_already_signed_up_variation() {
		$this->assertNull( $this->sut->is_already_signed_up( 1, 1, 'test@test.com', array( 'color' => 'red' ) ) );

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->update_meta_data( 'posted_attributes', array( 'color' => 'red' ) );
		$notification->save();

		$this->assertInstanceOf( Notification::class, $this->sut->is_already_signed_up( 1, 1, 'test@test.com', array( 'color' => 'red' ) ) );

		// Test with guest user.
		$this->assertNull( $this->sut->is_already_signed_up( 1, 0, 'test2@test.com', array( 'color' => 'red' ) ) );

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( 'test2@test.com' );
		$notification->update_meta_data( 'posted_attributes', array( 'color' => 'red' ) );
		$notification->save();

		$this->assertInstanceOf( Notification::class, $this->sut->is_already_signed_up( 1, 0, 'test2@test.com', array( 'color' => 'red' ) ) );
	}

	/**
	 * Test signup with double opt-in.
	 */
	public function test_signup_double_opt_in() {
		$this->enable_signups();
		$this->enable_double_opt_in();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'outofstock' );
		$product->save();
		$signup_result = $this->sut->signup( $product->get_id(), 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS_DOUBLE_OPT_IN, $signup_result->get_code() );

		// Test with existing notification.
		$signup_result = $this->sut->signup( $product->get_id(), 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED_DOUBLE_OPT_IN, $signup_result->get_code() );
	}

	/**
	 * Test with account creation.
	 */
	public function test_signup_account_creation() {
		$this->enable_signups();
		$this->enable_account_creation();

		// Negative test.
		$user = get_user_by( 'email', 'test@test.com' );
		$this->assertFalse( $user );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'outofstock' );
		$product->save();
		$signup_result = $this->sut->signup( $product->get_id(), 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS_ACCOUNT_CREATED, $signup_result->get_code() );

		// Test with existing user.
		$user = get_user_by( 'email', 'test@test.com' );
		$this->assertNotNull( $user );
		$this->assertEquals( 'test@test.com', $user->user_email );

		// Test with existing notification.
		$signup_result = $this->sut->signup( $product->get_id(), $user->ID, $user->user_email );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED, $signup_result->get_code() );
	}

	/**
	 * Test with double opt-in.
	 */
	public function test_signup_double_opt_in_account_creation() {
		$this->enable_signups();
		$this->enable_double_opt_in();
		$this->enable_account_creation();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'outofstock' );
		$product->save();
		$signup_result = $this->sut->signup( $product->get_id(), 0, 'test@test.com' );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_SUCCESS_ACCOUNT_CREATED_DOUBLE_OPT_IN, $signup_result->get_code() );

		// Test with existing user.
		$user = get_user_by( 'email', 'test@test.com' );
		$this->assertNotNull( $user );
		$this->assertEquals( 'test@test.com', $user->user_email );

		// Test with existing notification.
		$signup_result = $this->sut->signup( $product->get_id(), $user->ID, $user->user_email );
		$this->assertInstanceOf( SignupResult::class, $signup_result );
		$this->assertEquals( SignupService::SIGNUP_ALREADY_JOINED_DOUBLE_OPT_IN, $signup_result->get_code() );
	}

	/**
	 * Test parse product.
	 */
	public function test_parse_product() {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( $product->get_id(), $this->get_private_method( 'parse_product' )->invoke( $this->sut, array( 'wc_bis_product_id' => $product->get_id() ) )->get_id() );

		// Test with invalid product id.
		$this->assertInstanceOf( WP_Error::class, $this->get_private_method( 'parse_product' )->invoke( $this->sut, array( 'wc_bis_product_id' => 0 ) ) );

		// Test with invalid product.
		$this->assertInstanceOf( WP_Error::class, $this->get_private_method( 'parse_product' )->invoke( $this->sut, array( 'wc_bis_product_id' => 1 ) ) );
	}

	/**
	 * Test parse user data.
	 */
	public function test_parse_user_data() {
		$this->assertEquals( array( 'user_id' => 0, 'user_email' => 'test@test.com' ), $this->get_private_method( 'parse_user_data' )->invoke( $this->sut, array( 'wc_bis_email' => 'test@test.com' ) ) );

		$customer = WC_Helper_Customer::create_customer();
		$this->assertEquals(
			array(
				'user_id'    => $customer->get_id(),
				'user_email' => $customer->get_email(),
			),
			$this->get_private_method( 'parse_user_data' )->invoke( $this->sut, array( 'wc_bis_email' => $customer->get_email() ) )
		);

		// Test with invalid email.
		$this->assertInstanceOf( WP_Error::class, $this->get_private_method( 'parse_user_data' )->invoke( $this->sut, array( 'wc_bis_email' => 'invalid' ) ) );

		// Parse logged in user.
		wp_set_current_user( $customer->get_id() );
		$this->assertEquals(
			array(
				'user_id'    => $customer->get_id(),
				'user_email' => $customer->get_email(),
			),
			$this->get_private_method( 'parse_user_data' )->invoke( $this->sut, array() )
		);
	}

	/**
	 * Test parse posted attributes.
	 */
	public function test_parse_posted_attributes() {
		$product      = WC_Helper_Product::create_variation_product();
		$variation_id = $product->get_children()[0];
		$variation    = wc_get_product( $variation_id );
		$this->assertEquals( $variation->get_sku(), 'DUMMY SKU VARIABLE SMALL' );

		$source = array(
			'attribute_pa_colour' => 'small',
			'attribute_pa_size'   => 'blue',
			'attribute_pa_number' => '0',
		);
		$attributes = $this->get_private_method( 'parse_posted_attributes' )->invoke( $this->sut, $source, $variation );
		$this->assertEquals(
			array(
				'attribute_pa_size'   => 'blue',
				'attribute_pa_number' => '0',
			),
			$attributes
		);
	}

	/**
	 * Test create customer.
	 */
	public function test_create_customer() {
		$customer = $this->get_private_method( 'create_customer' )->invoke( $this->sut, 'test@test.com' );
		$this->assertNotNull( $customer );

		// Test with existing customer.
		$user = get_user_by( 'email', 'test@test.com' );
		$this->assertEquals( $user->ID, $customer );
		$this->assertEquals( 'test@test.com', $user->user_email );

		// Test with invalid email.
		$this->assertNull( $this->get_private_method( 'create_customer' )->invoke( $this->sut, 'invalid' ) );

		// Test with invalid email.
		$this->assertNull( $this->get_private_method( 'create_customer' )->invoke( $this->sut, '' ) );
	}

	/**
	 * Enable signups.
	 */
	private function enable_signups() {
		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );
	}

	/**
	 * Disable signups.
	 */
	private function disable_signups() {
		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'no' );
	}

	private function enable_double_opt_in() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );
	}

	private function disable_double_opt_in() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );
	}

	private function enable_account_creation() {
		update_option( 'woocommerce_customer_stock_notifications_create_account_on_signup', 'yes' );
	}

	private function disable_account_creation() {
		update_option( 'woocommerce_customer_stock_notifications_create_account_on_signup', 'no' );
	}

	private function get_private_method( string $method_name ) {
		$method = new \ReflectionMethod( $this->sut, $method_name );
		$method->setAccessible( true );
		return $method;
	}
}