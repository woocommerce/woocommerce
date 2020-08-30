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
		$query   = new WC_Order_Query();
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
		$now          = current_time( 'mysql', true );
		$now_stamp    = strtotime( $now );
		$now_date     = date( 'Y-m-d', $now_stamp );
		$past_stamp   = $now_stamp - DAY_IN_SECONDS;
		$past         = date( 'Y-m-d', $past_stamp );
		$future_stamp = $now_stamp + DAY_IN_SECONDS;
		$future       = date( 'Y-m-d', $future_stamp );

		$order = new WC_Order();
		$order->set_date_completed( $now_stamp );
		$order->save();

		// Check WC_DateTime support.
		$query  = new WC_Order_Query(
			array(
				'date_created' => $order->get_date_created(),
			)
		);
		$orders = $query->get_orders();
		$this->assertEquals( 1, count( $orders ) );

		// Check date support.
		$query = new WC_Order_Query(
			array(
				'date_created' => $now_date,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_created', $past );
		$this->assertEquals( 0, count( $query->get_orders() ) );

		// Check timestamp support.
		$query = new WC_Order_Query(
			array(
				'date_created' => $order->get_date_created()->getTimestamp(),
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_created', $future_stamp );
		$this->assertEquals( 0, count( $query->get_orders() ) );

		// Check comparison support.
		$query = new WC_Order_Query(
			array(
				'date_created' => '>' . $past,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_created', '<' . $past );
		$this->assertEquals( 0, count( $query->get_orders() ) );
		$query->set( 'date_created', '>=' . $now_date );
		$this->assertEquals( 1, count( $query->get_orders() ) );

		// Check timestamp comparison support.
		$query = new WC_Order_Query(
			array(
				'date_created' => '<' . $future_stamp,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_created', '<' . $past_stamp );
		$this->assertEquals( 0, count( $query->get_orders() ) );
		$query->set( 'date_created', '>=' . $now_stamp );

		// Check date range support.
		$query = new WC_Order_Query(
			array(
				'date_created' => $past . '...' . $future,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_created', $past . '...' . $now_date );
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_created', $future . '...' . $now_date );
		$this->assertEquals( 0, count( $query->get_orders() ) );

		// Check timestamp range support.
		$query = new WC_Order_Query(
			array(
				'date_created' => $past_stamp . '...' . $future_stamp,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_created', $now_stamp . '...' . $future_stamp );
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_created', $future_stamp . '...' . $now_stamp );
		$this->assertEquals( 0, count( $query->get_orders() ) );
	}

	/**
	 * Test querying by order date properties for dates stored in metadata.
	 *
	 * @since 3.1
	 */
	public function test_order_query_meta_date_queries() {
		$now          = current_time( 'mysql', true );
		$now_stamp    = strtotime( $now );
		$now_date     = date( 'Y-m-d', $now_stamp );
		$past_stamp   = $now_stamp - DAY_IN_SECONDS;
		$past         = date( 'Y-m-d', $past_stamp );
		$future_stamp = $now_stamp + DAY_IN_SECONDS;
		$future       = date( 'Y-m-d', $future_stamp );

		$order = new WC_Order();
		$order->set_date_completed( $now_stamp );
		$order->save();

		// Check WC_DateTime support.
		$query  = new WC_Order_Query(
			array(
				'date_completed' => $order->get_date_completed(),
			)
		);
		$orders = $query->get_orders();
		$this->assertEquals( 1, count( $orders ) );

		// Check date support.
		$query = new WC_Order_Query(
			array(
				'date_completed' => $now_date,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_completed', $past );
		$this->assertEquals( 0, count( $query->get_orders() ) );

		// Check timestamp support.
		$query = new WC_Order_Query(
			array(
				'date_completed' => $order->get_date_completed()->getTimestamp(),
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_completed', $future_stamp );
		$this->assertEquals( 0, count( $query->get_orders() ) );

		// Check comparison support.
		$query = new WC_Order_Query(
			array(
				'date_completed' => '>' . $past,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_completed', '<' . $past );
		$this->assertEquals( 0, count( $query->get_orders() ) );
		$query->set( 'date_completed', '>=' . $now_date );
		$this->assertEquals( 1, count( $query->get_orders() ) );

		// Check timestamp comparison support.
		$query = new WC_Order_Query(
			array(
				'date_completed' => '<' . $future_stamp,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_completed', '<' . $past_stamp );
		$this->assertEquals( 0, count( $query->get_orders() ) );
		$query->set( 'date_completed', '>=' . $now_stamp );

		// Check date range support.
		$query = new WC_Order_Query(
			array(
				'date_completed' => $past . '...' . $future,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_completed', $now_date . '...' . $future );
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_completed', $future . '...' . $now_date );
		$this->assertEquals( 0, count( $query->get_orders() ) );

		// Check timestamp range support.
		$query = new WC_Order_Query(
			array(
				'date_completed' => $past_stamp . '...' . $future_stamp,
			)
		);
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_completed', $now_stamp . '...' . $future_stamp );
		$this->assertEquals( 1, count( $query->get_orders() ) );
		$query->set( 'date_completed', $future_stamp . '...' . $now_stamp );
		$this->assertEquals( 0, count( $query->get_orders() ) );
	}

	/**
	 * Test the query var mapping customer_id => customer_user.
	 *
	 * @since 3.1
	 */
	public function test_order_query_key_mapping() {
		$user_id = wp_insert_user(
			array(
				'user_login' => 'testname',
				'user_pass'  => 'testpass',
				'user_email' => 'email@testmail.com',
			)
		);

		$order = new WC_Order();
		$order->set_customer_id( $user_id );
		$order->save();

		$query   = new WC_Order_Query(
			array(
				'customer_id' => $user_id,
			)
		);
		$results = $query->get_orders();

		$this->assertEquals( 1, count( $results ) );
	}

	public function test_order_query_search_by_customers() {
		$user_email_1 = 'email@testmail.com';
		$user_id_1    = wp_insert_user(
			array(
				'user_login' => 'testname',
				'user_pass'  => 'testpass',
				'user_email' => $user_email_1,
			)
		);

		$user_email_2 = 'email2@testmail.com';
		$user_id_2    = wp_insert_user(
			array(
				'user_login' => 'testname2',
				'user_pass'  => 'testpass2',
				'user_email' => $user_email_2,
			)
		);

		$order1 = new WC_Order();
		$order1->set_customer_id( $user_id_1 );
		$order1->save();

		$order2 = new WC_Order();
		$order2->set_customer_id( $user_id_2 );
		$order2->save();

		$order3 = new WC_Order();
		$order3->set_customer_id( $user_id_2 );
		$order3->save();

		// Searching for both users IDs should return all orders.
		$query   = new WC_Order_Query(
			array(
				'customer' => array( $user_id_1, $user_id_2 ),
			)
		);
		$results = $query->get_orders();
		$this->assertEquals( 3, count( $results ) );

		// Searching for user 1 email and user 2 ID should return all orders.
		$query   = new WC_Order_Query(
			array(
				'customer' => array( $user_email_1, $user_id_2 ),
			)
		);
		$results = $query->get_orders();
		$this->assertEquals( 3, count( $results ) );

		// Searching for orders that match the first user email AND ID should return only a single order
		$query   = new WC_Order_Query(
			array(
				'customer' => array( array( $user_email_1, $user_id_1 ) ),
			)
		);
		$results = $query->get_orders();
		$this->assertEquals( 1, count( $results ) );

		// Searching for orders that match the first user email AND the second user ID should return no orders.
		$query   = new WC_Order_Query(
			array(
				'customer' => array( array( $user_email_1, $user_id_2 ) ),
			)
		);
		$results = $query->get_orders();
		$this->assertEquals( 0, count( $results ) );
	}
}
