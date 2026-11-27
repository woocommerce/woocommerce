<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\Customers\NewCustomerSession;
use Automattic\WooCommerce\Internal\Orders\OrderReceivedAccess;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Customer;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the order received page access rules.
 */
class OrderReceivedAccessTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderReceivedAccess
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( OrderReceivedAccess::class );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		wp_set_current_user( 0 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		WC()->session->set( 'store_api_draft_order', null );
		WC()->session->set( 'customer', null );
		delete_option( 'woocommerce_enable_guest_checkout' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Create a saved order with a billing email. The data store gives it an order key.
	 *
	 * @param int $customer_id Customer ID, or 0 for a guest order.
	 * @return WC_Order
	 */
	private function create_order( int $customer_id = 0 ): WC_Order {
		$order = new WC_Order();
		$order->set_customer_id( $customer_id );
		$order->set_billing_email( 'buyer@example.com' );
		$order->save();

		return $order;
	}

	/**
	 * Create an order placed an hour ago, after the grace period.
	 *
	 * @param int $customer_id Customer ID, or 0 for a guest order.
	 * @return WC_Order
	 */
	private function create_old_order( int $customer_id = 0 ): WC_Order {
		$order = $this->create_order( $customer_id );
		$order->set_date_created( time() - HOUR_IN_SECONDS );
		$order->save();

		return $order;
	}

	/**
	 * @testdox A wrong or empty order key gives no access, even to the owner.
	 */
	public function test_wrong_order_key_gives_no_access(): void {
		$customer = WC_Helper_Customer::create_customer();
		$order    = $this->create_order( $customer->get_id() );
		wp_set_current_user( $customer->get_id() );

		$this->assertFalse( $this->sut->current_user_can_view_order( $order, 'wrong' ) );
		$this->assertFalse( $this->sut->current_user_can_view_order( $order, '' ) );
		$this->assertFalse( $this->sut->guest_can_unlock_order( $order, '' ) );
	}

	/**
	 * @testdox A logged in owner can view their order, and other visitors must log in.
	 */
	public function test_customer_order_needs_the_owner(): void {
		$customer = WC_Helper_Customer::create_customer();
		$order    = $this->create_old_order( $customer->get_id() );
		$key      = $order->get_order_key();

		$this->assertFalse( $this->sut->current_user_can_view_order( $order, $key ), 'A guest must not view a customer order.' );
		$this->assertFalse( $this->sut->guest_can_unlock_order( $order, $key ), 'A guest must log in, not enter an email.' );

		wp_set_current_user( $customer->get_id() );
		$this->assertTrue( $this->sut->current_user_can_view_order( $order, $key ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertFalse( $this->sut->current_user_can_view_order( $order, $key ), 'Another customer must not view the order.' );
	}

	/**
	 * @testdox Turning off woocommerce_order_received_verify_known_shoppers shows customer orders with the key.
	 */
	public function test_verify_known_shoppers_filter_shows_customer_orders(): void {
		$order = $this->create_old_order( WC_Helper_Customer::create_customer()->get_id() );
		add_filter( 'woocommerce_order_received_verify_known_shoppers', '__return_false' );

		$this->assertTrue( $this->sut->current_user_can_view_order( $order, $order->get_order_key() ) );
	}

	/**
	 * @testdox A guest sees a guest order during the grace period, and must unlock it with the email address after it.
	 */
	public function test_guest_order_grace_period_then_unlock(): void {
		$new_order = $this->create_order();
		$old_order = $this->create_old_order();

		$this->assertTrue( $this->sut->current_user_can_view_order( $new_order, $new_order->get_order_key() ), 'A new order is within the grace period.' );
		$this->assertFalse( $this->sut->current_user_can_view_order( $old_order, $old_order->get_order_key() ), 'An old order needs the email address.' );
		$this->assertTrue( $this->sut->guest_can_unlock_order( $old_order, $old_order->get_order_key() ) );
	}

	/**
	 * @testdox A guest cannot unlock a guest order while guest checkout is off.
	 */
	public function test_guest_order_cannot_be_unlocked_without_guest_checkout(): void {
		$order = $this->create_old_order();
		update_option( 'woocommerce_enable_guest_checkout', 'no' );

		$this->assertFalse( $this->sut->guest_can_unlock_order( $order, $order->get_order_key() ) );
	}

	/**
	 * @testdox A guest order without a billing email shows without a check, because no email can unlock it.
	 */
	public function test_guest_order_without_billing_email_shows(): void {
		$order = $this->create_old_order();
		$order->set_billing_email( '' );
		$order->save();

		$this->assertTrue( $this->sut->current_user_can_view_order( $order, $order->get_order_key() ) );
		$this->assertFalse( $this->sut->guest_can_unlock_order( $order, $order->get_order_key() ) );
	}

	/**
	 * @testdox A guest order shows while the session still holds it as the checkout draft.
	 */
	public function test_guest_order_in_session_shows(): void {
		$order = $this->create_old_order();
		WC()->session->set( 'store_api_draft_order', $order->get_id() );

		$this->assertTrue( $this->sut->current_user_can_view_order( $order, $order->get_order_key() ) );
	}

	/**
	 * @testdox An unsaved order never matches an empty session draft.
	 */
	public function test_unsaved_order_is_not_the_session_draft(): void {
		$order = new WC_Order();
		$order->set_order_key( 'wc_order_unsaved' );
		$order->set_billing_email( 'unsaved@example.com' );
		WC()->session->set( 'store_api_draft_order', null );

		$this->assertFalse( $this->sut->current_user_can_view_order( $order, 'wc_order_unsaved' ) );
	}

	/**
	 * @testdox woocommerce_order_email_verification_grace_period can remove the grace period.
	 */
	public function test_grace_period_filter(): void {
		$order = $this->create_order();
		add_filter(
			'woocommerce_order_email_verification_grace_period',
			static function () {
				return -1;
			}
		);

		$this->assertFalse( $this->sut->current_user_can_view_order( $order, $order->get_order_key() ) );
	}

	/**
	 * @testdox The session billing email no longer unlocks a guest order.
	 */
	public function test_session_email_does_not_unlock_guest_order(): void {
		$order = $this->create_old_order();
		WC()->session->set( 'customer', array( 'email' => $order->get_billing_email() ) );

		$this->assertFalse( $this->sut->current_user_can_view_order( $order, $order->get_order_key() ) );
	}

	/**
	 * @testdox woocommerce_order_email_verification_required can remove the email step for guest orders.
	 */
	public function test_email_verification_required_filter(): void {
		$order = $this->create_old_order();
		add_filter( 'woocommerce_order_email_verification_required', '__return_false' );

		$this->assertTrue( $this->sut->current_user_can_view_order( $order, $order->get_order_key() ) );
	}

	/**
	 * @testdox A guest can unlock the order that created an unconfirmed account, in any session, until the account is confirmed.
	 */
	public function test_unconfirmed_new_account_order_can_be_unlocked(): void {
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		$new_customer_session = wc_get_container()->get( NewCustomerSession::class );
		$customer_id          = $new_customer_session->create_customer( 'unlock-new-account@example.com' );
		$order                = $this->create_old_order( $customer_id );
		$new_customer_session->mark_order_created_customer( $order, $customer_id );
		$order->save();
		WC()->session->set( 'new_customer', null );
		$key = $order->get_order_key();

		$this->assertFalse( $this->sut->current_user_can_view_order( $order, $key ), 'After the grace period, the guest must enter the email.' );
		$this->assertTrue( $this->sut->guest_can_unlock_order( $order, $key ), 'The account has no password, so the email must unlock it, even without guest checkout.' );

		wc_get_container()->get( EmailVerificationService::class )->mark_verified( $customer_id, 'unlock-new-account@example.com' );

		$this->assertFalse( $this->sut->guest_can_unlock_order( $order, $key ), 'A confirmed account has a password, so its owner must log in.' );
	}

	/**
	 * @testdox Only the billing email address unlocks the order, in any letter case.
	 */
	public function test_email_unlocks_order(): void {
		$order = $this->create_order();

		$this->assertTrue( $this->sut->email_unlocks_order( $order, strtoupper( $order->get_billing_email() ) ) );
		$this->assertFalse( $this->sut->email_unlocks_order( $order, 'someone-else@example.com' ) );
		$this->assertFalse( $this->sut->email_unlocks_order( $order, '' ) );
	}

	/**
	 * @testdox OrderUtil exposes the same rules.
	 */
	public function test_order_util_uses_the_same_rules(): void {
		$order = $this->create_old_order();
		$key   = $order->get_order_key();

		$this->assertFalse( OrderUtil::current_user_can_view_order( $order, $key ) );
		$this->assertTrue( OrderUtil::guest_can_unlock_order( $order, $key ) );
	}
}
