<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Orders;

use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Product;
use WC_Order;

/**
 * Class OrdersTableQueryTests.
 *
 * @group order-query-tests
 */
class OrdersTableQueryTests extends \WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::setup_cot_tables();
	}

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
		$this->cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
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
			++$filters_called;
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
		$filter_callback = function ( $clauses ) {
			$clauses['where'] .= ' AND 1=0 ';
			return $clauses;
		};

		add_filter( 'woocommerce_orders_table_query_clauses', $filter_callback );
		$this->assertCount( 0, wc_get_orders( array() ) );
		remove_all_filters( 'woocommerce_orders_table_query_clauses' );

		// Force a query that sorts orders by id ASC (as opposed to the default date DESC) if a query arg is present.
		$filter_callback = function ( $clauses, $query, $query_args ) {
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

		$callback = function ( $result, $query_object, $sql ) use ( $order1 ) {
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

		$callback = function ( $result, $query_object, $sql ) use ( $order1 ) {
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

		$callback = function () use ( $order1 ) {
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

		$callback = function () use ( $order1 ) {
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
		$order1->set_billing_email( 'test_user+shop@woo.test' );
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

		$query_args['s'] = 'test_user+shop';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $order1->get_id() ), $query->orders );

		$query_args['s'] = 'test_user+shop@woo.test';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $order1->get_id() ), $query->orders );

		$query_args['s'] = rawurlencode( 'test_user+shop@woo.test' );
		$query           = new OrdersTableQuery( $query_args );
		$this->assertCount( 0, $query->orders );

		$query_args['s'] = 'other_user';
		$query           = new OrdersTableQuery( $query_args );
		$this->assertEqualsCanonicalizing( array( $order2->get_id() ), $query->orders );

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
		$customer_order->set_status( OrderStatus::COMPLETED );
		$customer_order->save();

		$test_product = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Product name' ) );
		$test_product->save();
		$product_order = new WC_Order();
		$product_order->add_product( $test_product );
		$product_order->set_status( OrderStatus::COMPLETED );
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

	/**
	 * @testDox The get_query_args method returns the initial args sent.
	 */
	public function test_get_query_args() {
		$args = array(
			's' => 'test',
		);

		$query = new OrdersTableQuery( $args );
		$this->assertEquals( $args, $query->get_query_args() );
	}

	/**
	 * @testDox Total filtering with operators works as expected for HPOS storage.
	 */
	public function test_total_filtering_with_operators() {
		$order_totals_to_test = array( 5, 10, 50, 100.00, 100.00, 250.50, 250.50, 500.75, 1000.00 );
		foreach ( $order_totals_to_test as $order_total ) {
			$order = wc_create_order();
			$order->set_total( $order_total );
			$order->save();
		}

		$test_matrix = array(
			array(
				'value'          => 250.50,
				'operator'       => '=',
				'expected_count' => 2,
			),
			array(
				'value'          => 250.50,
				'operator'       => '!=',
				'expected_count' => 7,
			),
			array(
				'value'          => 250.50,
				'operator'       => '>',
				'expected_count' => 2,
			),
			array(
				'value'          => 250.50,
				'operator'       => '>=',
				'expected_count' => 4,
			),
			array(
				'value'          => 250.50,
				'operator'       => '<',
				'expected_count' => 5,
			),
			array(
				'value'          => 250.50,
				'operator'       => '<=',
				'expected_count' => 7,
			),
			array(
				'value'          => array( 100, 500 ),
				'operator'       => 'BETWEEN',
				'expected_count' => 4,
			),
			array(
				'value'          => array( 100, 500 ),
				'operator'       => 'NOT BETWEEN',
				'expected_count' => 5,
			),
		);

		foreach ( $test_matrix as $test ) {
			$orders = wc_get_orders(
				array(
					'total' => array(
						'value'    => $test['value'],
						'operator' => $test['operator'],
					),
				)
			);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$this->assertCount( $test['expected_count'], $orders, print_r( $test, true ) );
		}
	}

	/**
	 * @testDox Orderby total functionality works as expected for HPOS storage.
	 */
	public function test_orderby_total() {
		// Create orders with different totals.
		$order_totals = array( 100.00, 50.00, 250.50, 75.25, 500.00 );
		$orders       = array();
		foreach ( $order_totals as $order_total ) {
			$order = OrderHelper::create_order();
			$order->set_total( $order_total );
			$order->save();
			$orders[] = $order;
		}

		// Test ascending order.
		$orders_asc = wc_get_orders(
			array(
				'orderby' => 'total',
				'order'   => 'asc',
				'return'  => 'ids',
			)
		);

		$this->assertCount( 5, $orders_asc );

		// Verify ascending order by checking totals.
		$totals_asc = array();
		foreach ( $orders_asc as $order_id ) {
			$order        = wc_get_order( $order_id );
			$totals_asc[] = $order->get_total();
		}

		$expected_totals_asc = array( 50.00, 75.25, 100.00, 250.50, 500.00 );
		$this->assertEquals( $expected_totals_asc, $totals_asc, 'Orders should be sorted by total in ascending order' );

		// Test descending order.
		$orders_desc = wc_get_orders(
			array(
				'orderby' => 'total',
				'order'   => 'desc',
				'return'  => 'ids',
			)
		);

		$this->assertCount( 5, $orders_desc );

		// Verify descending order by checking totals.
		$totals_desc = array();
		foreach ( $orders_desc as $order_id ) {
			$order         = wc_get_order( $order_id );
			$totals_desc[] = $order->get_total();
		}

		$expected_totals_desc = array( 500.00, 250.50, 100.00, 75.25, 50.00 );
		$this->assertEquals( $expected_totals_desc, $totals_desc, 'Orders should be sorted by total in descending order' );

		// Clean up.
		foreach ( $orders as $order ) {
			$order->delete( true );
		}
	}

	/**
	 * @testdox Querying orders by customer_note returns only matching orders.
	 */
	public function test_query_customer_note(): void {
		$order1 = new \WC_Order();
		$order1->set_customer_note( 'Please leave at the door' );
		$order1->save();

		$order2 = new \WC_Order();
		$order2->set_customer_note( 'Ring the bell twice' );
		$order2->save();

		$order3 = new \WC_Order();
		$order3->save();

		// Exact match returns only the matching order.
		$query = new OrdersTableQuery(
			array(
				'customer_note' => 'Please leave at the door',
				'return'        => 'ids',
			)
		);
		$this->assertEqualsCanonicalizing( array( $order1->get_id() ), $query->orders );

		// Different note returns the other order.
		$query = new OrdersTableQuery(
			array(
				'customer_note' => 'Ring the bell twice',
				'return'        => 'ids',
			)
		);
		$this->assertEqualsCanonicalizing( array( $order2->get_id() ), $query->orders );

		// Empty string matches orders with no customer note.
		$query = new OrdersTableQuery(
			array(
				'customer_note' => '',
				'return'        => 'ids',
			)
		);
		$this->assertContains( $order3->get_id(), $query->orders );
		$this->assertNotContains( $order1->get_id(), $query->orders );
		$this->assertNotContains( $order2->get_id(), $query->orders );

		$order1->delete( true );
		$order2->delete( true );
		$order3->delete( true );
	}

	/**
	 * Helper function to create orders with interleaved statuses and strictly decreasing creation dates.
	 *
	 * @param int $count Number of orders to create.
	 * @return int[] Order IDs, ordered by creation date descending.
	 */
	private function create_orders_with_interleaved_statuses( int $count ): array {
		$statuses = array( OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::COMPLETED );
		$ids      = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$order = new \WC_Order();
			$order->set_status( $statuses[ $i % count( $statuses ) ] );
			$order->set_date_created( strtotime( '2023-06-01 12:00:00' ) - ( $i * HOUR_IN_SECONDS ) );
			$order->save();
			$ids[] = $order->get_id();
		}

		return $ids;
	}

	/**
	 * Helper function to run wc_get_orders() and capture the SQL query executed by OrdersTableQuery.
	 *
	 * @param array $args Query args ('return' => 'ids' is always added).
	 * @return array Two-element array containing the queried order IDs and the executed SQL query.
	 */
	private function get_orders_and_capture_sql( array $args ): array {
		$captured_sql = '';
		$callback     = function ( $result, $query, $sql ) use ( &$captured_sql ) {
			// Avoid parameter not used PHPCS errors.
			unset( $query );
			$captured_sql = $sql;
			return $result;
		};

		add_filter( 'woocommerce_hpos_pre_query', $callback, 10, 3 );
		$ids = wc_get_orders( array_merge( $args, array( 'return' => 'ids' ) ) );
		remove_filter( 'woocommerce_hpos_pre_query', $callback );

		return array( $ids, $captured_sql );
	}


	/**
	 * Helper function to force-enable the status union rewrite, which by default is gated by store size.
	 */
	private function force_enable_status_union_rewrite(): void {
		add_filter( 'woocommerce_orders_table_query_status_union_optimization', '__return_true' );
	}

	/**
	 * Helper function to remove the force-enablement of the status union rewrite.
	 */
	private function reset_status_union_rewrite(): void {
		remove_filter( 'woocommerce_orders_table_query_status_union_optimization', '__return_true' );
	}

	/**
	 * @testdox Multi-status queries ordered by creation date are rewritten as a UNION of single-status queries and return the same results.
	 */
	public function test_status_union_rewrite_applies_and_preserves_results(): void {
		$ids  = $this->create_orders_with_interleaved_statuses( 9 );
		$args = array(
			'status'  => array( OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::COMPLETED ),
			'orderby' => 'date',
			'order'   => 'DESC',
			'limit'   => 4,
		);

		$this->force_enable_status_union_rewrite();
		list( $queried_ids, $sql ) = $this->get_orders_and_capture_sql( $args );
		$this->reset_status_union_rewrite();

		$this->assertStringContainsString( 'UNION ALL', $sql, 'Eligible multi-status queries should be rewritten as a UNION of single-status queries' );
		$this->assertSame( array_slice( $ids, 0, 4 ), $queried_ids, 'The rewritten query should return the most recent orders across all statuses' );

		add_filter( 'woocommerce_orders_table_query_status_union_optimization', '__return_false' );
		list( $unoptimized_ids, $unoptimized_sql ) = $this->get_orders_and_capture_sql( $args );
		remove_filter( 'woocommerce_orders_table_query_status_union_optimization', '__return_false' );

		$this->assertStringNotContainsString( 'UNION ALL', $unoptimized_sql, 'The rewrite should be disabled by the woocommerce_orders_table_query_status_union_optimization filter' );
		$this->assertSame( $unoptimized_ids, $queried_ids, 'Rewritten and regular queries should return identical results' );
	}

	/**
	 * @testdox The default order query (multiple statuses, ordered by creation date) is rewritten as a UNION of single-status queries.
	 */
	public function test_status_union_rewrite_applies_to_default_query(): void {
		$ids = $this->create_orders_with_interleaved_statuses( 3 );

		$this->force_enable_status_union_rewrite();
		list( $queried_ids, $sql ) = $this->get_orders_and_capture_sql( array() );
		$this->reset_status_union_rewrite();

		$this->assertStringContainsString( 'UNION ALL', $sql, 'The default order query should be rewritten as a UNION of single-status queries' );
		$this->assertSame( $ids, $queried_ids, 'The rewritten default query should return all orders, most recent first' );
	}

	/**
	 * @testdox The status union rewrite returns the same results as the regular query across pages and sort directions.
	 */
	public function test_status_union_rewrite_pagination_and_sort_direction(): void {
		$this->create_orders_with_interleaved_statuses( 9 );

		$this->force_enable_status_union_rewrite();

		foreach ( array( 'DESC', 'ASC' ) as $order ) {
			foreach ( array( 1, 2, 3 ) as $page ) {
				$args = array(
					'status'  => array( OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::COMPLETED ),
					'orderby' => 'date',
					'order'   => $order,
					'limit'   => 4,
					'page'    => $page,
				);

				list( $queried_ids, $sql ) = $this->get_orders_and_capture_sql( $args );

				add_filter( 'woocommerce_orders_table_query_status_union_optimization', '__return_false' );
				list( $unoptimized_ids ) = $this->get_orders_and_capture_sql( $args );
				remove_filter( 'woocommerce_orders_table_query_status_union_optimization', '__return_false' );

				$this->assertStringContainsString( 'UNION ALL', $sql, "Page {$page} ({$order}) should be served by the rewritten query" );
				$this->assertSame( $unoptimized_ids, $queried_ids, "Page {$page} ({$order}) of the rewritten query should match the regular query" );
			}
		}

		$this->reset_status_union_rewrite();
	}

	/**
	 * @testdox The status union rewrite is skipped for queries it cannot serve identically.
	 */
	public function test_status_union_rewrite_skipped_for_ineligible_queries(): void {
		$this->create_orders_with_interleaved_statuses( 3 );
		$this->force_enable_status_union_rewrite();

		$ineligible_args = array(
			'a single status'    => array( 'status' => array( OrderStatus::PENDING ) ),
			'no row limit'       => array( 'limit' => -1 ),
			'a non-date orderby' => array( 'orderby' => 'id' ),
			'a field filter'     => array( 'customer_id' => 123 ),
			'a meta query'       => array(
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

					array(
						'key'   => 'some_key',
						'value' => 'some_value',
					),
				),
			),
		);

		foreach ( $ineligible_args as $description => $args ) {
			$args = array_merge(
				array(
					'status'  => array( OrderStatus::PENDING, OrderStatus::PROCESSING ),
					'orderby' => 'date',
					'order'   => 'DESC',
					'limit'   => 4,
				),
				$args
			);

			list( , $sql ) = $this->get_orders_and_capture_sql( $args );

			$this->assertStringNotContainsString( 'UNION ALL', $sql, "A query with {$description} should not be rewritten" );
		}

		$this->reset_status_union_rewrite();
	}

	/**
	 * @testdox Queries customized via the clauses filter are not rewritten.
	 */
	public function test_status_union_rewrite_skipped_when_clauses_modified(): void {
		$ids = $this->create_orders_with_interleaved_statuses( 3 );
		$this->force_enable_status_union_rewrite();

		$filter_callback = function ( $clauses ) {
			$clauses['where'] .= ' AND 1=1';
			return $clauses;
		};

		add_filter( 'woocommerce_orders_table_query_clauses', $filter_callback );
		list( $queried_ids, $sql ) = $this->get_orders_and_capture_sql(
			array(
				'status'  => array( OrderStatus::PENDING, OrderStatus::PROCESSING ),
				'orderby' => 'date',
				'order'   => 'DESC',
				'limit'   => 4,
			)
		);
		remove_filter( 'woocommerce_orders_table_query_clauses', $filter_callback );

		$this->reset_status_union_rewrite();

		$this->assertStringNotContainsString( 'UNION ALL', $sql, 'Queries modified via the clauses filter should not be rewritten' );
		$this->assertSame( array( $ids[0], $ids[1] ), $queried_ids, 'The unmodified query should still return matching orders' );
	}

	/**
	 * @testdox Queries modified via the SQL filter are not rewritten, and the modified SQL is the one executed.
	 */
	public function test_status_union_rewrite_skipped_when_sql_modified(): void {
		$ids = $this->create_orders_with_interleaved_statuses( 3 );
		$this->force_enable_status_union_rewrite();

		$filter_callback = function ( $sql ) {
			return $sql . ' -- modified';
		};

		add_filter( 'woocommerce_orders_table_query_sql', $filter_callback );
		list( $queried_ids, $sql ) = $this->get_orders_and_capture_sql(
			array(
				'status'  => array( OrderStatus::PENDING, OrderStatus::PROCESSING ),
				'orderby' => 'date',
				'order'   => 'DESC',
				'limit'   => 4,
			)
		);
		remove_filter( 'woocommerce_orders_table_query_sql', $filter_callback );

		$this->reset_status_union_rewrite();

		$this->assertStringNotContainsString( 'UNION ALL', $sql, 'Queries modified via the SQL filter should not be rewritten' );
		$this->assertStringEndsWith( '-- modified', $sql, 'The SQL modified by the filter should be the SQL that gets executed' );
		$this->assertSame( array( $ids[0], $ids[1] ), $queried_ids, 'The filter-modified query should still return matching orders' );
	}

	/**
	 * @testdox The status union rewrite is disabled by default on stores below the order count threshold.
	 */
	public function test_status_union_rewrite_disabled_by_default_on_small_stores(): void {
		$this->create_orders_with_interleaved_statuses( 3 );

		list( , $sql ) = $this->get_orders_and_capture_sql(
			array(
				'status'  => array( OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::COMPLETED ),
				'orderby' => 'date',
				'order'   => 'DESC',
				'limit'   => 4,
			)
		);

		$this->assertStringNotContainsString( 'UNION ALL', $sql, 'The rewrite should be disabled by default on stores below the order count threshold' );
	}

	/**
	 * @testdox The status union rewrite is enabled by default once cached order counts reach the threshold.
	 */
	public function test_status_union_rewrite_enabled_by_default_on_large_stores(): void {
		$ids = $this->create_orders_with_interleaved_statuses( 3 );

		$count_cache = new OrderCountCache();
		$count_cache->set_multiple(
			'shop_order',
			array(
				'wc-pending'    => 200000,
				'wc-processing' => 200000,
				'wc-completed'  => 200000,
			)
		);

		list( $queried_ids, $sql ) = $this->get_orders_and_capture_sql(
			array(
				'type'    => 'shop_order',
				'status'  => array( OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::COMPLETED ),
				'orderby' => 'date',
				'order'   => 'DESC',
				'limit'   => 4,
			)
		);

		$count_cache->flush( 'shop_order' );

		$this->assertStringContainsString( 'UNION ALL', $sql, 'The rewrite should be enabled by default once cached order counts reach the threshold' );
		$this->assertSame( $ids, $queried_ids, 'The rewritten query should return all orders, most recent first' );
	}
}
