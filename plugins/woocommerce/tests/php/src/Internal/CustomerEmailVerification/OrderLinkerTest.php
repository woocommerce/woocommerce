<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\OrderLinker;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderLinker class.
 */
class OrderLinkerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderLinker
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// Resolve from the container to guarantee the constructor ran and hooks are registered.
		$this->sut = wc_get_container()->get( OrderLinker::class );
	}

	/**
	 * @testdox Verifying a customer should link their matching guest orders to their account.
	 */
	public function test_verifying_links_matching_guest_orders(): void {
		$user_id = wc_create_new_customer( 'order-link-test@example.com', 'orderlinkuser', 'pw' );
		$user    = get_user_by( 'id', $user_id );

		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $user->user_email );
		$order->set_customer_id( 0 );
		$order->save();
		$order_id = $order->get_id();

		wc_get_container()->get( EmailVerificationService::class )->mark_verified( $user_id );

		$linked_order = wc_get_order( $order_id );
		$this->assertSame( $user_id, $linked_order->get_customer_id(), 'Matching guest order should be linked to the verified customer' );
	}

	/**
	 * @testdox Guest orders with a different email should not be linked when a customer verifies.
	 */
	public function test_guest_orders_for_other_emails_are_not_linked(): void {
		$user_id = wc_create_new_customer( 'other-link-test@example.com', 'otherlinkuser', 'pw' );

		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( 'completely-different@example.com' );
		$order->set_customer_id( 0 );
		$order->save();
		$order_id = $order->get_id();

		wc_get_container()->get( EmailVerificationService::class )->mark_verified( $user_id );

		$unlinked_order = wc_get_order( $order_id );
		$this->assertSame( 0, $unlinked_order->get_customer_id(), 'Orders with a different billing email should remain unlinked' );
	}

	/**
	 * @testdox has_linkable_orders is true when a matching guest order exists, false otherwise.
	 */
	public function test_has_linkable_orders_detects_matching_guest_orders(): void {
		$email   = 'has-linkable@example.com';
		$user_id = wc_create_new_customer( $email, 'haslinkableuser', 'pw' );

		$this->assertFalse( $this->sut->has_linkable_orders( $user_id ), 'No guest orders yet — should be false' );

		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->set_customer_id( 0 );
		$order->save();

		$this->assertTrue( $this->sut->has_linkable_orders( $user_id ), 'A matching guest order exists — should be true' );
	}

	/**
	 * @testdox has_linkable_orders is false when guest orders exist only for a different email.
	 */
	public function test_has_linkable_orders_ignores_other_emails(): void {
		$user_id = wc_create_new_customer( 'no-linkable@example.com', 'nolinkableuser', 'pw' );

		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( 'someone-else@example.com' );
		$order->set_customer_id( 0 );
		$order->save();

		$this->assertFalse( $this->sut->has_linkable_orders( $user_id ), 'Guest orders for a different email should not count' );
	}
}
