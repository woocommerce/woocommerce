<?php

/**
 * Test WC_Order_Query
 * @package WooCommerce\Tests\Order
 */
class WC_Tests_WC_Order_Query extends WC_Unit_Test_Case {

	/**
	 * Test basic methods on a new WC_Order_Query.
	 *
	 * @since 3.1
	 */
	public function test_order_query_new() {
		$query = new WC_Order_Query();
		$this->assertEquals( '', $query->get( 'total' ) );
		$this->assertEquals( wc_get_order_types( 'view-orders' ), $query->get( 'type' ) );
	}

	/**
	 * Test querying by order properties.
	 *
	 * @since 3.1
	 */
	public function test_order_query_standard() {
		$order1 = new WC_Order();
		$order1->set_prices_include_tax( true );
		$order1->set_total( '100.00' );
		$order1->save();

		$order2 = new WC_Order();
		$order2->set_prices_include_tax( false );
		$order2->set_total( '100.00' );
		$order2->save();

		// Just get some orders.
		$query = new WC_Order_Query();
		$results = $query->get_orders();
		$this->assertEquals( 2, count( $results ) );

		// Get orders with a specific property..
		$query->set( 'prices_include_tax', 'no' );
		$results = $query->get_orders();
		$this->assertEquals( 1, count( $results ) );
		$this->assertEquals( $results[0]->get_id(), $order2->get_id() );

		// Get orders with two specific properties.
		$query->set( 'total', '100.00' );
		$results = $query->get_orders();
		$this->assertEquals( 1, count( $results ) );
		$this->assertEquals( $results[0]->get_id(), $order2->get_id() );

		// Get multiple orders that have a the same specific property.
		$query->set( 'prices_include_tax', '' );
		$results = $query->get_orders();
		$this->assertEquals( 2, count( $results ) );

		// Limit to one result.
		$query->set( 'limit', 1 );
		$results = $query->get_orders();
		$this->assertEquals( 1, count( $results ) );
	}

	/**
	 * Test querying by order date properties.
	 *
	 * @since 3.1
	 */
	public function test_order_query_date_queries() {
		$now = current_time( 'mysql', true );
		$now_stamp = strtotime( $now );
		$past = date( DATE_ISO8601, $now_stamp - DAY_IN_SECONDS );
		$future = date( DATE_ISO8601, $now_stamp + DAY_IN_SECONDS );

		$order = new WC_Order();
		$order->set_date_completed( $now_stamp );
		$order->save();

		$query = new WC_Order_Query( array(
			'date_created' => $order->get_date_created()
		) );
		$orders = $query->get_orders();
		$this->assertEquals( 1, count( $orders ) );

		$query->set( 'date_created', '>' . $future );
		$orders = $query->get_orders();
		$this->assertEquals( 0, count( $orders ) );

		$query = new WC_Order_Query( array(
			'date_created' => '<=' . $order->get_date_created()
		) );
		$orders = $query->get_orders();
		$this->assertEquals( 1, count( $orders ) );

		// Test with some metadata-stored date params.
		$query = new WC_Order_Query( array(
			'date_completed' => '>' . $past
		) );
		$orders = $query->get_orders();
		$this->assertEquals( 1, count( $orders ) );

		$query->set( 'date_completed', '>' . $future );
		$orders = $query->get_orders();
		$this->assertEquals( 0, count( $orders ) );

		$query->set( 'date_completed', $past . '...' . $future );
		$orders = $query->get_orders();
		$this->assertEquals( 1, count( $orders ) );
	}

	/**
	 * Test the query var mapping customer_id => customer_user.
	 *
	 * @since 3.1
	 */
	public function test_order_query_key_mapping() {
		$user_id = wp_insert_user( array(
			'user_login' => 'testname',
			'user_pass' => 'testpass',
			'user_email' => 'email@testmail.com'
		) );

		$order = new WC_Order();
		$order->set_customer_id( $user_id );
		$order->save();

		$query = new WC_Order_Query( array(
			'customer_id' => $user_id
		) );
		$results = $query->get_orders();

		$this->assertEquals( 1, count( $results ) );
	}
}
