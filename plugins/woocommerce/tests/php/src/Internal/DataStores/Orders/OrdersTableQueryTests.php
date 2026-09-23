<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Orders;

use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Helpers\DateQueryGuardTrait;
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
	use DateQueryGuardTrait;

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

	/**
	 * Malformed values for args that RESTRICT the result set.
	 *
	 * Each raised an uncaught fatal before #58259. The correct outcome is an empty result set:
	 * discarding a restricting arg would widen the query, which is the dangerous direction.
	 * meta_query is absent because its fatal lives in OrdersTableMetaQuery, fixed separately.
	 *
	 * @return array<string, array{0: array}>
	 */
	public function provider_malformed_restricting_args(): array {
		return array(
			'date_created array'         => array( array( 'date_created' => array( 'foo' ) ) ),
			'date_created nested array'  => array( array( 'date_created' => array( array() ) ) ),
			'date_created object'        => array( array( 'date_created' => new \stdClass() ) ),
			'date_paid array'            => array( array( 'date_paid' => array( 'foo' ) ) ),
			'date_completed array'       => array( array( 'date_completed' => array( 'foo' ) ) ),
			'date_created_gmt object'    => array( array( 'date_created_gmt' => new \stdClass() ) ),
			'date_query string'          => array( array( 'date_query' => 'bogus' ) ),
			'date_query int'             => array( array( 'date_query' => 12345 ) ),
			'date_query true'            => array( array( 'date_query' => true ) ),
			'status object'              => array( array( 'status' => new \stdClass() ) ),
			'search_filter array'        => array(
				array(
					's'             => 'x',
					'search_filter' => array( 'y' ),
				),
			),
			'field_query field array'    => array( array( 'field_query' => array( array( 'field' => array() ) ) ) ),
			'field_query compare array'  => array(
				array(
					'field_query' => array(
						array(
							'field'   => 'id',
							'compare' => array(),
						),
					),
				),
			),
			'field_query IN object'      => array(
				array(
					'field_query' => array(
						array(
							'field'   => 'id',
							'compare' => 'IN',
							'value'   => new \stdClass(),
						),
					),
				),
			),
			'field_query BETWEEN object' => array(
				array(
					'field_query' => array(
						array(
							'field'   => 'id',
							'compare' => 'BETWEEN',
							'value'   => new \stdClass(),
						),
					),
				),
			),
			'bool field object'          => array( array( 'prices_include_tax' => new \stdClass() ) ),
			// Nested, not array( 'x' ): where() decomposes a top-level array into an IN list.
			'bool field array'           => array( array( 'prices_include_tax' => array( array() ) ) ),
			'decimal field object'       => array( array( 'tax_amount' => new \stdClass() ) ),
			'decimal field array'        => array( array( 'tax_amount' => array( array() ) ) ),
			'date_query nested column'   => array( array( 'date_query' => array( array( 'column' => new \stdClass() ) ) ) ),
			'date_query nested value'    => array(
				array(
					'date_query' => array(
						array(
							'column' => 'date_created',
							'before' => new \stdClass(),
						),
					),
				),
			),
			'date_query nested array'    => array(
				array(
					'date_query' => array(
						array(
							'column' => 'date_created',
							'after'  => array( 'year' => 2000 ),
						),
					),
				),
			),
			'date_query gmt before'      => array(
				array(
					'date_query' => array(
						array(
							'column' => 'date_created_gmt',
							'before' => new \stdClass(),
						),
					),
				),
			),
			'date_query no column'       => array( array( 'date_query' => array( 'before' => new \stdClass() ) ) ),
			'date_query relation'        => array(
				array(
					'date_query' => array(
						'relation' => new \stdClass(),
						array(
							'column' => 'date_created_gmt',
							'after'  => '2000-01-01',
						),
					),
				),
			),
			'date_query year'            => array(
				array(
					'date_query' => array(
						array(
							'column' => 'date_created_gmt',
							'year'   => new \stdClass(),
						),
					),
				),
			),
			'total BETWEEN object'       => array(
				array(
					'total' => array(
						'value'    => array( new \stdClass(), 5 ),
						'operator' => 'BETWEEN',
					),
				),
			),
			'bool field resource'        => array( array( 'prices_include_tax' => STDIN ) ),
			'field_query compare object' => array(
				array(
					'field_query' => array(
						array(
							'field'   => 'id',
							'compare' => new \stdClass(),
						),
					),
				),
			),
			'field_query LIKE object'    => array(
				array(
					'field_query' => array(
						array(
							'field'   => 'status',
							'compare' => 'LIKE',
							'value'   => new \stdClass(),
						),
					),
				),
			),
			'currency object'            => array( array( 'currency' => new \stdClass() ) ),
			'type object'                => array( array( 'type' => new \stdClass() ) ),
			'payment_method object'      => array( array( 'payment_method' => new \stdClass() ) ),
			'billing_email object'       => array( array( 'billing_email' => new \stdClass() ) ),
			'transaction_id object'      => array( array( 'transaction_id' => new \stdClass() ) ),
			'customer_note object'       => array( array( 'customer_note' => new \stdClass() ) ),
		);
	}

	/**
	 * @testDox A malformed restricting arg returns no orders instead of raising a fatal error.
	 *
	 * @dataProvider provider_malformed_restricting_args
	 *
	 * @param array $args Malformed args to pass to `wc_get_orders()`.
	 */
	public function test_malformed_restricting_args_fail_closed( array $args ): void {
		// Also asserts the notice fires: WP fails if a declared one never is.
		$this->setExpectedIncorrectUsage( 'wc_get_orders' );

		$this->create_orders_with_different_dates();

		$result = wc_get_orders(
			array_merge(
				$args,
				array(
					'return' => 'ids',
					'limit'  => -1,
				)
			)
		);

		// assertSame( array() ), not assertIsArray(): a type-only check would pass even if the
		// arg were dropped and the query widened.
		$this->assertSame( array(), $result, 'A malformed restricting arg must match no orders.' );
	}

	/**
	 * Args that already worked before the fatal-error guard and must be completely unaffected.
	 *
	 * The third element indexes into create_orders_with_different_dates(), so a guard that wrongly
	 * rejects a valid value fails here.
	 *
	 * @return array<string, array{0: mixed, 1: string, 2: array<int>}>
	 */
	public function provider_unaffected_date_args(): array {
		// Order 0 created 2000-01-01, order 1 on 2000-02-01, order 2 on 2001-01-01.
		return array(
			'plain date'         => array( '2000-01-01', 'date_created', array( 0 ) ),
			'shorthand >='       => array( '>=2000-02-01', 'date_created', array( 1, 2 ) ),
			'shorthand <'        => array( '<2000-02-01', 'date_created', array( 0 ) ),
			'range'              => array( '2000-01-01...2000-12-31', 'date_created', array( 0, 1 ) ),
			'gmt key plain date' => array( '2000-01-01', 'date_created_gmt', array( 0 ) ),
			'gmt key range'      => array( '2000-01-01...2000-12-31', 'date_created_gmt', array( 0, 1 ) ),
			'unparseable string' => array( 'not-a-date', 'date_created', array() ),
			'WC_DateTime'        => array( new \WC_DateTime( '2001-01-01T10:00:00', new \DateTimeZone( 'UTC' ) ), 'date_created', array( 2 ) ),
		);
	}

	/**
	 * Args that must not fatal, exercised under production error semantics.
	 *
	 * Some fail on trunk by way of a warning first. phpunit converts that into an exception the
	 * data store catches, so without production semantics they would pass with no guard at all.
	 *
	 * @return array<string, array{0: array}>
	 */
	public function provider_args_not_allowed_to_fatal(): array {
		return array(
			'search term array'    => array( array( 's' => array( 'x' ) ) ),
			'search term object'   => array( array( 's' => new \stdClass() ) ),
			'order as array'       => array( array( 'order' => array( 'DESC' ) ) ),
			// Objects, not arrays: an array only warns on a string cast, an object raises an Error,
			// so only the object form fails if the fallback is removed.
			'order as object'      => array( array( 'order' => new \stdClass() ) ),
			'field_query type'     => array(
				array(
					'field_query' => array(
						array(
							'field' => 'id',
							'type'  => array(),
						),
					),
				),
			),
			'field_query type obj' => array(
				array(
					'field_query' => array(
						array(
							'field' => 'id',
							'type'  => new \stdClass(),
						),
					),
				),
			),
			'field_query relation' => array(
				array(
					'field_query' => array(
						'relation' => new \stdClass(),
						array(
							'field'   => 'id',
							'value'   => 1,
							'compare' => '>',
						),
						array(
							'field'   => 'id',
							'value'   => 99999,
							'compare' => '<',
						),
					),
				),
			),
		);
	}

	/**
	 * @testDox Malformed args do not fatal under production error semantics.
	 *
	 * @dataProvider provider_args_not_allowed_to_fatal
	 *
	 * @param array $args Malformed args to pass to `wc_get_orders()`.
	 */
	public function test_malformed_args_do_not_fatal_under_production_semantics( array $args ): void {
		// Declaring this asserts the notice actually fires: WP fails the test both if an
		// undeclared incorrect-usage notice is raised and if a declared one never is.
		$this->setExpectedIncorrectUsage( 'wc_get_orders' );

		$this->create_orders_with_different_dates();

		// Swallow warnings and continue, as production does. Without this the first warning
		// becomes an exception the data store catches, and the assertion proves nothing.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Test-only: reproduces production error semantics.
		set_error_handler( static fn() => true );

		try {
			$result = wc_get_orders(
				array_merge(
					$args,
					array(
						'return' => 'ids',
						'limit'  => -1,
					)
				)
			);
		} finally {
			restore_error_handler();
		}

		$this->assertIsArray( $result, 'A malformed arg must not raise a fatal error.' );
	}

	/**
	 * @testDox An unusable relation or cast type falls back instead of emptying the query.
	 */
	public function test_field_query_fallbacks_do_not_empty_the_query(): void {
		// Declaring this asserts the notice actually fires: WP fails the test both if an
		// undeclared incorrect-usage notice is raised and if a declared one never is.
		$this->setExpectedIncorrectUsage( 'wc_get_orders' );

		$orders = $this->create_orders_with_different_dates();
		$id     = $orders[1]->get_id();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Test-only: reproduces production error semantics.
		set_error_handler( static fn() => true );

		try {
			// An unusable cast type must behave like an absent one, not invalidate the clause.
			$result = wc_get_orders(
				array(
					'field_query' => array(
						array(
							'field' => 'id',
							'value' => $id,
							'type'  => array(),
						),
					),
					'return'      => 'ids',
					'limit'       => -1,
				)
			);
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array( $id ), $result, 'An unusable cast type must still match the order.' );
	}

	/**
	 * @testDox A string field with one unusable element still matches its usable ones.
	 *
	 * strval() yields 'Array' rather than raising, so this list worked before and must keep
	 * working. Rejecting the whole clause would empty a working query.
	 */
	public function test_string_field_keeps_matching_usable_elements(): void {
		$order = OrderHelper::create_order();
		$order->set_billing_email( 'guard-test@example.com' );
		$order->save();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Test-only: reproduces production error semantics.
		set_error_handler( static fn() => true );

		try {
			$result = wc_get_orders(
				array(
					'billing_email' => array( 'guard-test@example.com', array() ),
					'return'        => 'ids',
					'limit'         => -1,
					'status'        => 'any',
				)
			);
		} finally {
			restore_error_handler();
		}

		$this->assertContains( $order->get_id(), $result, 'A usable element must still match.' );
	}

	/**
	 * @testDox Args that never fatalled keep returning every order.
	 *
	 * @dataProvider provider_args_that_never_fatalled
	 *
	 * @param array $args Args that are inert rather than restricting.
	 */
	public function test_args_that_never_fatalled_are_unchanged( array $args ): void {
		$orders = $this->create_orders_with_different_dates();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Test-only: reproduces production error semantics.
		set_error_handler( static fn() => true );

		try {
			// 'status' goes FIRST because it is a default: a later key wins, and one provider case
			// supplies its own status.
			$result = wc_get_orders(
				array_merge(
					array( 'status' => 'any' ),
					$args,
					array(
						'return' => 'ids',
						'limit'  => -1,
					)
				)
			);
		} finally {
			restore_error_handler();
		}

		$this->assertCount( count( $orders ), $result, 'An arg that never fatalled must keep matching every order.' );
	}

	/**
	 * Args that produced results on trunk and must be left alone by the guards.
	 *
	 * @return array<string, array{0: array}>
	 */
	public function provider_args_that_never_fatalled(): array {
		return array(
			'exclude object'      => array( array( 'exclude' => new \stdClass() ) ),
			'status nested array' => array( array( 'status' => array( array() ) ) ),
			'orderby array'       => array( array( 'orderby' => array( array() ) ) ),
			'date_query false'    => array( array( 'date_query' => false ) ),
			'search_filter alone' => array( array( 'search_filter' => array( 'x' ) ) ),
			'status all'          => array( array( 'status' => 'all' ) ),
			'status any'          => array( array( 'status' => 'any' ) ),
			'status array all'    => array( array( 'status' => array( 'all' ) ) ),
			'empty status list'   => array( array( 'status' => array() ) ),
		);
	}

	/**
	 * @testDox An unusable customer is left to the existing 1=0 handling rather than guarded.
	 *
	 * generate_customer_query() already resolves an unusable value to a false predicate, so it
	 * never fatalled. Guarding it in an earlier revision broke the nested-array grouping form.
	 */
	public function test_unusable_customer_is_not_guarded(): void {
		$this->create_orders_with_different_dates();

		$result = wc_get_orders(
			array(
				'customer' => new \stdClass(),
				'return'   => 'ids',
				'limit'    => -1,
				'status'   => 'any',
			)
		);

		$this->assertSame( array(), $result, 'An unusable customer must match no orders.' );
	}

	/**
	 * Falsy date_query values, which carried no restriction before this change.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_falsy_date_query_values(): array {
		return array(
			'false'       => array( false ),
			'zero'        => array( 0 ),
			'zero string' => array( '0' ),
		);
	}

	/**
	 * @testDox A falsy date_query is normalised rather than rejected, so other date args still work.
	 *
	 * arg_isset() does not treat these as unset, so they reached array_merge() as soon as another
	 * date arg was present. Normalised rather than rejected, because alone they were inert.
	 *
	 * @dataProvider provider_falsy_date_query_values
	 *
	 * @param mixed $falsy The falsy date_query value.
	 */
	public function test_falsy_date_query_is_normalised_not_rejected( $falsy ): void {
		$orders = $this->create_orders_with_different_dates();

		$result = wc_get_orders(
			array(
				'date_query'   => $falsy,
				'date_created' => '2000-01-01',
				'return'       => 'ids',
				'limit'        => -1,
				'orderby'      => 'id',
				'order'        => 'ASC',
			)
		);

		// The surviving date_created filter still applies: only order 0 was created that day.
		$this->assertSame( array( $orders[0]->get_id() ), $result, 'The other date arg must still filter.' );
	}

	/**
	 * @testDox Date args that worked before the guard keep matching exactly the same orders.
	 *
	 * @dataProvider provider_unaffected_date_args
	 *
	 * @param mixed  $value            The date value.
	 * @param string $date_key         The date query arg to set.
	 * @param array  $expected_indexes Indexes of the orders the arg must match.
	 */
	public function test_valid_date_args_are_unaffected( $value, string $date_key, array $expected_indexes ): void {
		$orders = $this->create_orders_with_different_dates();

		$result = wc_get_orders(
			array(
				$date_key => $value,
				'return'  => 'ids',
				'limit'   => -1,
				'orderby' => 'id',
				'order'   => 'ASC',
			)
		);

		$expected = array_map(
			static function ( $index ) use ( $orders ) {
				return $orders[ $index ]->get_id();
			},
			$expected_indexes
		);

		$this->assertSame( $expected, $result, 'A valid date arg must keep matching the same orders.' );
	}

	/**
	 * @testDox The 'total' arg keeps using the store's configured price decimals.
	 *
	 * Guards a regression in which resolving the decimals was gated on the internal 'total_amount'
	 * key, which the public 'total' arg is only remapped to later, so 'total' silently fell back to
	 * two decimals and stopped matching on stores configured for anything else.
	 */
	public function test_total_arg_respects_store_price_decimals(): void {
		update_option( 'woocommerce_price_num_decimals', 4 );

		try {
			$order = OrderHelper::create_order();
			$order->set_total( 25.1235 );
			$order->save();

			$by_alias  = wc_get_orders(
				array(
					'total'  => 25.1235,
					'return' => 'ids',
					'limit'  => -1,
				)
			);
			$by_column = wc_get_orders(
				array(
					'total_amount' => 25.1235,
					'return'       => 'ids',
					'limit'        => -1,
				)
			);

			$this->assertSame( array( $order->get_id() ), $by_alias, "The 'total' arg must match a 4-decimal total." );
			$this->assertSame( $by_column, $by_alias, "'total' and 'total_amount' must agree." );
		} finally {
			update_option( 'woocommerce_price_num_decimals', 2 );
		}
	}

	/**
	 * @testDox An unusable field_query comparison invalidates the clause instead of inverting it.
	 *
	 * Falling back to '=' is unsafe here: an array value promotes the default to 'IN', so a
	 * malformed 'NOT IN' would return the complement of what the caller asked for.
	 */
	public function test_unusable_field_query_compare_does_not_invert_the_clause(): void {
		// Declaring this asserts the notice actually fires: WP fails the test both if an
		// undeclared incorrect-usage notice is raised and if a declared one never is.
		$this->setExpectedIncorrectUsage( 'wc_get_orders' );

		$orders = $this->create_orders_with_different_dates();
		$ids    = array( $orders[0]->get_id(), $orders[1]->get_id() );

		$complement = wc_get_orders(
			array(
				'field_query' => array(
					array(
						'field'   => 'id',
						'value'   => $ids,
						'compare' => 'NOT IN',
					),
				),
				'return'      => 'ids',
				'limit'       => -1,
				'status'      => 'any',
			)
		);

		// An object, not an array: an array only warns, which phpunit converts, so it would pass
		// with the guard removed. The array form is the motivating case in production.
		$malformed = wc_get_orders(
			array(
				'field_query' => array(
					array(
						'field'   => 'id',
						'value'   => $ids,
						'compare' => new \stdClass(),
					),
				),
				'return'      => 'ids',
				'limit'       => -1,
				'status'      => 'any',
			)
		);

		$this->assertNotEmpty( $complement, 'A valid NOT IN must still match the other orders.' );
		$this->assertSame( array(), $malformed, 'An unusable comparison must not silently invert the predicate.' );
	}

	/**
	 * Status lists pairing an unusable object with a sibling that filters nothing on its own.
	 *
	 * @return array<string, array{0: array}>
	 */
	public function provider_unusable_status_with_inert_sibling(): array {
		return array(
			'empty string sibling' => array( array( '', new \stdClass() ) ),
			'null sibling'         => array( array( null, new \stdClass() ) ),
			'empty array sibling'  => array( array( array(), new \stdClass() ) ),
		);
	}

	/**
	 * @testDox An unusable status whose only siblings are inert fails the query closed.
	 *
	 * @dataProvider provider_unusable_status_with_inert_sibling
	 *
	 * @param array $status Status list mixing an unusable entry with an inert one.
	 */
	public function test_unusable_status_with_inert_sibling_fails_closed( array $status ): void {
		// Also asserts the notice fires: WP fails if a declared one never is.
		$this->setExpectedIncorrectUsage( 'wc_get_orders' );

		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		// An array entry warns on the 'wc-' concatenation exactly as it did before this change.
		// Swallow it and continue, as production does, rather than let phpunit convert it.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Test-only: reproduces production error semantics.
		set_error_handler( static fn() => true, E_WARNING | E_NOTICE );

		try {
			// An inert entry constrains no status, so keeping it as a survivor would drop the
			// clause and return every order, including trashed ones.
			$result = wc_get_orders(
				array(
					'status' => $status,
					'return' => 'ids',
					'limit'  => -1,
				)
			);
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array(), $result, 'The query must match no orders.' );
	}

	/**
	 * @testDox An unusable status entry is dropped while a usable sibling keeps filtering.
	 */
	public function test_partially_usable_status_list_keeps_working(): void {
		// The dropped entry is still reported even though the query runs; declaring this also
		// asserts the notice fires, since WP fails the test if a declared one never does.
		$this->setExpectedIncorrectUsage( 'wc_get_orders' );

		$completed = OrderHelper::create_order();
		$completed->set_status( OrderStatus::COMPLETED );
		$completed->save();

		$pending = OrderHelper::create_order();
		$pending->set_status( OrderStatus::PENDING );
		$pending->save();

		// An unregistered string sibling is already carried into the IN clause and simply matches
		// nothing, leaving 'completed' filtering. An unusable object gets the same treatment, so
		// the result must not depend on which kind of junk the caller passed.
		$result = wc_get_orders(
			array(
				'status' => array( OrderStatus::COMPLETED, new \stdClass() ),
				'return' => 'ids',
				'limit'  => -1,
			)
		);

		$this->assertContains( $completed->get_id(), $result, 'A usable sibling must keep filtering.' );
		$this->assertNotContains( $pending->get_id(), $result, 'The surviving status must still restrict the query.' );
	}

	/**
	 * @testDox An unregistered status string does not poison its siblings.
	 */
	public function test_unregistered_status_string_keeps_sibling(): void {
		$completed = OrderHelper::create_order();
		$completed->set_status( OrderStatus::COMPLETED );
		$completed->save();

		$pending = OrderHelper::create_order();
		$pending->set_status( OrderStatus::PENDING );
		$pending->save();

		// This is the pre-existing contract the object case above is aligned with.
		$result = wc_get_orders(
			array(
				'status' => array( OrderStatus::COMPLETED, 'bogus-not-a-status' ),
				'return' => 'ids',
				'limit'  => -1,
			)
		);

		$this->assertContains( $completed->get_id(), $result, 'An unregistered status must not drop a valid sibling.' );
		$this->assertNotContains( $pending->get_id(), $result, 'The valid status must still restrict the query.' );
	}

	/**
	 * @testDox An unsupported operator still returns an empty clause rather than throwing.
	 */
	public function test_unsupported_operator_returns_empty_clause(): void {
		$query = new OrdersTableQuery( array( 'limit' => 1 ) );

		// where() is public. An unsupported operator short-circuits before the value is used, and
		// callers rely on the empty clause rather than an exception.
		$this->assertSame(
			'',
			$query->where( 't', 'f', 'BOGUS', new \stdClass(), 'string' ),
			'An unsupported operator must not throw on an unusable value it never reaches.'
		);
	}

	/**
	 * @testDox A date_query the guard must honour still matches, shared with the legacy suite.
	 *
	 * @dataProvider provider_date_query_must_match
	 *
	 * @param array $date_query   The clause under test.
	 * @param bool  $should_match Whether the seeded order should be returned.
	 */
	public function test_shared_date_query_must_match( array $date_query, bool $should_match = true ): void {
		$order = OrderHelper::create_order();
		$order->set_date_created( '2024-06-01' );
		$order->save();

		$result = wc_get_orders(
			array(
				'date_query' => $date_query,
				'return'     => 'ids',
				'limit'      => -1,
				'status'     => 'any',
			)
		);

		if ( $should_match ) {
			$this->assertContains( $order->get_id(), $result, 'A supported date_query must keep matching.' );
		} else {
			$this->assertNotContains( $order->get_id(), $result, 'The clause must still restrict the query.' );
		}
	}

	/**
	 * @testDox An unusable date_query column fails the query closed.
	 *
	 * Not in the shared set: WP_Query ignores an unusable column, so the legacy store answers this
	 * differently and correctly.
	 */
	public function test_unusable_date_query_column_fails_closed(): void {
		$this->setExpectedIncorrectUsage( 'wc_get_orders' );

		OrderHelper::create_order();

		$result = wc_get_orders(
			array(
				'date_query' => array(
					array(
						'column' => new \stdClass(),
						'year'   => 2024,
					),
				),
				'return'     => 'ids',
				'limit'      => -1,
			)
		);

		$this->assertSame( array(), $result, 'An unusable column must fail the query closed.' );
	}

	/**
	 * @testDox A date_query the guard must reject returns nothing, shared with the legacy suite.
	 *
	 * @dataProvider provider_date_query_must_fail_closed
	 *
	 * @param array $date_query The clause under test.
	 */
	public function test_shared_date_query_must_fail_closed( array $date_query ): void {
		// Also asserts the notice fires: WP fails the test both if an undeclared
		// incorrect-usage notice is raised and if a declared one never is.
		$this->setExpectedIncorrectUsage( 'wc_get_orders' );

		$order = OrderHelper::create_order();
		$order->set_date_created( '2024-06-01' );
		$order->save();

		$result = wc_get_orders(
			array(
				'date_query' => $date_query,
				'return'     => 'ids',
				'limit'      => -1,
				'status'     => 'any',
			)
		);

		$this->assertSame( array(), $result, 'An unusable date_query must fail closed rather than widen the query.' );
	}
}
