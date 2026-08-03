<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\DraftOrderTrait;
use WC_Unit_Test_Case;
use WC_Helper_Order;

/**
 * Tests for DraftOrderTrait.
 */
class DraftOrderTraitTest extends WC_Unit_Test_Case {

	/**
	 * Test class that uses the trait.
	 *
	 * @var DraftOrderTraitUser
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new DraftOrderTraitUser();
	}

	/**
	 * @testDox Zero-total pending order with matching cart hash is valid for retry.
	 */
	public function test_zero_total_pending_order_is_valid() {
		$order = WC_Helper_Order::create_order();
		$order->set_total( 0 );
		$order->set_status( 'pending' );
		$order->set_cart_hash( wc()->cart->get_cart_hash() );
		$order->save();

		$this->assertTrue( $this->sut->expose_is_valid_draft_order( $order ) );
	}

	/**
	 * @testDox Zero-total failed order with matching cart hash is valid for retry.
	 */
	public function test_zero_total_failed_order_is_valid() {
		$order = WC_Helper_Order::create_order();
		$order->set_total( 0 );
		$order->set_status( 'failed' );
		$order->set_cart_hash( wc()->cart->get_cart_hash() );
		$order->save();

		$this->assertTrue( $this->sut->expose_is_valid_draft_order( $order ) );
	}

	/**
	 * @testDox Processing order with matching cart hash is NOT valid for retry.
	 */
	public function test_processing_order_is_not_valid() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'processing' );
		$order->set_cart_hash( wc()->cart->get_cart_hash() );
		$order->save();

		$this->assertFalse( $this->sut->expose_is_valid_draft_order( $order ) );
	}

	/**
	 * @testDox Completed order with matching cart hash is NOT valid for retry.
	 */
	public function test_completed_order_is_not_valid() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->set_cart_hash( wc()->cart->get_cart_hash() );
		$order->save();

		$this->assertFalse( $this->sut->expose_is_valid_draft_order( $order ) );
	}

	/**
	 * @testDox Pending order without matching cart hash is NOT valid for retry.
	 */
	public function test_pending_order_wrong_cart_hash_is_not_valid() {
		$order = WC_Helper_Order::create_order();
		$order->set_total( 0 );
		$order->set_status( 'pending' );
		$order->set_cart_hash( 'non_matching_hash_' . uniqid() );
		$order->save();

		$this->assertFalse( $this->sut->expose_is_valid_draft_order( $order ) );
	}

	/**
	 * @testDox Checkout-draft order is always valid regardless of cart hash.
	 */
	public function test_draft_order_is_valid() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'checkout-draft' );
		$order->save();

		$this->assertTrue( $this->sut->expose_is_valid_draft_order( $order ) );
	}

	/**
	 * @testDox Non-order object is not valid.
	 */
	public function test_non_order_is_not_valid() {
		$this->assertFalse( $this->sut->expose_is_valid_draft_order( null ) );
	}
}

/**
 * Helper class to expose the protected trait method for testing.
 */
class DraftOrderTraitUser {
	use DraftOrderTrait;

	/**
	 * Expose is_valid_draft_order for testing.
	 *
	 * @param mixed $order Order object.
	 * @return bool
	 */
	public function expose_is_valid_draft_order( $order ) {
		return $this->is_valid_draft_order( $order );
	}
}
