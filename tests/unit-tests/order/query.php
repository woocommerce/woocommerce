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

	public function test_standard_order_query() {
		$order1 = new WC_Order();
		$order1->set_prices_include_tax( true );
		$order1->set_total( '100.00' );
		$order1->save();

		$order2 = new WC_Order();
		$order2->set_prices_include_tax( false );
		$order2->set_total( '100.00' );
		$order2->save();

		$query = new WC_Order_Query();

		$results = $query->get_orders();
		$this->assertEquals( 2, count( $results ) );

		$query->set( 'prices_include_tax', 'no' );
		$results = $query->get_orders();

		$this->assertEquals( 1, count( $results ) );
		$this->assertEquals( $results[0]->get_id(), $order2->get_id() );

		$query->set( 'prices_include_tax', '' );
		$query->set( 'total', '100.00' );
		$results = $query->get_orders();
		$this->assertEquals( 2, count( $results ) );
	}
}
