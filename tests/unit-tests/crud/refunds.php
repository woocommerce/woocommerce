<?php

/**
 * Meta
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Refunds extends WC_Unit_Test_Case {

	/**
	 * Test: get_type
	 */
	function test_get_type() {
		$object = new WC_Order_Refund();
		$this->assertEquals( 'shop_order_refund', $object->get_type() );
	}

	/**
	 * Test: get_refund_amount
	 */
	function test_get_refund_amount() {
		$object = new WC_Order_Refund();
		$object->set_amount( 20 );
		$this->assertEquals( '20.00', $object->get_amount() );
	}

	/**
	 * Test: get_refund_reason
	 */
	function test_get_refund_reason() {
		$object = new WC_Order_Refund();
		$object->set_reason( 'Customer is an idiot' );
		$this->assertEquals( 'Customer is an idiot', $object->get_reason() );
	}

	/**
	 * Test: get_refunded_by
	 */
	function test_get_refunded_by() {
		$object = new WC_Order_Refund();
		$object->set_refunded_by( 1 );
		$this->assertEquals( 1, $object->get_refunded_by() );
	}
}
