<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class OrdersTableQueryTests.
 */
class OrdersTableQueryTests extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Stores the original COT state.
	 *
	 * @var bool
	 */
	private $cot_state;

	/**
	 * Setup - enable COT.
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->setup_cot();
		$this->cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		$this->toggle_cot_feature_and_usage( true );
	}

	/**
	 * Restore the original COT state.
	 */
	public function tearDown(): void {
		$this->toggle_cot_feature_and_usage( $this->cot_state );
		remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		parent::tearDown();
	}

	/**
	 * Helper function to create different orders with different dates for testing.
	 *
	 * @return array Array of WC_Order objects.
	 */
	private function create_orders_with_different_dates() {
		$order1 = OrderHelper::create_order();
		$order2 = OrderHelper::create_order();
		$order3 = OrderHelper::create_order();

		$order1->set_date_created( '2000-01-01T10:00:00' );
		$order1->set_date_modified( '2001-02-01T10:00:00' );
		$order1->set_date_paid( '2002-03-01T10:00:00' );
		$order1->save();

		$order2->set_date_created( '2000-02-01T10:00:00' );
		$order2->set_date_modified( '2001-01-01T10:00:00' );
		$order2->set_date_paid( '2002-03-01T10:00:00' );
		$order2->save();

		$order3->set_date_created( '2001-01-01T10:00:00' );
		$order3->set_date_modified( '2001-02-01T10:00:00' );
		$order3->set_date_paid( '2002-03-01T10:00:00' );
		$order3->save();

		return array( $order1, $order2, $order3 );
	}

	/**
	 * @testDox Nested date queries works as expected.
	 */
	public function test_nested_date_queries_single() {
		$orders = $this->create_orders_with_different_dates();

		$date_query_created_in_2000 = array(
			array(
				'relation' => 'AND',
				array(
					'column'    => 'date_created',
					'inclusive' => true,
					'after'     => '2000-01-01T00:00:00',
				),
				array(
					'column'    => 'date_created',
					'inclusive' => false,
					'before'    => '2001-01-01T10:00:00',
				),
			),
		);

		$queried_orders = wc_get_orders(
			array(
				'return'     => 'ids',
				'date_query' => $date_query_created_in_2000,
			)
		);

		$this->assertEquals( 2, count( $queried_orders ) );
		$this->assertContains( $orders[0]->get_id(), $queried_orders );
		$this->assertContains( $orders[1]->get_id(), $queried_orders );
	}

	/**
	 * @testDox Multiple nested date queries works as expected.
	 */
	public function test_nested_date_queries_multi() {
		$orders = $this->create_orders_with_different_dates();

		$date_query_created_in_2000_and_modified_in_2001 = array(
			array(
				'relation' => 'AND',
				array(
					'column'    => 'date_created',
					'inclusive' => true,
					'after'     => '2000-01-01T00:00:00',
				),
				array(
					'column'    => 'post_date',
					'inclusive' => false,
					'before'    => '2001-01-01T10:00:00',
				),
			),
			array(
				'column' => 'date_modified',
				'before' => '2001-01-02T10:00:00',
			),
		);

		$queried_orders = wc_get_orders(
			array(
				'return'     => 'ids',
				'date_query' => $date_query_created_in_2000_and_modified_in_2001,
			)
		);

		$this->assertEquals( 1, count( $queried_orders ) );
		$this->assertContains( $orders[1]->get_id(), $queried_orders );
	}

	/**
	 * @testDox 'suppress_filters' arg is honored in queries.
	 */
	public function test_query_suppress_filters() {
		$hooks = array(
			'woocommerce_orders_table_query_clauses',
			'woocommerce_orders_table_query_sql',
		);

		$filters_called  = 0;
		$filter_callback = function ( $arg ) use ( &$filters_called ) {
			$filters_called++;
			return $arg;
		};

		foreach ( $hooks as $hook ) {
			add_filter( $hook, $filter_callback );
		}

		// Check that suppress_filters = false is honored.
		foreach ( $hooks as $hook ) {
			wc_get_orders( array() );
		}

		$this->assertNotEquals( $filters_called, 0 );

		// Check that suppress_filters = true is honored.
		$filters_called = 0;
		foreach ( $hooks as $hook ) {
			wc_get_orders(
				array(
					'suppress_filters' => true,
				)
			);
		}
		$this->assertEquals( $filters_called, 0 );

		foreach ( $hooks as $hook ) {
			remove_all_filters( $hook );
		}
	}

	/**
	 * @testdox Query filters successfully allow modificatio of order queries.
	 */
	public function test_query_filters() {
		$order1 = new \WC_Order();
		$order1->set_date_created( time() - HOUR_IN_SECONDS );
		$order1->save();

		$order2 = new \WC_Order();
		$order2->save();

		$this->assertCount( 2, wc_get_orders( array() ) );

		// Force a query that returns nothing.
		$filter_callback = function( $clauses ) {
			$clauses['where'] .= ' AND 1=0 ';
			return $clauses;
		};

		add_filter( 'woocommerce_orders_table_query_clauses', $filter_callback );
		$this->assertCount( 0, wc_get_orders( array() ) );
		remove_all_filters( 'woocommerce_orders_table_query_clauses' );

		// Force a query that sorts orders by id ASC (as opposed to the default date DESC) if a query arg is present.
		$filter_callback = function( $clauses, $query, $query_args ) {
			if ( ! empty( $query_args['my_custom_arg'] ) ) {
				$clauses['orderby'] = $query->get_table_name( 'orders' ) . '.id ASC';
			}

			return $clauses;
		};

		add_filter( 'woocommerce_orders_table_query_clauses', $filter_callback, 10, 3 );
		$this->assertEquals(
			wc_get_orders(
				array(
					'return'        => 'ids',
					'my_custom_arg' => true,
				)
			),
			array(
				$order1->get_id(),
				$order2->get_id(),
			)
		);
		$this->assertEquals(
			wc_get_orders(
				array(
					'return' => 'ids',
				)
			),
			array(
				$order2->get_id(),
				$order1->get_id(),
			)
		);
		remove_all_filters( 'woocommerce_orders_table_query_clauses' );
	}

	/**
	 * @testdox The pre-query escape hook allows replacing the order query. The callback does not return pagination information.
	 */
	public function test_pre_query_escape_hook_simple() {
		$order1 = new \WC_Order();
		$order1->set_date_created( time() - HOUR_IN_SECONDS );
		$order1->save();

		$order2 = new \WC_Order();
		$order2->save();

		$query = new OrdersTableQuery( array() );
		$this->assertCount( 2, $query->orders );
		$this->assertEquals( 2, $query->found_orders );
		$this->assertEquals( 0, $query->max_num_pages );

		$callback = function( $result, $query_object, $sql ) use ( $order1 ) {
			$this->assertNull( $result );
			$this->assertInstanceOf( OrdersTableQuery::class, $query_object );
			$this->assertStringContainsString( 'SELECT ', $sql );

			// Only return one of the orders to show that we are replacing the query result.
			// Do not return found_orders or max_num_pages to show we're setting defaults.
			$order_ids = array( $order1->get_id() );
			return array( $order_ids, null, null );
		};
		add_filter( 'woocommerce_hpos_pre_query', $callback, 10, 3 );

		$query = new OrdersTableQuery( array() );
		$this->assertCount( 1, $query->orders );
		$this->assertEquals( 1, $query->found_orders );
		$this->assertEquals( 1, $query->max_num_pages );
		$this->assertEquals( $order1->get_id(), $query->orders[0] );

		$orders = wc_get_orders( array() );
		$this->assertCount( 1, $orders );
		$this->assertEquals( $order1->get_id(), $orders[0]->get_id() );

		remove_all_filters( 'woocommerce_hpos_pre_query' );
	}

	/**
	 * @testdox The pre-query escape hook allows replacing the order query. The callback returns pagination information.
	 */
	public function test_pre_query_escape_hook_with_pagination() {
		$order1 = new \WC_Order();
		$order1->set_date_created( time() - HOUR_IN_SECONDS );
		$order1->save();

		$order2 = new \WC_Order();
		$order2->save();

		$query = new OrdersTableQuery( array() );
		$this->assertCount( 2, $query->orders );
		$this->assertEquals( 2, $query->found_orders );
		$this->assertEquals( 0, $query->max_num_pages );

		$callback = function( $result, $query_object, $sql ) use ( $order1 ) {
			$this->assertNull( $result );
			$this->assertInstanceOf( OrdersTableQuery::class, $query_object );
			$this->assertStringContainsString( 'SELECT ', $sql );

			// Only return one of the orders to show that we are replacing the query result.
			$order_ids = array( $order1->get_id() );
			// These are made up to show that we are actually replacing the values.
			$found_orders  = 17;
			$max_num_pages = 23;
			return array( $order_ids, $found_orders, $max_num_pages );
		};
		add_filter( 'woocommerce_hpos_pre_query', $callback, 10, 3 );

		$query = new OrdersTableQuery( array() );
		$this->assertCount( 1, $query->orders );
		$this->assertEquals( 17, $query->found_orders );
		$this->assertEquals( 23, $query->max_num_pages );
		$this->assertEquals( $order1->get_id(), $query->orders[0] );

		$orders = wc_get_orders( array() );
		$this->assertCount( 1, $orders );
		$this->assertEquals( $order1->get_id(), $orders[0]->get_id() );

		remove_all_filters( 'woocommerce_hpos_pre_query' );
	}

	/**
	 * @testdox The pre-query escape hook uses the limit arg if it is set.
	 */
	public function test_pre_query_escape_hook_pass_limit() {
		$order1 = new \WC_Order();
		$order1->set_date_created( time() - HOUR_IN_SECONDS );
		$order1->save();

		$callback = function( $result, $query_object, $sql ) use ( $order1 ) {
			// Do not return found_orders or max_num_pages so as to provoke a warning.
			$order_ids = array( $order1->get_id() );
			return array( $order_ids, 10, null );
		};
		add_filter( 'woocommerce_hpos_pre_query', $callback, 10, 3 );

		$query = new OrdersTableQuery(
			array(
				'limit' => 5,
			)
		);
		$this->assertCount( 1, $query->orders );
		$this->assertEquals( 10, $query->found_orders );
		$this->assertEquals( 2, $query->max_num_pages );

		remove_all_filters( 'woocommerce_hpos_pre_query' );
	}

	/**
	 * @testdox A regular query will still work even if the pre-query escape hook returns null for the whole 3-tuple.
	 */
	public function test_pre_query_escape_hook_return_null() {
		add_filter( 'woocommerce_hpos_pre_query', '__return_null', 10, 3 );

		// Query with no results.
		$query = new OrdersTableQuery();
		$this->assertNotNull( $query->orders );
		$this->assertNotNull( $query->found_orders );
		$this->assertNotNull( $query->max_num_pages );
		$this->assertCount( 0, $query->orders );
		$this->assertEquals( 0, $query->found_orders );
		$this->assertEquals( 0, $query->max_num_pages );

		// Query with 1 result.
		$order1 = new \WC_Order();
		$order1->set_date_created( time() - HOUR_IN_SECONDS );
		$order1->save();

		$query = new OrdersTableQuery();
		$this->assertCount( 1, $query->orders );
		$this->assertEquals( 1, $query->found_orders );
		$this->assertEquals( null, $query->max_num_pages );

		remove_all_filters( 'woocommerce_hpos_pre_query' );
	}

	/**
	 * @testdox A regular query with a limit will still work even if the pre-query escape hook returns null for the whole 3-tuple.
	 */
	public function test_pre_query_escape_hook_return_null_limit() {
		$order1 = new \WC_Order();
		$order1->set_date_created( time() - HOUR_IN_SECONDS );
		$order1->save();

		$callback = function( $result, $query_object, $sql ) use ( $order1 ) {
			// Just return null.
			return null;
		};
		add_filter( 'woocommerce_hpos_pre_query', $callback, 10, 3 );

		$query = new OrdersTableQuery(
			array(
				'limit' => 5,
			)
		);
		$this->assertCount( 1, $query->orders );
		$this->assertEquals( 1, $query->found_orders );
		$this->assertEquals( 1, $query->max_num_pages );

		remove_all_filters( 'woocommerce_hpos_pre_query' );
	}

	/**
	 * @testdox Orders will be correctly returned by inexact queries using the 's' search argument.
	 */
	public function test_query_s_argument() {
		$order1 = new \WC_Order();
		$order1->set_billing_first_name( '%ir Woo' );
		$order1->set_billing_email( 'test_user@woo.test' );
		$order1->save();

		$order2 = new \WC_Order();
		$order2->set_billing_email( 'other_user@woo.test' );
		$order2->save();

		$query_args = array(
			's'      => '',
			'return' => 'ids',
		);

		$query_args['s'] = '%';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $order1->get_id() ), $query->orders );

		$query_args['s'] = '%ir';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $order1->get_id() ), $query->orders );

		$query_args['s'] = 'test_user';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $order1->get_id() ), $query->orders );

		$query_args['s'] = 'woo.test';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $order1->get_id(), $order2->get_id() ), $query->orders );

		$query_args['s'] = '_user';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $order1->get_id(), $order2->get_id() ), $query->orders );

		$query_args['s'] = 'nowhere_to_be_found';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertCount( 0, $query->orders );
	}

	/**
	 * Set up some dummy orders, to help test the search filter.
	 *
	 * @return array Order IDs
	 */
	private function setup_dummy_orders_for_search_filter() {
		$customer_order = new \WC_Order();
		$customer_order->set_billing_first_name( 'Customer name' );
		$customer_order->set_billing_email( 'customer@woo.test' );
		$customer_order->set_status( 'completed' );
		$customer_order->save();

		$test_product = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Product name' ) );
		$test_product->save();
		$product_order = new WC_Order();
		$product_order->add_product( $test_product );
		$product_order->set_status( 'completed' );
		$product_order->save();

		return array( $customer_order->get_id(), $product_order->get_id() );
	}

	/**
	 * @testDox The 'search_filter' argument works with a 'customer' param passed in.
	 */
	public function test_query_s_filters_customers() {
		$orders = $this->setup_dummy_orders_for_search_filter();

		$query_args = array(
			's'      => '',
			'return' => 'ids',
		);

		$query_args['search_filter'] = 'customers';

		$query_args['s'] = 'Customer';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $orders[0] ), $query->orders );

		$query_args['s'] = 'Product';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertCount( 0, $query->orders );
	}

	/**
	 * @testDox The 'search_filter' argument works with a 'product' param passed in.
	 */
	public function test_query_s_filters_products() {
		$orders = $this->setup_dummy_orders_for_search_filter();

		$query_args = array(
			's'      => '',
			'return' => 'ids',
		);

		$query_args['search_filter'] = 'products';

		$query_args['s'] = 'Product';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $orders[1] ), $query->orders );

		$query_args['s'] = 'Customer';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertCount( 0, $query->orders );
	}

	/**
	 * @testDox The 'search_filter' argument works with an 'all' param passed in.
	 */
	public function test_query_s_filters_all() {
		$orders = $this->setup_dummy_orders_for_search_filter();

		$query_args = array(
			's'      => '',
			'return' => 'ids',
		);

		// Default search filter is all, so we don't need to set it explicitly.

		$query_args['s'] = 'Product';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $orders[1] ), $query->orders );

		$query_args['s'] = 'Customer';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $orders[0] ), $query->orders );

		$query_args['s'] = 'name';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( $orders, $query->orders );
	}

	/**
	 * @testDox The 'search_filter' argument works with an 'order_id' param passed in.
	 */
	public function test_query_s_filters_order_id() {
		$orders = $this->setup_dummy_orders_for_search_filter();

		$query_args = array(
			's'      => $orders[0],
			'return' => 'ids',
		);

		$query_args['search_filter'] = 'order_id';

		$query = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $orders[0] ), $query->orders );

		$query_args['s'] = $orders[1];
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $orders[1] ), $query->orders );
	}

	/**
	 * @testDox The 'search_filter' argument works with an 'customer_email' param passed in.
	 */
	public function test_query_s_filters_customer_email() {
		$orders = $this->setup_dummy_orders_for_search_filter();

		$query_args = array(
			's'      => 'customer@woo.t',
			'return' => 'ids',
		);

		$query_args['search_filter'] = 'customer_email';

		$query = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $orders[0] ), $query->orders );
	}
}
