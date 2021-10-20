<?php
/**
 * Tests for WC_Order_Refund.
 *
 * @package WooCommerce\Tests\CRUD
 */

/**
 * Tests for WC_Order_Refund.
 */
class WC_Tests_CRUD_Refunds extends WC_Unit_Test_Case {

	/**
	 * Test: get_type
	 */
	public function test_get_type() {
		$object = new WC_Order_Refund();
		$this->assertEquals( 'shop_order_refund', $object->get_type() );
	}

	/**
	 * Test: get_refund_amount
	 */
	public function test_get_refund_amount() {
		$object = new WC_Order_Refund();
		$object->set_amount( 20 );
		$this->assertEquals( '20', $object->get_amount() );
	}

	/**
	 * Test: get_refund_reason
	 */
	public function test_get_refund_reason() {
		$object = new WC_Order_Refund();
		$object->set_reason( 'Customer is an idiot' );
		$this->assertEquals( 'Customer is an idiot', $object->get_reason() );
	}

	/**
	 * Test: get_refunded_by
	 */
	public function test_get_refunded_by() {
		$object = new WC_Order_Refund();
		$object->set_refunded_by( 1 );
		$this->assertEquals( 1, $object->get_refunded_by() );
	}

	/**
	 * Test: get_refunded_payment
	 */
	public function test_get_refunded_payment() {
		$object = new WC_Order_Refund();
		$object->set_refunded_payment( true );
		$this->assertEquals( true, $object->get_refunded_payment() );
	}
}
