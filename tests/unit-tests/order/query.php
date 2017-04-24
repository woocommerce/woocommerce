<?php

/**
 * Test WC_Order_Query
 * @package WooCommerce\Tests\Order
 */
class WC_Tests_WC_Order_Query extends WC_Unit_Test_Case {

	public function test_new_order_query() {
		$query = new WC_Order_Query();
		$this->assertEquals( '', $query->get( 'total' ) );
		$this->assertEquals( wc_get_order_types( 'view-orders' ), $query->get( 'type' ) );
	}

}
