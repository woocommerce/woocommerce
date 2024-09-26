<?php
/**
 * Reports order stats tests.
 *
 * @package WooCommerce\Admin\Tests\Orders
 */

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query as OrdersStatsQuery;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * Class WC_Admin_Tests_Reports_Orders_Stats
 * Excluding it from code coverage until we refactor this test.
 * @group run-in-separate-process
 */
class WC_Admin_Tests_Reports_Orders_Stats extends WC_Unit_Test_Case {
	/**
	 * Don't cache report data during these tests.
	 */
	public static function setUpBeforeClass(): void {
		add_filter( 'woocommerce_analytics_report_should_use_cache', '__return_false' );
	}

	/**
	 * Restore cache for other tests.
	 */
	public static function tearDownAfterClass(): void {
		remove_filter( 'woocommerce_analytics_report_should_use_cache', '__return_false' );
	}

	/**
	 * Test the calculations and querying works correctly for the base case of 1 order.
	 *
	 * @since 3.5.0
	 */
	public function test_populate_and_query() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$coupon = WC_Helper_Coupon::create_coupon( 'test-coupon' );
		$coupon->set_amount( 20 );
		$coupon->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->apply_coupon( $coupon );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		$refund = wc_create_refund(
			array(
				'amount'   => 12,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => 1,
				'num_items_sold'      => 4,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 68,
				'total_sales'         => 85,
				'gross_sales'         => 100,
				'coupons'             => 20,
				'coupons_count'       => 1,
				'refunds'             => 12,
				'taxes'               => 7,
				'shipping'            => 10,
				'net_revenue'         => 68,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'total_sales'         => 85,
						'gross_sales'         => 100,
						'net_revenue'         => 68,
						'coupons'             => 20,
						'coupons_count'       => 1,
						'shipping'            => 10,
						'taxes'               => 7,
						'refunds'             => 12,
						'orders_count'        => 1,
						'num_items_sold'      => 4,
						'avg_items_per_order' => 4,
						'avg_order_value'     => 68,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		// Test retrieving the stats from the data store.
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true ) );

		// Test retrieving the stats through the query class.
		$query          = new OrdersStatsQuery( $args );
		$expected_stats = array(
			'totals'    => array(
				'net_revenue'         => 68,
				'avg_order_value'     => 68,
				'orders_count'        => 1,
				'avg_items_per_order' => 4,
				'num_items_sold'      => 4,
				'coupons'             => 20,
				'coupons_count'       => 1,
				'total_customers'     => 1,
				'products'            => '1',
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'net_revenue'         => 68,
						'avg_order_value'     => 68,
						'orders_count'        => 1,
						'avg_items_per_order' => 4,
						'num_items_sold'      => 4,
						'coupons'             => 20,
						'coupons_count'       => 1,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $query->get_data() ), true ) );
	}

	/**
	 * Test that querying statuses includes the default or query-specific statuses.
	 */
	public function test_populate_and_query_statuses() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order_types = array(
			array(
				'status' => 'refunded',
				'total'  => 50,
			),
			array(
				'status' => 'completed',
				'total'  => 100,
			),
			array(
				'status' => 'failed',
				'total'  => 75,
			),
		);

		$time = time();

		foreach ( $order_types as $order_type ) {
			$order = WC_Helper_Order::create_order( 1, $product );
			$order->set_status( $order_type['status'] );
			$order->set_total( $order_type['total'] );
			$order->set_date_created( $time );
			$order->set_date_paid( $time );
			$order->set_shipping_total( 0 );
			$order->set_cart_tax( 0 );
			$order->save();
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		// Query default statuses that should not include excluded or refunded order statuses.
		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => 2,
				'num_items_sold'      => 8,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 50,
				'total_sales'         => 100,
				'gross_sales'         => 150,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 50,
				'taxes'               => 0,
				'shipping'            => 0,
				'net_revenue'         => 100,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'total_sales'         => 100,
						'gross_sales'         => 150,
						'net_revenue'         => 100,
						'coupons'             => 0,
						'coupons_count'       => 0,
						'shipping'            => 0,
						'taxes'               => 0,
						'refunds'             => 50,
						'orders_count'        => 2,
						'num_items_sold'      => 8,
						'avg_items_per_order' => 4,
						'avg_order_value'     => 50,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true ) );

		// Query an excluded status which should still return orders with the queried status.
		$args           = array(
			'interval'  => 'hour',
			'after'     => $start_time,
			'before'    => $end_time,
			'status_is' => array( 'failed' ),
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => 1,
				'num_items_sold'      => 4,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 75,
				'total_sales'         => 75,
				'gross_sales'         => 75,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => 0,
				'net_revenue'         => 75,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'total_sales'         => 75,
						'gross_sales'         => 75,
						'net_revenue'         => 75,
						'coupons'             => 0,
						'coupons_count'       => 0,
						'shipping'            => 0,
						'taxes'               => 0,
						'refunds'             => 0,
						'orders_count'        => 1,
						'num_items_sold'      => 4,
						'avg_items_per_order' => 4,
						'avg_order_value'     => 75,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true ) );
	}

	/**
	 * Test refund type filtering.
	 */
	public function test_populate_and_query_refunds() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order_types = array(
			array(
				'status' => 'refunded',
				'total'  => 50,
			),
			array(
				'status' => 'completed',
				'total'  => 100,
			),
			array(
				'status' => 'completed',
				'total'  => 75,
			),
		);

		$time = time();

		foreach ( $order_types as $order_type ) {
			$order = WC_Helper_Order::create_order( 1, $product );
			$order->set_status( $order_type['status'] );
			$order->set_total( $order_type['total'] );
			$order->set_date_created( $time );
			$order->set_date_paid( $time );
			$order->set_shipping_total( 0 );
			$order->set_cart_tax( 0 );
			$order->save();
		}

		// Add a partial refund on the last order.
		$refund = wc_create_refund(
			array(
				'amount'   => 10,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		// Query all refunds.
		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
			'refunds'  => 'all',
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => 0,
				'num_items_sold'      => 0,
				'avg_items_per_order' => 0,
				'avg_order_value'     => 0,
				'total_sales'         => -60,
				'gross_sales'         => 0,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 60,
				'taxes'               => 0,
				'shipping'            => 0,
				'net_revenue'         => -60,
				'total_customers'     => 1,
				'products'            => 0,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'total_sales'         => -60,
						'gross_sales'         => 0,
						'net_revenue'         => -60,
						'coupons'             => 0,
						'coupons_count'       => 0,
						'shipping'            => 0,
						'taxes'               => 0,
						'refunds'             => 60,
						'orders_count'        => 0,
						'num_items_sold'      => 0,
						'avg_items_per_order' => 0,
						'avg_order_value'     => 0,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true ) );

		// Query all non-refunds.
		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
			'refunds'  => 'none',
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => 3,
				'num_items_sold'      => 12,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 75,
				'total_sales'         => 225,
				'gross_sales'         => 225,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => 0,
				'net_revenue'         => 225,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'total_sales'         => 225,
						'gross_sales'         => 225,
						'net_revenue'         => 225,
						'coupons'             => 0,
						'coupons_count'       => 0,
						'shipping'            => 0,
						'taxes'               => 0,
						'refunds'             => 0,
						'orders_count'        => 3,
						'num_items_sold'      => 12,
						'avg_items_per_order' => 4,
						'avg_order_value'     => 75,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true ) );

		// Query partial refunds.
		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
			'refunds'  => 'partial',
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => 0,
				'num_items_sold'      => 0,
				'avg_items_per_order' => 0,
				'avg_order_value'     => 0,
				'total_sales'         => -10,
				'gross_sales'         => 0,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 10,
				'taxes'               => 0,
				'shipping'            => 0,
				'net_revenue'         => -10,
				'total_customers'     => 1,
				'products'            => 0,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'total_sales'         => -10,
						'gross_sales'         => 0,
						'net_revenue'         => -10,
						'coupons'             => 0,
						'coupons_count'       => 0,
						'shipping'            => 0,
						'taxes'               => 0,
						'refunds'             => 10,
						'orders_count'        => 0,
						'num_items_sold'      => 0,
						'avg_items_per_order' => 0,
						'avg_order_value'     => 0,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true ) );

		// Query full refunds.
		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
			'refunds'  => 'full',
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => 0,
				'num_items_sold'      => 0,
				'avg_items_per_order' => 0,
				'avg_order_value'     => 0,
				'total_sales'         => -50,
				'gross_sales'         => 0,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 50,
				'taxes'               => 0,
				'shipping'            => 0,
				'net_revenue'         => -50,       // @todo - does this value make sense?
				'total_customers'     => 1,
				'products'            => 0,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getOffsetTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'total_sales'         => -50,
						'gross_sales'         => 0,
						'net_revenue'         => -50,
						'coupons'             => 0,
						'coupons_count'       => 0,
						'shipping'            => 0,
						'taxes'               => 0,
						'refunds'             => 50,
						'orders_count'        => 0,
						'num_items_sold'      => 0,
						'avg_items_per_order' => 0,
						'avg_order_value'     => 0,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true ) );
	}

	/**
	 * Test the calculation of multiple coupons on orders.
	 */
	public function test_populate_and_query_multiple_coupons() {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		// Create a product and customer.
		$customer      = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );
		$product_price = 23.45;
		$product       = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( $product_price );
		$product->save();

		// create 3 coupons valued 1,2,3.
		$coupons = array();
		foreach ( range( 1, 3 ) as $amount ) {
			$coupon = WC_Helper_Coupon::create_coupon( 'coupon_' . $amount );
			$coupon->set_amount( $amount );
			$coupon->save();
			$coupons[ $amount ] = $coupon;
		}

		// Create 3 orders with 1, 2, 3 coupons applied respectively.
		$minute_ago        = time() - MINUTE_IN_SECONDS;
		$report_start_time = $minute_ago - ( $minute_ago % HOUR_IN_SECONDS );
		$order_time        = $report_start_time + 1;
		$applied_coupons   = 0;
		$applied_amount    = 0;
		$orders_total      = 0;
		$orders            = array();

		foreach ( range( 1, 3 ) as $order_number ) {
			$order = WC_Helper_Order::create_order( $customer->get_id(), $product );
			$order->set_date_created( $order_time++ );
			$order->set_status( 'completed' );

			foreach ( $coupons as $amount => $coupon ) {
				if ( $amount >= $order_number ) {
					$order->apply_coupon( $coupon );
					$applied_coupons++;
					$applied_amount += $amount;
				}
			}

			$order->calculate_totals();
			$orders_total += $order->get_total();
			$order->save();

			$orders[] = $order;
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		// Test for the current hour.
		$current_hour_start = new DateTime();
		$current_hour_start->setTimestamp( $report_start_time );

		$current_hour_end = new DateTime();
		$current_hour_end->setTimestamp( $report_start_time + HOUR_IN_SECONDS - 1 );

		$query_args = array(
			'after'    => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
		);

		$order_shipping  = 10; // Hardcoded in WC_Helper_Order::create_order.
		$qty_per_product = 4; // Hardcoded in WC_Helper_Order::create_order.
		$total_customers = 1;
		$orders_count    = count( $orders );
		$coupons_count   = count( $coupons );
		$num_items_sold  = $orders_count * $qty_per_product;
		$shipping        = $orders_count * $order_shipping;
		$net_revenue     = $orders_total - $shipping;
		$gross_sales     = $product_price * $num_items_sold;
		$subtotals       = array(
			'orders_count'        => $orders_count,
			'num_items_sold'      => $num_items_sold,
			'total_sales'         => $orders_total,
			'gross_sales'         => $gross_sales,
			'coupons'             => $applied_amount,
			'coupons_count'       => $coupons_count,
			'refunds'             => 0,
			'taxes'               => 0,
			'shipping'            => $shipping,
			'net_revenue'         => $net_revenue,
			'avg_items_per_order' => $num_items_sold / $orders_count,
			'avg_order_value'     => $net_revenue / $orders_count,
			'total_customers'     => $total_customers,
			'segments'            => array(),
		);
		$totals          = array_merge( $subtotals, array( 'products' => 1 ) );

		$expected_stats = array(
			'totals'    => $totals,
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => $subtotals,
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );
	}

	/**
	 * Test the calculations and querying works correctly for the case of multiple orders.
	 */
	public function test_populate_and_query_multiple_intervals() {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		// 4 different products.
		$product_1_price = 25;
		$product_1       = new WC_Product_Simple();
		$product_1->set_name( 'Test Product' );
		$product_1->set_regular_price( $product_1_price );
		$product_1->save();

		$product_2_price = 10;
		$product_2       = new WC_Product_Simple();
		$product_2->set_name( 'Test Product 2' );
		$product_2->set_regular_price( $product_2_price );
		$product_2->save();

		$product_3_price = 13;
		$product_3       = new WC_Product_Simple();
		$product_3->set_name( 'Test Product 3' );
		$product_3->set_regular_price( $product_3_price );
		$product_3->save();

		$product_4_price = 1;
		$product_4       = new WC_Product_Simple();
		$product_4->set_name( 'Test Product 4' );
		$product_4->set_regular_price( $product_4_price );
		$product_4->save();

		// 2 different coupons
		$coupon_1_amount = 1; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		$coupon_2_amount = 2;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		$order_status_1 = 'completed';
		$order_status_2 = 'processing';

		$customer_1 = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'cust_2', 'pwd_2', 'user_2@mail.com' );

		$order_1_datetime = new DateTime();
		$order_1_hour     = (int) $order_1_datetime->format( 'H' );
		$order_1_datetime->setTime( $order_1_hour, 10, 0 ); // Set a time near the top of the hour.
		$order_1_time = $order_1_datetime->format( 'U' );

		// One more order needs to fit into the same hour, but also be one second later than this one.
		$order_2_time = $order_1_time + 1;

		$this_['hour']  = array( 1, 2 );
		$this_['day']   = array( 1, 2 );
		$this_['week']  = array( 1, 2 );
		$this_['month'] = array( 1, 2 );
		$this_['year']  = array( 1, 2 );

		$order[1]['year']  = (int) $order_1_datetime->format( 'Y' );
		$order[1]['month'] = (int) $order_1_datetime->format( 'm' );
		$order[1]['week']  = (int) $order_1_datetime->format( 'W' );
		$order[1]['day']   = (int) $order_1_datetime->format( 'd' );
		$order[1]['hour']  = (int) $order_1_datetime->format( 'H' );

		// same day, different hour.
		$order_3_datetime  = new DateTime();
		$order_3_datetime  = $order_3_datetime->setTimestamp( $order_1_time - HOUR_IN_SECONDS );
		$order_3_time      = $order_3_datetime->format( 'U' );
		$order[3]['year']  = (int) $order_3_datetime->format( 'Y' );
		$order[3]['month'] = (int) $order_3_datetime->format( 'm' );
		$order[3]['week']  = (int) $order_3_datetime->format( 'W' );
		$order[3]['day']   = (int) $order_3_datetime->format( 'd' );

		// Previous day.
		$order_4_datetime  = new DateTime();
		$order_4_datetime  = $order_4_datetime->setTimestamp( $order_1_time - DAY_IN_SECONDS );
		$order_4_time      = $order_4_datetime->format( 'U' );
		$order[4]['year']  = (int) $order_4_datetime->format( 'Y' );
		$order[4]['month'] = (int) $order_4_datetime->format( 'm' );
		$order[4]['week']  = (int) $order_4_datetime->format( 'W' );
		$order[4]['day']   = (int) $order_4_datetime->format( 'd' );

		// Previous week.
		$order_5_datetime  = new DateTime();
		$order_5_datetime  = $order_5_datetime->setTimestamp( $order_1_time - WEEK_IN_SECONDS );
		$order_5_time      = $order_5_datetime->format( 'U' );
		$order[5]['year']  = (int) $order_5_datetime->format( 'Y' );
		$order[5]['month'] = (int) $order_5_datetime->format( 'm' );
		$order[5]['week']  = (int) $order_5_datetime->format( 'W' );
		$order[5]['day']   = (int) $order_5_datetime->format( 'd' );

		// Previous month.
		$order_6_datetime  = new DateTime();
		$order_6_datetime  = $order_6_datetime->setTimestamp( $order_1_time - MONTH_IN_SECONDS );
		$order_6_time      = $order_6_datetime->format( 'U' );
		$order[6]['year']  = (int) $order_6_datetime->format( 'Y' );
		$order[6]['month'] = (int) $order_6_datetime->format( 'm' );
		$order[6]['week']  = (int) $order_6_datetime->format( 'W' );
		$order[6]['day']   = (int) $order_6_datetime->format( 'd' );

		// Previous year.
		$order_7_datetime  = new DateTime();
		$order_7_datetime  = $order_7_datetime->setTimestamp( $order_1_time - YEAR_IN_SECONDS );
		$order_7_time      = $order_7_datetime->format( 'U' );
		$order[7]['year']  = (int) $order_7_datetime->format( 'Y' );
		$order[7]['month'] = (int) $order_7_datetime->format( 'm' );
		$order[7]['week']  = (int) $order_7_datetime->format( 'W' );
		$order[7]['day']   = (int) $order_7_datetime->format( 'd' );

		foreach ( array( 3, 4, 5, 6, 7 ) as $order_no ) {
			if ( $order[ $order_no ]['day'] === $order[1]['day'] && $order[ $order_no ]['month'] === $order[1]['month'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$this_['day'][] = $order_no;
			}
			if ( $order[ $order_no ]['week'] === $order[1]['week'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$this_['week'][] = $order_no;
			}
			if ( $order[ $order_no ]['month'] === $order[1]['month'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$this_['month'][] = $order_no;
			}
			if ( $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$this_['year'][] = $order_no;
			}
		}

		$orders = array();
		// 2 different order statuses, plus new vs returning customer
		$qty_per_product = 4; // Hardcoded in WC_Helper_Order::create_order.
		$iterations      = 1;

		foreach ( array( $product_1, $product_2, $product_3 ) as $product ) {
			foreach ( array( null, $coupon_1, $coupon_2 ) as $coupon ) {
				foreach ( array( $order_status_1, $order_status_2 ) as $order_status ) {
					foreach ( array( $customer_1, $customer_2 ) as $customer ) {
						foreach (
							array(
								$order_1_time,
								$order_2_time,
							) as $order_time
						) { // As there are no tests for different timeframes, ignore these for now: $order_3_time, $order_4_time, $order_5_time, $order_6_time, $order_7_time
							// One order with only 1 product.
							$order = WC_Helper_Order::create_order( $customer->get_id(), $product );
							$order->set_date_created( $order_time + $iterations++ ); // offset each order by 1 second.
							$order->set_status( $order_status );

							if ( $coupon ) {
								$order->apply_coupon( $coupon );
							}

							$order->calculate_totals();
							$order->save();

							$orders[] = $order;

							// One order with 2 products: product_4 and selected product.
							$order_2 = WC_Helper_Order::create_order( $customer->get_id(), $product_4 );

							$item = new WC_Order_Item_Product();
							$item->set_props(
								array(
									'product'  => $product,
									'quantity' => 4,
									'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
									'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 4 ) ),
								)
							);
							$item->save();
							$order_2->add_item( $item );
							$order_2->set_date_created( $order_time + $iterations++ ); // offset each order by 1 second.
							$order_2->set_status( $order_status );

							if ( $coupon ) {
								$order_2->apply_coupon( $coupon );
							}

							$order_2->calculate_totals();
							$order_2->save();

							$orders[] = $order_2;
						}
					}
				}
			}
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		// Tests for before & after set to current hour.
		$current_hour_start = new DateTime();
		$current_hour_start->setTimestamp( $order_1_time );
		$current_hour_minutes = (int) $current_hour_start->format( 'i' );
		$current_hour_start->setTimestamp( $order_1_time - $current_hour_minutes * MINUTE_IN_SECONDS );

		$current_hour_end = new DateTime();
		$current_hour_end->setTimestamp( $order_1_time );
		$order_1_seconds = (int) $current_hour_end->format( 'U' ) % HOUR_IN_SECONDS;
		$current_hour_end->setTimestamp( $order_1_time + ( HOUR_IN_SECONDS - $order_1_seconds ) - 1 );

		// All orders, no filters.
		// 72 orders in one batch (3 products * 3 coupon options * 2 order statuses * 2 customers * 2 orders), 4 items of each product per order
		// 24 orders without coupons, 48 with coupons: 24 with $1 coupon and 24 with $2 coupon.
		// shipping is $10 per order.
		$query_args = array(
			'after'    => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
		);

		$order_permutations     = 72;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 24;

		$orders_count    = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold  = $orders_count / 2 * $qty_per_product + $orders_count / 2 * $qty_per_product * 2;
		$coupons         = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count   = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping        = $orders_count * 10;
		$net_revenue     = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales     = $net_revenue + $shipping;
		$gross_sales     = $net_revenue + $coupons;
		$total_customers = 2;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// * Order status filter
		// ** Status is, positive filter for 2 statuses, i.e. all orders.
		$query_args = array(
			'after'     => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'    => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'  => 'hour',
			'status_is' => array(
				$order_status_1,
				$order_status_2,
			),
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Status is, positive filter for 1 status -> half orders.
		$query_args = array(
			'after'     => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'    => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'  => 'hour',
			'status_is' => array(
				$order_status_1,
			),
		);

		$order_permutations     = 36;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 12;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Status is not, negative filter for 1 status -> half orders.
		$query_args = array(
			'after'         => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'        => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'      => 'hour',
			'status_is_not' => array(
				$order_status_2,
			),
		);

		$order_permutations     = 36;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 12;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Status is not, negative filter for 2 statuses -> no orders.
		$query_args = array(
			'after'         => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'        => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'      => 'hour',
			'status_is_not' => array(
				$order_status_1,
				$order_status_2,
			),
		);

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => 0,
				'num_items_sold'      => 0,
				'total_sales'         => 0,
				'gross_sales'         => 0,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => 0,
				'net_revenue'         => 0,
				'avg_items_per_order' => 0,
				'avg_order_value'     => 0,
				'total_customers'     => 0,
				'products'            => 0,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => 0,
						'num_items_sold'      => 0,
						'total_sales'         => 0,
						'gross_sales'         => 0,
						'coupons'             => 0,
						'coupons_count'       => 0,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => 0,
						'net_revenue'         => 0,
						'avg_items_per_order' => 0,
						'avg_order_value'     => 0,
						'total_customers'     => 0,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Status is + Status is not, positive filter for 2 statuses, negative for 1 -> half of orders.
		$query_args = array(
			'after'         => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'        => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'      => 'hour',
			'status_is'     => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not' => array(
				$order_status_2,
			),
		);

		$order_permutations     = 36;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 12;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// * Product filter
		// ** Product includes, positive filter for 2 products, i.e. 2 orders out of 3.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 16;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 4 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 3,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Product includes, positive filter for 1 product, 1/3 of orders
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_includes' => array(
				$product_3->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 8;
		$order_w_coupon_2_perms = 8;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_3_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 2,
				'segments'            => array(),
				// product 3 and product 4 (that is sometimes included in the orders with product 3).
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Product excludes, negative filter for 1 product, 2/3 of orders.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_excludes' => array(
				$product_1->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 16;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_3_price * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 3,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Product excludes, negative filter for 2 products, 1/3 of orders
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_excludes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 8;
		$order_w_coupon_2_perms = 8;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_3_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Product includes + product excludes, positive filter for 2 products, negative for 1 -> 1/3 of orders, only orders with product 2 and product 2 + product 4
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
			'product_excludes' => array(
				$product_1->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 8;
		$order_w_coupon_2_perms = 8;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_2_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// * Coupon filters
		// ** Coupon includes, positive filter for 2 coupons, i.e. 2/3 of orders.
		$query_args = array(
			'after'           => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'          => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_includes' => array(
				$coupon_1->get_id(),
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Coupon includes, positive filter for 1 coupon, 1/3 of orders
		$query_args = array(
			'after'           => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'          => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_includes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Coupon excludes, negative filter for 1 coupon, 2/3 of orders
		$query_args = array(
			'after'           => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'          => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_excludes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 0;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Coupon excludes, negative filter for 2 coupons, 1/3 of orders
		$query_args = array(
			'after'           => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'          => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_excludes' => array(
				$coupon_1->get_id(),
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 0;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Coupon includes + coupon excludes, positive filter for 2 coupon, negative for 1, 1/3 orders
		$query_args = array(
			'after'           => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'          => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'        => 'hour',
			'coupon_includes' => array(
				$coupon_1->get_id(),
				$coupon_2->get_id(),
			),
			'coupon_excludes' => array(
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 24;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// * Customer filters
		// ** Customer new
		$query_args = array(
			'after'         => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'        => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'      => 'hour',
			'customer_type' => 'new',
		);

		$orders_count   = 2;
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * $orders_count;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => 0,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => 0,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ** Customer returning
		$query_args = array(
			'after'         => $current_hour_start->format( TimeInterval::$sql_datetime_format ), // I don't think this makes sense.... gmdate( 'Y-m-d H:i:s', $orders[0]->get_date_created()->getOffsetTimestamp() + 1 ), // Date after initial order to get a returning customer.
			'before'        => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'      => 'hour',
			'customer_type' => 'returning',
		);

		$total_orders_count     = 144;
		$returning_orders_count = 2;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 24;

		$orders_count   = $total_orders_count - $returning_orders_count;
		$num_items_sold = $total_orders_count * 6 - ( $returning_orders_count * 4 );
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $total_orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $total_orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $total_orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $total_orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $total_orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $total_orders_count / 6 )
						- $product_1_price * $qty_per_product * $returning_orders_count
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => round( $num_items_sold / $orders_count, 4 ),
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $returning_orders_count,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
					'date_start_gmt' => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => round( $num_items_sold / $orders_count, 4 ),
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $returning_orders_count,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query};" );

		// Combinations: match all
		// status_is + product_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
		);

		$order_permutations     = 12;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 4;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is + coupon_includes.
		$query_args = array(
			'after'           => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'          => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'        => 'hour',
			'status_is'       => array(
				$order_status_1,
			),
			'coupon_includes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 12;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// product_includes + coupon_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 8;
		$order_w_coupon_1_perms = 8;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is + product_includes + coupon_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 4;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is + status_is_not + product_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 4;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is + status_is_not + product_includes + product_excludes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
			'product_excludes' => array(
				$product_4->get_id(),
			),
		);

		$order_permutations     = 12;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 4;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count * $qty_per_product; // No 2-item-orders here.
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				// Prod_1, status_1, no coupon orders included here, so 2 new cust orders.
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is + status_is_not + product_includes + product_excludes + coupon_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
			'product_excludes' => array(
				$product_4->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 4;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is + status_is_not + product_includes + product_excludes + coupon_includes + coupon_excludes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'status_is'        => array(
				$order_status_1,
				$order_status_2,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
				$product_2->get_id(),
			),
			'product_excludes' => array(
				$product_4->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
				$coupon_2->get_id(),
			),
			'coupon_excludes'  => array(
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 4;
		$order_w_coupon_1_perms = 4;
		$order_w_coupon_2_perms = 0;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 2 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 2 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 2,
				'products'            => 2,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 2,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// Combinations: match any
		// status_is + status_is_not, all orders.
		$query_args = array(
			'after'         => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'        => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'      => 'hour',
			'match'         => 'any',
			'status_is'     => array(
				$order_status_1,
			),
			'status_is_not' => array(
				$order_status_1,
			),
		);

		$order_permutations     = 72;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is OR product_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 16;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 4 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 8 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 8 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 4 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 8 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 8 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is OR coupon_includes.
		$query_args = array(
			'after'           => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'          => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'        => 'hour',
			'match'           => 'any',
			'status_is'       => array(
				$order_status_1,
			),
			'coupon_includes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 48;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 12;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is OR coupon_excludes.
		$query_args = array(
			'after'           => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'          => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'        => 'hour',
			'match'           => 'any',
			'status_is'       => array(
				$order_status_1,
			),
			'coupon_excludes' => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 60;
		$order_w_coupon_1_perms = 12;
		$order_w_coupon_2_perms = 24;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_2_price * $qty_per_product * ( $orders_count / 6 )
						+ $product_3_price * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count / 6 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// product_includes OR coupon_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 40;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 8;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 10 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 10 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 1 / 10 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 10 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 10 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 10 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is OR product_includes OR coupon_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 56;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 14 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 7 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 1 / 7 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 14 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 7 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 7 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is OR status_is_not OR product_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 56;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 16;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 14 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 7 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 1 / 7 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 14 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 7 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 7 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is OR status_is_not OR product_includes OR product_excludes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'product_excludes' => array(
				$product_2->get_id(),
			),
		);

		$order_permutations     = 60;
		$order_w_coupon_1_perms = 20;
		$order_w_coupon_2_perms = 20;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 1 / 5 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 10 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 1 / 5 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 5 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 10 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 5 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is OR status_is_not OR product_includes OR product_excludes OR coupon_includes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'product_excludes' => array(
				$product_2->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
		);

		$order_permutations     = 64;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 20;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 16 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 1 / 8 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 3 / 16 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 16 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 1 / 8 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 16 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// status_is OR status_is_not OR product_includes OR product_excludes OR coupon_includes OR coupon_excludes.
		$query_args = array(
			'after'            => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'           => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval'         => 'hour',
			'match'            => 'any',
			'status_is'        => array(
				$order_status_1,
			),
			'status_is_not'    => array(
				$order_status_2,
			),
			'product_includes' => array(
				$product_1->get_id(),
			),
			'product_excludes' => array(
				$product_2->get_id(),
			),
			'coupon_includes'  => array(
				$coupon_1->get_id(),
			),
			'coupon_excludes'  => array(
				$coupon_2->get_id(),
			),
		);

		$order_permutations     = 68;
		$order_w_coupon_1_perms = 24;
		$order_w_coupon_2_perms = 20;

		$orders_count   = count( $this_['hour'] ) * $order_permutations;
		$num_items_sold = $orders_count / 2 * $qty_per_product
						+ $orders_count / 2 * $qty_per_product * 2;
		$coupons        = count( $this_['hour'] ) * ( $order_w_coupon_1_perms * $coupon_1_amount + $order_w_coupon_2_perms * $coupon_2_amount );
		$coupons_count  = ( $order_w_coupon_1_perms ? 1 : 0 ) + ( $order_w_coupon_2_perms ? 1 : 0 );
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_1_price * $qty_per_product * ( $orders_count * 3 / 17 )
						+ $product_2_price * $qty_per_product * ( $orders_count * 5 / 34 )
						+ $product_3_price * $qty_per_product * ( $orders_count * 3 / 17 )
						+ ( $product_1_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 17 )
						+ ( $product_2_price + $product_4_price ) * $qty_per_product * ( $orders_count * 5 / 34 )
						+ ( $product_3_price + $product_4_price ) * $qty_per_product * ( $orders_count * 3 / 17 )
						- $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 4,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

	}

	/**
	 * Test if lookup tables are cleaned after delete an order.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::delete_order
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::sync_on_order_delete
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::sync_on_order_delete
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore::sync_on_order_delete
	 */
	public function test_order_deletion() {
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		// Tables to check.
		$tables = array(
			'wc_order_coupon_lookup',
			'wc_order_product_lookup',
			'wc_order_stats',
			'wc_order_tax_lookup',
		);

		// Enable taxes.
		$default_calc_taxes       = get_option( 'woocommerce_calc_taxes', 'no' );
		$default_customer_address = get_option( 'woocommerce_default_customer_address', 'geolocation' );
		$default_tax_based_on     = get_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'base' );

		// Create tax.
		$tax_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// Create product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Create coupon.
		$coupon = new WC_Coupon();
		$coupon->set_code( '20fixed' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 20 );
		$coupon->save();

		// Create order.
		$order = WC_Helper_Order::create_order();
		$order->add_product( $product, 1 );
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->apply_coupon( $coupon );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Check if lookup tables are populated.
		foreach ( $tables as $table ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}{$table} WHERE order_id = %d", // phpcs:ignore
					$order->get_id()
				)
			);

			$this->assertTrue( is_array( $results ) && (bool) count( $results ) );
		}

		// Delete order and clean all other objects too.
		$order->delete( true );
		$product->delete( true );
		$coupon->delete( true );
		WC_Tax::_delete_tax_rate( $tax_id );

		// Check if results are empty now.
		foreach ( $tables as $table ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}{$table} WHERE order_id = %d",  // phpcs:ignore
					$order->get_id()
				)
			);

			$this->assertEmpty( $results );
		}

		// Reset taxes settings.
		update_option( 'woocommerce_calc_taxes', $default_customer_address );
		update_option( 'woocommerce_default_customer_address', $default_calc_taxes );
		update_option( 'woocommerce_tax_based_on', $default_tax_based_on );
	}

	/**
	 * Test segmenting by product id and by variation id.
	 */
	public function test_segmenting_by_product_and_variation() {
		// Simple product.
		$product_1_price = 25;
		$product_1       = new WC_Product_Simple();
		$product_1->set_name( 'Simple Product' );
		$product_1->set_regular_price( $product_1_price );
		$product_1->save();

		// Variable product.
		$product_2 = new WC_Product_Variable();
		$product_2->set_name( 'Variable Product' );
		$product_2->save();

		$child_1 = new WC_Product_Variation();
		$child_1->set_parent_id( $product_2->get_id() );
		$child_1->set_regular_price( 23 );
		$child_1->save();

		$child_2 = new WC_Product_Variation();
		$child_2->set_parent_id( $product_2->get_id() );
		$child_2->set_regular_price( 27 );
		$child_2->save();

		$product_2->set_children( array( $child_1->get_id(), $child_2->get_id() ) );

		$child_1->set_stock_status( 'instock' );
		$child_1->save();
		$child_2->set_stock_status( 'instock' );
		$child_2->save();
		WC_Product_Variable::sync( $product_2 );

		// Simple product, not used.
		$product_3_price = 17;
		$product_3       = new WC_Product_Simple();
		$product_3->set_name( 'Simple Product not used' );
		$product_3->set_regular_price( $product_3_price );
		$product_3->save();

		$order_status = 'completed';

		$customer_1 = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );

		$order_1_time = time();
		$order_3_time = $order_1_time - 1 * HOUR_IN_SECONDS;

		// Order 3: 4 x product 1, done one hour earlier.
		$order_3 = WC_Helper_Order::create_order( $customer_1->get_id(), $product_1 );
		$order_3->set_date_created( $order_3_time );
		$order_3->set_date_paid( $order_3_time );
		$order_3->set_status( $order_status );
		$order_3->calculate_totals();
		$order_3->save();

		// Order 1: 4 x product 1 & 3 x product 2-child 1.
		$order_1 = WC_Helper_Order::create_order( $customer_1->get_id(), $product_1 );
		$item    = new WC_Order_Item_Product();

		$item->set_props(
			array(
				'product_id'   => $product_2->get_id(),
				'variation_id' => $child_1->get_id(),
				'quantity'     => 3,
				'subtotal'     => 3 * floatval( $child_1->get_price() ),
				'total'        => 3 * floatval( $child_1->get_price() ),
			)
		);
		$item->save();
		$order_1->add_item( $item );
		$order_1->set_status( $order_status );
		$order_1->calculate_totals();
		$order_1->save();

		// Order 2: 4 x product 2-child 1 & 1 x product 2-child 2.
		$order_2 = WC_Helper_Order::create_order( $customer_1->get_id(), $child_1 );
		$item    = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product_id'   => $product_2->get_id(),
				'variation_id' => $child_2->get_id(),
				'quantity'     => 1,
				'subtotal'     => floatval( $child_2->get_price() ),
				'total'        => floatval( $child_2->get_price() ),
			)
		);
		$item->save();
		$order_2->add_item( $item );
		$order_2->set_status( $order_status );
		$order_2->calculate_totals();
		$order_2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		// Tests for before & after set to current hour.
		$now = new DateTime();

		$two_hours_back     = new DateTime();
		$i1_start_timestamp = $order_1_time - 2 * HOUR_IN_SECONDS;
		$two_hours_back->setTimestamp( $i1_start_timestamp );
		$i1_end_timestamp = $i1_start_timestamp + ( 3600 - ( $i1_start_timestamp % 3600 ) ) - 1;
		$i1_start         = new DateTime();
		$i1_start->setTimestamp( $i1_start_timestamp );
		$i1_end = new DateTime();
		$i1_end->setTimestamp( $i1_end_timestamp );

		$i2_start_timestamp = $i1_end_timestamp + 1;
		$i2_end_timestamp   = $i1_end_timestamp + 3600;
		$i2_start           = new DateTime();
		$i2_start->setTimestamp( $i2_start_timestamp );
		$i2_end = new DateTime();
		$i2_end->setTimestamp( $i2_end_timestamp );

		$i3_start_timestamp = $i2_end_timestamp + 1;
		$i3_end_timestamp   = $now->format( 'U' );
		$i3_start           = new DateTime();
		$i3_start->setTimestamp( $i3_start_timestamp );
		$i3_end = new DateTime();
		$i3_end->setTimestamp( $i3_end_timestamp );

		$query_args = array(
			'after'     => $two_hours_back->format( TimeInterval::$sql_datetime_format ),
			'before'    => $now->format( TimeInterval::$sql_datetime_format ),
			'interval'  => 'hour',
			'segmentby' => 'product',
		);

		$shipping_amnt  = 10;
		$o1_net_revenue = 4 * $product_1_price + 3 * intval( $child_1->get_price() );
		$o2_net_revenue = 4 * intval( $child_1->get_price() ) + 1 * intval( $child_2->get_price() );
		$o3_net_revenue = 4 * $product_1_price;
		$o1_num_items   = 4 + 3;
		$o2_num_items   = 4 + 1;
		$o3_num_items   = 4;

		// Totals.
		$orders_count    = 3;
		$num_items_sold  = 7 + 5 + 4;
		$shipping        = $orders_count * $shipping_amnt;
		$net_revenue     = $o1_net_revenue + $o2_net_revenue + $o3_net_revenue;
		$total_sales     = $net_revenue + $shipping;
		$gross_sales     = $net_revenue;
		$total_customers = 1;
		// Totals segments.
		$p1_orders_count   = 2;
		$p1_num_items_sold = 8;
		$p1_shipping       = round( $shipping_amnt / $o1_num_items * 4, 6 ) + round( $shipping_amnt / $o3_num_items * 4, 6 );
		$p1_net_revenue    = 8 * $product_1_price;
		$p1_total_sales    = $p1_net_revenue + $p1_shipping;

		$p2_orders_count   = 2;
		$p2_num_items_sold = 8;
		$p2_shipping       = round( $shipping_amnt / $o1_num_items * 3, 6 ) + $shipping_amnt;
		$p2_net_revenue    = 7 * intval( $child_1->get_price() ) + 1 * intval( $child_2->get_price() );
		$p2_total_sales    = $p2_net_revenue + $p2_shipping;

		// Interval 3.
		// I3 Subtotals.
		$i3_tot_orders_count   = 2;
		$i3_tot_num_items_sold = 4 + 3 + 4 + 1;
		$i3_tot_shipping       = $i3_tot_orders_count * $shipping_amnt;
		$i3_tot_net_revenue    = 4 * $product_1_price + 7 * intval( $child_1->get_price() ) + 1 * intval( $child_2->get_price() );
		$i3_tot_total_sales    = $i3_tot_net_revenue + $i3_tot_shipping;

		// I3 Segments.
		$i3_p1_orders_count   = 1;
		$i3_p1_num_items_sold = 4;
		$i3_p1_shipping       = round( $shipping_amnt / $o1_num_items * 4, 6 );
		$i3_p1_net_revenue    = $i3_p1_num_items_sold * $product_1_price;
		$i3_p1_total_sales    = $i3_p1_net_revenue + $i3_p1_shipping;

		$i3_p2_orders_count   = 2;
		$i3_p2_num_items_sold = 8;
		$i3_p2_shipping       = round( $shipping_amnt / $o1_num_items * 3, 6 ) + $shipping_amnt;
		$i3_p2_net_revenue    = 7 * intval( $child_1->get_price() ) + 1 * intval( $child_2->get_price() );
		$i3_p2_total_sales    = $i3_p2_net_revenue + $i3_p2_shipping;

		// Interval 2
		// I2 Subtotals.
		$i2_tot_orders_count   = 1;
		$i2_tot_num_items_sold = 4;
		$i2_tot_shipping       = $i2_tot_orders_count * $shipping_amnt;
		$i2_tot_net_revenue    = 4 * $product_1_price;
		$i2_tot_total_sales    = $i2_tot_net_revenue + $i2_tot_shipping;

		// I2 Segments.
		$i2_p1_orders_count   = 1;
		$i2_p1_num_items_sold = 4;
		$i2_p1_shipping       = $shipping_amnt;
		$i2_p1_net_revenue    = 4 * $product_1_price;
		$i2_p1_total_sales    = $i2_p1_net_revenue + $i2_p1_shipping;

		$i2_p2_orders_count   = 0;
		$i2_p2_num_items_sold = 0;
		$i2_p2_shipping       = 0;
		$i2_p2_net_revenue    = 0;
		$i2_p2_total_sales    = $i2_p2_net_revenue + $i2_p2_shipping;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => round( $num_items_sold / $orders_count, 4 ),
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => $total_customers,
				'products'            => 2,
				'segments'            => array(
					array(
						'segment_id'    => $product_1->get_id(),
						'segment_label' => $product_1->get_name(),
						'subtotals'     => array(
							'orders_count'        => $p1_orders_count,
							'num_items_sold'      => $p1_num_items_sold,
							'total_sales'         => $p1_total_sales,
							'coupons'             => 0,
							'coupons_count'       => 0,
							'refunds'             => 0,
							'taxes'               => 0,
							'shipping'            => $p1_shipping,
							'net_revenue'         => $p1_net_revenue,
							'avg_items_per_order' => ( $o1_num_items + $o3_num_items ) / $p1_orders_count,
							'avg_order_value'     => ( $o1_net_revenue + $o3_net_revenue ) / $p1_orders_count,
							'total_customers'     => $total_customers,
						),
					),
					array(
						'segment_id'    => $product_2->get_id(),
						'segment_label' => $product_2->get_name(),
						'subtotals'     => array(
							'orders_count'        => $p2_orders_count,
							'num_items_sold'      => $p2_num_items_sold,
							'total_sales'         => $p2_total_sales,
							'coupons'             => 0,
							'coupons_count'       => 0,
							'refunds'             => 0,
							'taxes'               => 0,
							'shipping'            => $p2_shipping,
							'net_revenue'         => $p2_net_revenue,
							'avg_items_per_order' => ( $o1_num_items + $o2_num_items ) / $p2_orders_count,
							'avg_order_value'     => ( $o1_net_revenue + $o2_net_revenue ) / $p2_orders_count,
							'total_customers'     => $total_customers,
						),
					),
					array(
						'segment_id'    => $product_3->get_id(),
						'segment_label' => $product_3->get_name(),
						'subtotals'     => array(
							'orders_count'        => 0,
							'num_items_sold'      => 0,
							'total_sales'         => 0,
							'coupons'             => 0,
							'coupons_count'       => 0,
							'refunds'             => 0,
							'taxes'               => 0,
							'shipping'            => 0,
							'net_revenue'         => 0,
							'avg_items_per_order' => 0,
							'avg_order_value'     => 0,
							'total_customers'     => 0,
						),
					),
				),
			),
			'intervals' => array(
				array(
					'interval'       => $i3_start->format( 'Y-m-d H' ),
					'date_start'     => $i3_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $i3_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $i3_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $i3_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $i3_tot_orders_count,
						'num_items_sold'      => $i3_tot_num_items_sold,
						'total_sales'         => $i3_tot_total_sales,
						'gross_sales'         => $i3_tot_net_revenue, // no coupons or refunds.
						'coupons'             => 0,
						'coupons_count'       => 0,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $i3_tot_shipping,
						'net_revenue'         => $i3_tot_net_revenue,
						'avg_items_per_order' => $i3_tot_num_items_sold / $i3_tot_orders_count,
						'avg_order_value'     => $i3_tot_net_revenue / $i3_tot_orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(
							array(
								'segment_id'    => $product_1->get_id(),
								'segment_label' => $product_1->get_name(),
								'subtotals'     => array(
									'orders_count'        => $i3_p1_orders_count,
									'num_items_sold'      => $i3_p1_num_items_sold,
									'total_sales'         => $i3_p1_total_sales,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => $i3_p1_shipping,
									'net_revenue'         => $i3_p1_net_revenue,
									'avg_items_per_order' => $o1_num_items / $i3_p1_orders_count,
									'avg_order_value'     => $o1_net_revenue / $i3_p1_orders_count,
									'total_customers'     => $total_customers,
								),
							),
							array(
								'segment_id'    => $product_2->get_id(),
								'segment_label' => $product_2->get_name(),
								'subtotals'     => array(
									'orders_count'        => $i3_p2_orders_count,
									'num_items_sold'      => $i3_p2_num_items_sold,
									'total_sales'         => $i3_p2_total_sales,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => $i3_p2_shipping,
									'net_revenue'         => $i3_p2_net_revenue,
									'avg_items_per_order' => ( $o1_num_items + $o2_num_items ) / $i3_p2_orders_count,
									'avg_order_value'     => ( $o1_net_revenue + $o2_net_revenue ) / $i3_p2_orders_count,
									'total_customers'     => $total_customers,
								),
							),
							array(
								'segment_id'    => $product_3->get_id(),
								'segment_label' => $product_3->get_name(),
								'subtotals'     => array(
									'orders_count'        => 0,
									'num_items_sold'      => 0,
									'total_sales'         => 0,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => 0,
									'net_revenue'         => 0,
									'avg_items_per_order' => 0,
									'avg_order_value'     => 0,
									'total_customers'     => 0,
								),
							),
						),
					),
				),
				array(
					'interval'       => $i2_start->format( 'Y-m-d H' ),
					'date_start'     => $i2_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $i2_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $i2_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $i2_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $i2_tot_orders_count,
						'num_items_sold'      => $i2_tot_num_items_sold,
						'total_sales'         => $i2_tot_total_sales,
						'gross_sales'         => $i2_tot_net_revenue, // no coupons or refunds.
						'coupons'             => 0,
						'coupons_count'       => 0,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $i2_tot_shipping,
						'net_revenue'         => $i2_tot_net_revenue,
						'avg_items_per_order' => $i2_tot_num_items_sold / $i2_tot_orders_count,
						'avg_order_value'     => $i2_tot_net_revenue / $i2_tot_orders_count,
						'total_customers'     => $total_customers,
						'segments'            => array(
							array(
								'segment_id'    => $product_1->get_id(),
								'segment_label' => $product_1->get_name(),
								'subtotals'     => array(
									'orders_count'        => $i2_p1_orders_count,
									'num_items_sold'      => $i2_p1_num_items_sold,
									'total_sales'         => $i2_p1_total_sales,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => $i2_p1_shipping,
									'net_revenue'         => $i2_p1_net_revenue,
									'avg_items_per_order' => $o3_num_items / $i2_p1_orders_count,
									'avg_order_value'     => $o3_net_revenue / $i2_p1_orders_count,
									'total_customers'     => $total_customers,
								),
							),
							array(
								'segment_id'    => $product_2->get_id(),
								'segment_label' => $product_2->get_name(),
								'subtotals'     => array(
									'orders_count'        => $i2_p2_orders_count,
									'num_items_sold'      => $i2_p2_num_items_sold,
									'total_sales'         => $i2_p2_total_sales,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => $i2_p2_shipping,
									'net_revenue'         => $i2_p2_net_revenue,
									'avg_items_per_order' => $i2_p2_orders_count ? $o3_num_items / $i2_p2_orders_count : 0,
									'avg_order_value'     => $i2_p2_orders_count ? $o3_net_revenue / $i2_p2_orders_count : 0,
									'total_customers'     => $i2_p2_orders_count ? $total_customers : 0,
								),
							),
							array(
								'segment_id'    => $product_3->get_id(),
								'segment_label' => $product_3->get_name(),
								'subtotals'     => array(
									'orders_count'        => 0,
									'num_items_sold'      => 0,
									'total_sales'         => 0,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => 0,
									'net_revenue'         => 0,
									'avg_items_per_order' => 0,
									'avg_order_value'     => 0,
									'total_customers'     => 0,
								),
							),
						),
					),
				),
				array(
					'interval'       => $i1_start->format( 'Y-m-d H' ),
					'date_start'     => $i1_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $i1_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $i1_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $i1_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => 0,
						'num_items_sold'      => 0,
						'total_sales'         => 0,
						'gross_sales'         => 0,
						'coupons'             => 0,
						'coupons_count'       => 0,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => 0,
						'net_revenue'         => 0,
						'avg_items_per_order' => 0,
						'avg_order_value'     => 0,
						'total_customers'     => 0,
						'segments'            => array(
							array(
								'segment_id'    => $product_1->get_id(),
								'segment_label' => $product_1->get_name(),
								'subtotals'     => array(
									'orders_count'        => 0,
									'num_items_sold'      => 0,
									'total_sales'         => 0,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => 0,
									'net_revenue'         => 0,
									'avg_items_per_order' => 0,
									'avg_order_value'     => 0,
									'total_customers'     => 0,
								),
							),
							array(
								'segment_id'    => $product_2->get_id(),
								'segment_label' => $product_2->get_name(),
								'subtotals'     => array(
									'orders_count'        => 0,
									'num_items_sold'      => 0,
									'total_sales'         => 0,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => 0,
									'net_revenue'         => 0,
									'avg_items_per_order' => 0,
									'avg_order_value'     => 0,
									'total_customers'     => 0,
								),
							),
							array(
								'segment_id'    => $product_3->get_id(),
								'segment_label' => $product_3->get_name(),
								'subtotals'     => array(
									'orders_count'        => 0,
									'num_items_sold'      => 0,
									'total_sales'         => 0,
									'coupons'             => 0,
									'coupons_count'       => 0,
									'refunds'             => 0,
									'taxes'               => 0,
									'shipping'            => 0,
									'net_revenue'         => 0,
									'avg_items_per_order' => 0,
									'avg_order_value'     => 0,
									'total_customers'     => 0,
								),
							),
						),
					),
				),
			),
			'total'     => 3,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$actual = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );

		$this->assertEquals( $expected_stats, $actual, 'Segmenting by product, expected: ' . $this->return_print_r( $expected_stats ) . '; actual: ' . $this->return_print_r( $actual ) );
	}

	/**
	 * Test zero filling when ordering by date in descending and ascending order.
	 */
	public function test_zero_fill_order_by_date() {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$product_price = 11;
		$product       = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( $product_price );
		$product->save();

		$customer = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );

		$order_1_time = time();
		$order_2_time = $order_1_time;

		$order_during_this_['hour']  = array( 1, 2 );
		$order_during_this_['day']   = array( 1, 2 );
		$order_during_this_['week']  = array( 1, 2 );
		$order_during_this_['month'] = array( 1, 2 );
		$order_during_this_['year']  = array( 1, 2 );

		$order_1_datetime = new DateTime();
		$order_1_datetime = $order_1_datetime->setTimestamp( $order_1_time );

		// Previous year.
		$order_7_datetime  = new DateTime();
		$order_7_datetime  = $order_7_datetime->setTimestamp( $order_1_time - YEAR_IN_SECONDS );
		$order_7_time      = $order_7_datetime->format( 'U' );
		$order[7]['year']  = (int) $order_7_datetime->format( 'Y' );
		$order[7]['month'] = (int) $order_7_datetime->format( 'm' );
		$order[7]['week']  = (int) $order_7_datetime->format( 'W' );
		$order[7]['day']   = (int) $order_7_datetime->format( 'd' );

		// Previous month.
		$order_6_datetime  = new DateTime();
		$order_6_datetime  = $order_6_datetime->setTimestamp( $order_1_time - MONTH_IN_SECONDS );
		$order_6_time      = $order_6_datetime->format( 'U' );
		$order[6]['year']  = (int) $order_6_datetime->format( 'Y' );
		$order[6]['month'] = (int) $order_6_datetime->format( 'm' );
		$order[6]['week']  = (int) $order_6_datetime->format( 'W' );
		$order[6]['day']   = (int) $order_6_datetime->format( 'd' );

		// Previous week.
		$order_5_datetime  = new DateTime();
		$order_5_datetime  = $order_5_datetime->setTimestamp( $order_1_time - WEEK_IN_SECONDS );
		$order_5_time      = $order_5_datetime->format( 'U' );
		$order[5]['year']  = (int) $order_5_datetime->format( 'Y' );
		$order[5]['month'] = (int) $order_5_datetime->format( 'm' );
		$order[5]['week']  = (int) $order_5_datetime->format( 'W' );
		$order[5]['day']   = (int) $order_5_datetime->format( 'd' );

		// Previous day.
		$order_4_datetime  = new DateTime();
		$order_4_datetime  = $order_4_datetime->setTimestamp( $order_1_time - DAY_IN_SECONDS );
		$order_4_time      = $order_4_datetime->format( 'U' );
		$order[4]['year']  = (int) $order_4_datetime->format( 'Y' );
		$order[4]['month'] = (int) $order_4_datetime->format( 'm' );
		$order[4]['week']  = (int) $order_4_datetime->format( 'W' );
		$order[4]['day']   = (int) $order_4_datetime->format( 'd' );

		// same day, -1 hour.
		$order_3_datetime  = new DateTime();
		$order_3_datetime  = $order_3_datetime->setTimestamp( $order_1_time - HOUR_IN_SECONDS );
		$order_3_time      = $order_3_datetime->format( 'U' );
		$order[3]['year']  = (int) $order_3_datetime->format( 'Y' );
		$order[3]['month'] = (int) $order_3_datetime->format( 'm' );
		$order[3]['week']  = (int) $order_3_datetime->format( 'W' );
		$order[3]['day']   = (int) $order_3_datetime->format( 'd' );

		// Current order.
		$order[1]['year']  = (int) $order_1_datetime->format( 'Y' );
		$order[1]['month'] = (int) $order_1_datetime->format( 'm' );
		$order[1]['week']  = (int) $order_1_datetime->format( 'W' );
		$order[1]['day']   = (int) $order_1_datetime->format( 'd' );
		$order[1]['hour']  = (int) $order_1_datetime->format( 'H' );

		foreach ( array( 3, 4, 5, 6, 7 ) as $order_no ) {
			if ( $order[ $order_no ]['day'] === $order[1]['day'] && $order[ $order_no ]['month'] === $order[1]['month'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$order_during_this_['day'][] = $order_no;
			}
			if ( $order[ $order_no ]['week'] === $order[1]['week'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$order_during_this_['week'][] = $order_no;
			}
			if ( $order[ $order_no ]['month'] === $order[1]['month'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$order_during_this_['month'][] = $order_no;
			}
			if ( $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$order_during_this_['year'][] = $order_no;
			}
		}

		$order_status    = 'completed';
		$qty_per_product = 4; // Hardcoded in WC_Helper_Order::create_order.

		$orders = array();
		foreach (
			array(
				$order_7_time,
				$order_6_time,
				$order_5_time,
				$order_4_time,
				$order_3_time,
				$order_2_time,
				$order_1_time,
			) as $order_time
		) {
			// Order with 1 product.
			$order = WC_Helper_Order::create_order( $customer->get_id(), $product );
			$order->set_date_created( $order_time );
			$order->set_date_paid( $order_time );
			$order->set_status( $order_status );

			$order->calculate_totals();
			$order->save();

			$orders[] = $order;
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		// Tests for before & after set to current hour.
		$current_hour_start = new DateTime();
		$current_hour_start->setTimestamp( $order_1_time );
		$current_hour_minutes = (int) $current_hour_start->format( 'i' );
		$current_hour_start->setTimestamp( $order_1_time - $current_hour_minutes * MINUTE_IN_SECONDS );

		$current_hour_end = new DateTime();
		$current_hour_end->setTimestamp( $order_1_time );
		$seconds_into_hour = (int) $current_hour_end->format( 'U' ) % HOUR_IN_SECONDS;
		$current_hour_end->setTimestamp( $order_1_time + ( HOUR_IN_SECONDS - $seconds_into_hour ) - 1 );

		// Test for current hour--only 1 hour visible.
		// DESC.
		$query_args = array(
			'after'    => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'desc',
		);

		$orders_count   = count( $order_during_this_['hour'] );
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$coupons_count  = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_price * $qty_per_product * $orders_count - $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ASC -- only 1 interval, so should be the same.
		$query_args = array(
			'after'    => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'asc',
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * Test for [-5 hours, now] -- partial 1 page.
		 * bottom            ...                  top
		 * | --------------------|------|-----|-----|
		 * minus 5 hours         |      |   order1  now
		 *                       |      |   order2
		 *                       |  now-1 hour
		 *                     order 3
		 */
		// DESC.
		$hour_offset   = 5;
		$minus_5_hours = new DateTime();
		$now_timestamp = (int) $current_hour_end->format( 'U' );
		$minus_5_hours->setTimestamp( $now_timestamp - $hour_offset * HOUR_IN_SECONDS );

		$query_args = array(
			'after'    => $minus_5_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'desc',
		);

		// Expected Intervals section construction.
		$expected_intervals = array();
		// Even in case this runs exactly at the hour turn second, there should still be 6 intervals:
		// e.g. 20:30:51 -(minus 5 hours)- 15:30:51 means intervals 15:30:51--15:59:59, 16:00-16:59, 17, 18, 19, 20:00-20:30, i.e. 6 intervals
		// also if this run exactly at 20:00 -(minus 5 hours)- 15:00, then intervals should be 15:00-15:59, 16, 17, 18, 19, 20:00-20:00.
		$interval_count = $hour_offset + 1;
		for ( $i = 0; $i < $interval_count; $i ++ ) {
			if ( 0 === $i ) {
				$date_start = new DateTime( $current_hour_end->format( 'Y-m-d H:00:00' ) );
				$date_end   = $current_hour_end;
			} elseif ( $hour_offset === $i ) {
				$date_start = $minus_5_hours;
				$date_end   = new DateTime( $minus_5_hours->format( 'Y-m-d H:59:59' ) );
			} else {
				$hour_anchor = new DateTime();
				$hour_anchor->setTimestamp( $now_timestamp - $i * HOUR_IN_SECONDS );
				$date_start = new DateTime( $hour_anchor->format( 'Y-m-d H:00:00' ) );
				$date_end   = new DateTime( $hour_anchor->format( 'Y-m-d H:59:59' ) );
			}

			if ( 0 === $i ) {
				$orders_count        = count( $order_during_this_['hour'] );
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} elseif ( 1 === $i ) {
				$orders_count        = 1;
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} else {
				$orders_count        = 0;
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 0;
			}

			$expected_interval = array(
				'interval'       => $date_start->format( 'Y-m-d H' ),
				'date_start'     => $date_start->format( 'Y-m-d H:i:s' ),
				'date_start_gmt' => $date_start->format( 'Y-m-d H:i:s' ),
				'date_end'       => $date_end->format( 'Y-m-d H:i:s' ),
				'date_end_gmt'   => $date_end->format( 'Y-m-d H:i:s' ),
				'subtotals'      => array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $total_sales,
					'gross_sales'         => $gross_sales,
					'coupons'             => $coupons,
					'coupons_count'       => $coupons_count,
					'refunds'             => 0,
					'taxes'               => 0,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => 0 === $orders_count ? 0 : $num_items_sold / $orders_count,
					'avg_order_value'     => 0 === $orders_count ? 0 : $net_revenue / $orders_count,
					'total_customers'     => $returning_customers,
					'segments'            => array(),
				),
			);

			$expected_intervals[] = $expected_interval;
		}

		// Totals section.
		$orders_count   = count( $order_during_this_['hour'] ) + 1; // order 3 is 1 hour before order 1 & 2, so include it.
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_price * $qty_per_product * $orders_count - $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => $expected_intervals,
			'total'     => $interval_count,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$actual         = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ASC -- reverse the intervals array, but numbers stay the same.
		$query_args                  = array(
			'after'    => $minus_5_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'asc',
		);
		$expected_stats['intervals'] = array_reverse( $expected_stats['intervals'] );

		$actual = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * Test for [-9 hours, now] -- full 1 page.
		 * DESC:
		 * bottom         ...                     top
		 * | --------------------|------|-----|-----|
		 * minus 9 hours         |      |   order1  now
		 *                       |      |   order2
		 *                       |  now-1 hour
		 *                     order 3
		 */
		$hour_offset   = 9;
		$minus_9_hours = new DateTime();
		$now_timestamp = (int) $current_hour_end->format( 'U' );
		$minus_9_hours->setTimestamp( $now_timestamp - $hour_offset * HOUR_IN_SECONDS );

		$query_args = array(
			'after'    => $minus_9_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'desc',
		);

		// Expected Intervals section construction.
		$expected_intervals = array();
		$interval_count     = $hour_offset + 1;
		for ( $i = 0; $i < $interval_count; $i ++ ) {
			if ( 0 === $i ) {
				$date_start = new DateTime( $current_hour_end->format( 'Y-m-d H:00:00' ) );
				$date_end   = $current_hour_end;
			} elseif ( $hour_offset === $i ) {
				$date_start = $minus_9_hours;
				$date_end   = new DateTime( $minus_9_hours->format( 'Y-m-d H:59:59' ) );
			} else {
				$hour_anchor = new DateTime();
				$hour_anchor->setTimestamp( $now_timestamp - $i * HOUR_IN_SECONDS );
				$date_start = new DateTime( $hour_anchor->format( 'Y-m-d H:00:00' ) );
				$date_end   = new DateTime( $hour_anchor->format( 'Y-m-d H:59:59' ) );
			}

			if ( 0 === $i ) {
				$orders_count    = count( $order_during_this_['hour'] );
				$num_items_sold  = $orders_count * $qty_per_product;
				$coupons         = 0;
				$shipping        = $orders_count * 10;
				$net_revenue     = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales     = $net_revenue + $shipping;
				$gross_sales     = $net_revenue + $coupons;
				$total_customers = 1;
			} elseif ( 1 === $i ) {
				$orders_count    = 1;
				$num_items_sold  = $orders_count * $qty_per_product;
				$coupons         = 0;
				$shipping        = $orders_count * 10;
				$net_revenue     = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales     = $net_revenue + $shipping;
				$gross_sales     = $net_revenue + $coupons;
				$total_customers = 1;
			} else {
				$orders_count    = 0;
				$num_items_sold  = $orders_count * $qty_per_product;
				$coupons         = 0;
				$shipping        = $orders_count * 10;
				$net_revenue     = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales     = $net_revenue + $shipping;
				$gross_sales     = $net_revenue + $coupons;
				$total_customers = 0;
			}

			$expected_interval = array(
				'interval'       => $date_start->format( 'Y-m-d H' ),
				'date_start'     => $date_start->format( 'Y-m-d H:i:s' ),
				'date_start_gmt' => $date_start->format( 'Y-m-d H:i:s' ),
				'date_end'       => $date_end->format( 'Y-m-d H:i:s' ),
				'date_end_gmt'   => $date_end->format( 'Y-m-d H:i:s' ),
				'subtotals'      => array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $total_sales,
					'gross_sales'         => $gross_sales,
					'coupons'             => $coupons,
					'coupons_count'       => $coupons_count,
					'refunds'             => 0,
					'taxes'               => 0,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => 0 === $orders_count ? 0 : $num_items_sold / $orders_count,
					'avg_order_value'     => 0 === $orders_count ? 0 : $net_revenue / $orders_count,
					'total_customers'     => $total_customers,
					'segments'            => array(),
				),
			);

			$expected_intervals[] = $expected_interval;
		}

		// Totals section.
		$orders_count   = count( $order_during_this_['hour'] ) + 1; // order 3 is 1 hour before order 1 & 2, so include it.
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_price * $qty_per_product * $orders_count - $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => $expected_intervals,
			'total'     => $interval_count,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ASC -- same values, just reverse order of intervals.
		$query_args = array(
			'after'    => $minus_9_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'asc',
		);

		$expected_stats['intervals'] = array_reverse( $expected_stats['intervals'] );

		$actual = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * Test for [-10 hours, now] -- 1 page full, 1 interval on 2nd page.
		 * DESC:
		 * bottom           ...                    top
		 *                 page 1
		 * | --------------------|------|-----|-----|
		 * minus 9 hours         |      |   order1  now
		 *                       |      |   order2
		 *                       |  now-1 hour
		 *                     order 3
		 *
		 *                 page 2
		 * |---|
		 * minus 10 hours
		 *     minus 9 hours
		 */
		$hour_offset    = 10;
		$minus_10_hours = new DateTime();
		$now_timestamp  = (int) $current_hour_end->format( 'U' );
		$minus_10_hours->setTimestamp( $now_timestamp - $hour_offset * HOUR_IN_SECONDS );
		$per_page = 10;

		// Page 1.
		$query_args = array(
			'after'    => $minus_10_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'desc',
			'page'     => 1,
			'per_page' => $per_page,
		);

		// Expected Intervals section construction.
		$expected_intervals = array();
		$interval_count     = 11;
		for ( $i = 0; $i < $interval_count; $i ++ ) {
			if ( 0 === $i ) {
				$date_start = new DateTime( $current_hour_end->format( 'Y-m-d H:00:00' ) );
				$date_end   = $current_hour_end;
			} elseif ( $hour_offset === $i ) {
				$date_start = $minus_10_hours;
				$date_end   = new DateTime( $minus_10_hours->format( 'Y-m-d H:59:59' ) );
			} else {
				$hour_anchor = new DateTime();
				$hour_anchor->setTimestamp( $now_timestamp - $i * HOUR_IN_SECONDS );
				$date_start = new DateTime( $hour_anchor->format( 'Y-m-d H:00:00' ) );
				$date_end   = new DateTime( $hour_anchor->format( 'Y-m-d H:59:59' ) );
			}

			if ( 0 === $i ) {
				$orders_count        = count( $order_during_this_['hour'] );
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} elseif ( 1 === $i ) {
				$orders_count        = 1;
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} else {
				$orders_count        = 0;
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 0;
			}

			$expected_interval = array(
				'interval'       => $date_start->format( 'Y-m-d H' ),
				'date_start'     => $date_start->format( 'Y-m-d H:i:s' ),
				'date_start_gmt' => $date_start->format( 'Y-m-d H:i:s' ),
				'date_end'       => $date_end->format( 'Y-m-d H:i:s' ),
				'date_end_gmt'   => $date_end->format( 'Y-m-d H:i:s' ),
				'subtotals'      => array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $total_sales,
					'gross_sales'         => $gross_sales,
					'coupons'             => $coupons,
					'coupons_count'       => $coupons_count,
					'refunds'             => 0,
					'taxes'               => 0,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => 0 === $orders_count ? 0 : $num_items_sold / $orders_count,
					'avg_order_value'     => 0 === $orders_count ? 0 : $net_revenue / $orders_count,
					'total_customers'     => $returning_customers,
					'segments'            => array(),
				),
			);

			$expected_intervals[] = $expected_interval;
		}

		// Totals section.
		$orders_count   = count( $order_during_this_['hour'] ) + 1; // order 3 is 1 hour before order 1 & 2, so include it.
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_price * $qty_per_product * $orders_count - $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array_slice( $expected_intervals, 0, $per_page ),
			'total'     => 11,
			'pages'     => 2,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// Page 2.
		$query_args = array(
			'after'    => $minus_10_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'desc',
			'page'     => 2,
		);

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array_slice( $expected_intervals, $per_page ),
			'total'     => 11,
			'pages'     => 2,
			'page_no'   => 2,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * Test for [-10 hours, now] -- 1 page full, 1 interval on 2nd page.
		 * ASC:
		 * bottom           ...                    top
		 *                 page 1
		 * | -----------|-----|-----------|-----------|
		 * minus 1 hour |     |           |        minus 10 hours
		 *           order 3  |           |
		 *                    |          minus 9 hours
		 *                   minus 2 hours
		 *
		 *                 page 2
		 * |-----------|
		 * now hours
		 *            minus 1 hour
		 */
		$expected_intervals = array_reverse( $expected_intervals );

		// Page 1.
		$query_args = array(
			'after'    => $minus_10_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'asc',
			'page'     => 1,
			'per_page' => $per_page,
		);

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array_slice( $expected_intervals, 0, $per_page ),
			'total'     => 11,
			'pages'     => 2,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// Page 2.
		$query_args = array(
			'after'    => $minus_10_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'date',
			'order'    => 'asc',
			'page'     => 2,
			'per_page' => $per_page,
		);

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array_slice( $expected_intervals, $per_page ),
			'total'     => 11,
			'pages'     => 2,
			'page_no'   => 2,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// Now the same should be done for days, weeks, months and years. But I'm too low on mana to do that.
	}

	/**
	 * Test zero filling when ordering by non-date property, in this case orders_count.
	 */
	public function test_zero_fill_order_by_orders_count() {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$product_price = 11;
		$product       = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( $product_price );
		$product->save();

		$customer = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.test' );

		// 1 order within the current hour.
		$order_1_time = time();

		$order_during_this_['hour']  = array( 1 );
		$order_during_this_['day']   = array( 1 );
		$order_during_this_['week']  = array( 1 );
		$order_during_this_['month'] = array( 1 );
		$order_during_this_['year']  = array( 1 );

		$order_1_datetime = new DateTime();
		$order_1_datetime = $order_1_datetime->setTimestamp( $order_1_time );

		// Create one order in previous year.
		$order_7_datetime  = new DateTime();
		$order_7_datetime  = $order_7_datetime->setTimestamp( $order_1_time - YEAR_IN_SECONDS );
		$order_7_time      = $order_7_datetime->format( 'U' );
		$order[7]['year']  = (int) $order_7_datetime->format( 'Y' );
		$order[7]['month'] = (int) $order_7_datetime->format( 'm' );
		$order[7]['week']  = (int) $order_7_datetime->format( 'W' );
		$order[7]['day']   = (int) $order_7_datetime->format( 'd' );

		// Create one order in previous month.
		$order_6_datetime  = new DateTime();
		$order_6_datetime  = $order_6_datetime->setTimestamp( $order_1_time - MONTH_IN_SECONDS );
		$order_6_time      = $order_6_datetime->format( 'U' );
		$order[6]['year']  = (int) $order_6_datetime->format( 'Y' );
		$order[6]['month'] = (int) $order_6_datetime->format( 'm' );
		$order[6]['week']  = (int) $order_6_datetime->format( 'W' );
		$order[6]['day']   = (int) $order_6_datetime->format( 'd' );

		// Create one order in previous week.
		$order_5_datetime  = new DateTime();
		$order_5_datetime  = $order_5_datetime->setTimestamp( $order_1_time - WEEK_IN_SECONDS );
		$order_5_time      = $order_5_datetime->format( 'U' );
		$order[5]['year']  = (int) $order_5_datetime->format( 'Y' );
		$order[5]['month'] = (int) $order_5_datetime->format( 'm' );
		$order[5]['week']  = (int) $order_5_datetime->format( 'W' );
		$order[5]['day']   = (int) $order_5_datetime->format( 'd' );

		// Create one order in previous day.
		$order_4_datetime  = new DateTime();
		$order_4_datetime  = $order_4_datetime->setTimestamp( $order_1_time - DAY_IN_SECONDS );
		$order_4_time      = $order_4_datetime->format( 'U' );
		$order[4]['year']  = (int) $order_4_datetime->format( 'Y' );
		$order[4]['month'] = (int) $order_4_datetime->format( 'm' );
		$order[4]['week']  = (int) $order_4_datetime->format( 'W' );
		$order[4]['day']   = (int) $order_4_datetime->format( 'd' );

		// Create one order in same day, -1 hour.
		$order_3_datetime  = new DateTime();
		$order_3_datetime  = $order_3_datetime->setTimestamp( $order_1_time - HOUR_IN_SECONDS );
		$order_3_time      = $order_3_datetime->format( 'U' );
		$order[3]['year']  = (int) $order_3_datetime->format( 'Y' );
		$order[3]['month'] = (int) $order_3_datetime->format( 'm' );
		$order[3]['week']  = (int) $order_3_datetime->format( 'W' );
		$order[3]['day']   = (int) $order_3_datetime->format( 'd' );

		// Current order.
		$order[1]['year']  = (int) $order_1_datetime->format( 'Y' );
		$order[1]['month'] = (int) $order_1_datetime->format( 'm' );
		$order[1]['week']  = (int) $order_1_datetime->format( 'W' );
		$order[1]['day']   = (int) $order_1_datetime->format( 'd' );
		$order[1]['hour']  = (int) $order_1_datetime->format( 'H' );

		// 2 orders within 1 hour before now to test multiple orders within one time interval.
		$order_2_time                 = $order_3_time;
		$order_during_this_['hour-1'] = array( 2, 3 );

		// In case some of the orders end up on different day/hour/month/year, we need to find out where exactly they ended up.
		foreach ( array( 3, 4, 5, 6, 7 ) as $order_no ) {
			if ( $order[ $order_no ]['day'] === $order[1]['day'] && $order[ $order_no ]['month'] === $order[1]['month'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$order_during_this_['day'][] = $order_no;
			}
			if ( $order[ $order_no ]['week'] === $order[1]['week'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$order_during_this_['week'][] = $order_no;
			}
			if ( $order[ $order_no ]['month'] === $order[1]['month'] && $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$order_during_this_['month'][] = $order_no;
			}
			if ( $order[ $order_no ]['year'] === $order[1]['year'] ) {
				$order_during_this_['year'][] = $order_no;
			}
		}

		$order_status    = 'completed';
		$qty_per_product = 4; // Hardcoded in WC_Helper_Order::create_order.

		// Create orders for the test cases.
		$orders = array();
		foreach (
			array(
				$order_7_time,
				$order_6_time,
				$order_5_time,
				$order_4_time,
				$order_3_time,
				$order_2_time,
				$order_1_time,
			) as $order_time
		) {
			// Order with 1 product.
			$order = WC_Helper_Order::create_order( $customer->get_id(), $product );
			$order->set_date_created( $order_time );
			$order->set_date_paid( $order_time );
			$order->set_status( $order_status );

			$order->calculate_totals();
			$order->save();

			$orders[] = $order;
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		global $wpdb;
		$res = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wc_order_stats" );

		$data_store = new OrdersStatsDataStore();

		// Tests for before & after set to current hour.
		// (this sets minutes for current hour to 0, seconds are left as they arem e.g. 15:23:43 becomes 15:00:43).
		$current_hour_start = new DateTime();
		$current_hour_start->setTimestamp( $order_1_time );
		$current_hour_minutes = (int) $current_hour_start->format( 'i' );
		$current_hour_start->setTimestamp( $order_1_time - $current_hour_minutes * MINUTE_IN_SECONDS );

		// This is the last second of the current hour, e.g. 15:23:43 becomes 15:59:59.
		$current_hour_end = new DateTime();
		$current_hour_end->setTimestamp( $order_1_time );
		$seconds_into_hour = (int) $current_hour_end->format( 'U' ) % HOUR_IN_SECONDS;
		$current_hour_end->setTimestamp( $order_1_time + ( HOUR_IN_SECONDS - $seconds_into_hour ) - 1 );

		// Test 1: only one hour visible, so only 1 interval in the response, no real ordering.
		// DESC.
		$query_args = array(
			'after'    => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'desc',
		);

		$orders_count   = count( $order_during_this_['hour'] );
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$coupons_count  = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_price * $qty_per_product * $orders_count - $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => $current_hour_start->format( 'Y-m-d H' ),
					'date_start'     => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $current_hour_start->format( 'Y-m-d H:i:s' ),
					'date_end'       => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $current_hour_end->format( 'Y-m-d H:i:s' ),
					'subtotals'      => array(
						'orders_count'        => $orders_count,
						'num_items_sold'      => $num_items_sold,
						'total_sales'         => $total_sales,
						'gross_sales'         => $gross_sales,
						'coupons'             => $coupons,
						'coupons_count'       => $coupons_count,
						'refunds'             => 0,
						'taxes'               => 0,
						'shipping'            => $shipping,
						'net_revenue'         => $net_revenue,
						'avg_items_per_order' => $num_items_sold / $orders_count,
						'avg_order_value'     => $net_revenue / $orders_count,
						'total_customers'     => 1,
						'segments'            => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$actual         = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// ASC -- only 1 interval, so should be the same.
		$query_args = array(
			'after'    => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'asc',
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * Test for [-5 hours, now] -- partial 1 page.
		 * This should include 3 orders, 2 done at hour-1 and one in the current hour.
		 *
		 * DESC ordering by order count =>
		 *  previous hour with 2 orders at the top,
		 *  then current hour with 1 order,
		 *  then zero-filled intervals.
		 */
		$hour_offset   = 5;
		$minus_5_hours = new DateTime();
		$now_timestamp = (int) $current_hour_end->format( 'U' );
		$minus_5_hours->setTimestamp( $now_timestamp - $hour_offset * HOUR_IN_SECONDS );

		$query_args = array(
			'after'    => $minus_5_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'desc',
		);

		// Expected Intervals section construction.
		$expected_intervals = array();
		// Even in case this runs exactly at the hour turn second, there should still be 6 intervals:
		// e.g. 20:30:51 -(minus 5 hours)- 15:30:51 means intervals 15:30:51--15:59:59, 16:00-16:59, 17, 18, 19, 20:00-20:30, i.e. 6 intervals
		// also if this run exactly at 20:00 -(minus 5 hours)- 15:00, then intervals should be 15:00-15:59, 16, 17, 18, 19, 20:00-20:00.
		$interval_count = $hour_offset + 1;
		for ( $i = $interval_count - 1; $i >= 0; $i -- ) {
			if ( 0 === $i ) {
				$date_start = new DateTime( $current_hour_end->format( 'Y-m-d H:00:00' ) );
				$date_end   = $current_hour_end;
			} elseif ( $hour_offset === $i ) {
				$date_start = $minus_5_hours;
				$date_end   = new DateTime( $minus_5_hours->format( 'Y-m-d H:59:59' ) );
			} else {
				$hour_anchor = new DateTime();
				$hour_anchor->setTimestamp( $now_timestamp - $i * HOUR_IN_SECONDS );
				$date_start = new DateTime( $hour_anchor->format( 'Y-m-d H:00:00' ) );
				$date_end   = new DateTime( $hour_anchor->format( 'Y-m-d H:59:59' ) );
			}

			if ( 1 === $i ) {
				$orders_count        = count( $order_during_this_['hour-1'] );
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} elseif ( 0 === $i ) {
				$orders_count        = count( $order_during_this_['hour'] );
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} else {
				$orders_count        = 0;
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 0;
			}

			$expected_interval = array(
				'interval'       => $date_start->format( 'Y-m-d H' ),
				'date_start'     => $date_start->format( 'Y-m-d H:i:s' ),
				'date_start_gmt' => $date_start->format( 'Y-m-d H:i:s' ),
				'date_end'       => $date_end->format( 'Y-m-d H:i:s' ),
				'date_end_gmt'   => $date_end->format( 'Y-m-d H:i:s' ),
				'subtotals'      => array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $total_sales,
					'gross_sales'         => $gross_sales,
					'coupons'             => $coupons,
					'coupons_count'       => $coupons_count,
					'refunds'             => 0,
					'taxes'               => 0,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => 0 === $orders_count ? 0 : $num_items_sold / $orders_count,
					'avg_order_value'     => 0 === $orders_count ? 0 : $net_revenue / $orders_count,
					'total_customers'     => $returning_customers,
					'segments'            => array(),
				),
			);

			$expected_intervals[] = $expected_interval;
		}

		/*
		 * The zero-filled intervals are actually always ordered by time ASC if the primary sorting condition is the same.
		 * $expected_intervals now:
		 *  - [0] => 0 orders, hour - 5
		 *  - [1] => 0 orders, hour - 4
		 *  - [2] => 0 orders, hour - 3
		 *  - [3] => 0 orders, hour - 2
		 *  - [4] => 2 orders, hour - 1
		 *  - [5] => 1 orders, hour - 0
		 *
		 * This means we need to put last two to the beginning of the array.
		 */
		$to_be_second = array_pop( $expected_intervals );
		$to_be_first  = array_pop( $expected_intervals );
		array_unshift( $expected_intervals, $to_be_second );
		array_unshift( $expected_intervals, $to_be_first );

		// Totals section.
		$orders_count   = count( $order_during_this_['hour'] ) + count( $order_during_this_['hour-1'] ); // order 2 & 3 is 1 hour before order 1, so include all of those.
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_price * $qty_per_product * $orders_count - $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => $expected_intervals,
			'total'     => $interval_count,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$actual         = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * 5 hour time window, ASC ordering -- numbers stay the same, but first include zero intervals in asc order, then the rest.
		 * $expected_intervals now:
		 *  - [0] => 2 orders, hour - 1
		 *  - [1] => 1 orders, hour - 0
		 *  - [2] => 0 orders, hour - 2
		 *  - [3] => 0 orders, hour - 3
		 *  - [4] => 0 orders, hour - 4
		 *  - [5] => 0 orders, hour - 5
		 * so we need to revert first two and put them at the bottom of the array.
		 */
		$to_be_last        = array_shift( $expected_stats['intervals'] );
		$to_be_second_last = array_shift( $expected_stats['intervals'] );
		array_push( $expected_stats['intervals'], $to_be_second_last );
		array_push( $expected_stats['intervals'], $to_be_last );

		$query_args = array(
			'after'    => $minus_5_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'asc',
		);

		$actual = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * Test for [-9 hours, now] -- full 1 page.
		 * Very similar to -5 hours.
		 *
		 */
		$hour_offset   = 9;
		$minus_9_hours = new DateTime();
		$now_timestamp = (int) $current_hour_end->format( 'U' );
		$minus_9_hours->setTimestamp( $now_timestamp - $hour_offset * HOUR_IN_SECONDS );

		$query_args = array(
			'after'    => $minus_9_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'desc',
		);

		/*
		 * Expected Intervals section construction.
		 * Initially intervals are ordered from the least recent to most recent, i.e.:
		 *  - [0] => 0 orders, hour - 9
		 *  - [1] => 0 orders, hour - 8
		 *  ...
		 *  - [7] => 0 orders, hour - 2
		 *  - [8] => 2 orders, hour - 1
		 *  - [9] => 1 orders, hour - 0
		 *
		 * To change ordering by orders_count, just pop last two and push them to the front (aka unshift in php).
		 */
		$expected_intervals = array();
		$interval_count     = $hour_offset + 1;
		for ( $i = $interval_count - 1; $i >= 0; $i -- ) {
			if ( 0 === $i ) {
				$date_start = new DateTime( $current_hour_end->format( 'Y-m-d H:00:00' ) );
				$date_end   = $current_hour_end;
			} elseif ( $hour_offset === $i ) {
				$date_start = $minus_9_hours;
				$date_end   = new DateTime( $minus_9_hours->format( 'Y-m-d H:59:59' ) );
			} else {
				$hour_anchor = new DateTime();
				$hour_anchor->setTimestamp( $now_timestamp - $i * HOUR_IN_SECONDS );
				$date_start = new DateTime( $hour_anchor->format( 'Y-m-d H:00:00' ) );
				$date_end   = new DateTime( $hour_anchor->format( 'Y-m-d H:59:59' ) );
			}

			if ( 0 === $i ) {
				$orders_count        = count( $order_during_this_['hour'] );
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} elseif ( 1 === $i ) {
				$orders_count        = count( $order_during_this_['hour-1'] );
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} else {
				$orders_count        = 0;
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 0;
			}

			$expected_interval = array(
				'interval'       => $date_start->format( 'Y-m-d H' ),
				'date_start'     => $date_start->format( 'Y-m-d H:i:s' ),
				'date_start_gmt' => $date_start->format( 'Y-m-d H:i:s' ),
				'date_end'       => $date_end->format( 'Y-m-d H:i:s' ),
				'date_end_gmt'   => $date_end->format( 'Y-m-d H:i:s' ),
				'subtotals'      => array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $total_sales,
					'gross_sales'         => $gross_sales,
					'coupons'             => $coupons,
					'coupons_count'       => $coupons_count,
					'refunds'             => 0,
					'taxes'               => 0,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => 0 === $orders_count ? 0 : $num_items_sold / $orders_count,
					'avg_order_value'     => 0 === $orders_count ? 0 : $net_revenue / $orders_count,
					'total_customers'     => $returning_customers,
					'segments'            => array(),
				),
			);

			$expected_intervals[] = $expected_interval;
		}
		$to_be_second = array_pop( $expected_intervals );
		$to_be_first  = array_pop( $expected_intervals );
		array_unshift( $expected_intervals, $to_be_second );
		array_unshift( $expected_intervals, $to_be_first );

		// Totals section.
		$orders_count   = count( $order_during_this_['hour'] ) + count( $order_during_this_['hour-1'] ); // order 3 is 1 hour before order 1 & 2, so include it.
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_price * $qty_per_product * $orders_count - $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => $expected_intervals,
			'total'     => $interval_count,
			'pages'     => 1,
			'page_no'   => 1,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * ASC ordering -- same values, different intervals order.
		 *
		 * Now the intervals are ordered by order count desc, i.e.:
		 *  - [0] => 2 orders, hour - 1
		 *  - [1] => 1 orders, hour - 0
		 *  - [2] => 0 orders, hour - 9
		 *  ...
		 *  - [8] => 0 orders, hour - 3
		 *  - [9] => 0 orders, hour - 2
		 *
		 * To change ordering to orders_count ASC, just shift first two and push them to the back in reversed order.
		 */
		$to_be_last        = array_shift( $expected_stats['intervals'] );
		$to_be_second_last = array_shift( $expected_stats['intervals'] );
		array_push( $expected_stats['intervals'], $to_be_second_last );
		array_push( $expected_stats['intervals'], $to_be_last );

		$query_args = array(
			'after'    => $minus_9_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'asc',
		);

		$actual = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * Test for [-10 hours, now] -- 1 page full, 1 interval on 2nd page.
		 * DESC.
		 */
		$hour_offset    = 10;
		$minus_10_hours = new DateTime();
		$now_timestamp  = (int) $current_hour_end->format( 'U' );
		$minus_10_hours->setTimestamp( $now_timestamp - $hour_offset * HOUR_IN_SECONDS );
		$per_page = 10;

		// Page 1.
		$query_args = array(
			'after'    => $minus_10_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'desc',
			'page'     => 1,
			'per_page' => $per_page,
		);

		/**
		 * Expected Intervals section construction.
		 * Initially created as:
		 *  - [0]  => 0 orders, hour - 10
		 *  - [1]  => 0 orders, hour - 9
		 *  ...
		 *  - [8]  => 0 orders, hour - 2
		 *  - [9]  => 2 orders, hour - 1
		 *  - [10] => 1 orders, hour - 0
		 *
		 * so last two need to be put first (last one second, second last one first).
		 */
		$expected_intervals = array();
		$interval_count     = 11;
		for ( $i = $interval_count - 1; $i >= 0; $i -- ) {
			if ( 0 === $i ) {
				$date_start = new DateTime( $current_hour_end->format( 'Y-m-d H:00:00' ) );
				$date_end   = $current_hour_end;
			} elseif ( $hour_offset === $i ) {
				$date_start = $minus_10_hours;
				$date_end   = new DateTime( $minus_10_hours->format( 'Y-m-d H:59:59' ) );
			} else {
				$hour_anchor = new DateTime();
				$hour_anchor->setTimestamp( $now_timestamp - $i * HOUR_IN_SECONDS );
				$date_start = new DateTime( $hour_anchor->format( 'Y-m-d H:00:00' ) );
				$date_end   = new DateTime( $hour_anchor->format( 'Y-m-d H:59:59' ) );
			}

			if ( 0 === $i ) {
				$orders_count        = count( $order_during_this_['hour'] );
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} elseif ( 1 === $i ) {
				$orders_count        = count( $order_during_this_['hour-1'] );
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 1;
			} else {
				$orders_count        = 0;
				$num_items_sold      = $orders_count * $qty_per_product;
				$coupons             = 0;
				$shipping            = $orders_count * 10;
				$net_revenue         = $product_price * $qty_per_product * $orders_count - $coupons;
				$total_sales         = $net_revenue + $shipping;
				$gross_sales         = $net_revenue + $coupons;
				$returning_customers = 0;
			}

			$expected_interval = array(
				'interval'       => $date_start->format( 'Y-m-d H' ),
				'date_start'     => $date_start->format( 'Y-m-d H:i:s' ),
				'date_start_gmt' => $date_start->format( 'Y-m-d H:i:s' ),
				'date_end'       => $date_end->format( 'Y-m-d H:i:s' ),
				'date_end_gmt'   => $date_end->format( 'Y-m-d H:i:s' ),
				'subtotals'      => array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $total_sales,
					'gross_sales'         => $gross_sales,
					'coupons'             => $coupons,
					'coupons_count'       => $coupons_count,
					'refunds'             => 0,
					'taxes'               => 0,
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => 0 === $orders_count ? 0 : $num_items_sold / $orders_count,
					'avg_order_value'     => 0 === $orders_count ? 0 : $net_revenue / $orders_count,
					'total_customers'     => $returning_customers,
					'segments'            => array(),
				),
			);

			$expected_intervals[] = $expected_interval;
		}
		$to_be_second = array_pop( $expected_intervals );
		$to_be_first  = array_pop( $expected_intervals );
		array_unshift( $expected_intervals, $to_be_second );
		array_unshift( $expected_intervals, $to_be_first );

		// Totals section.
		$orders_count   = count( $order_during_this_['hour'] ) + count( $order_during_this_['hour-1'] ); // orders 2 & 3 are 1 hour before order 1, so include all of those.
		$num_items_sold = $orders_count * $qty_per_product;
		$coupons        = 0;
		$shipping       = $orders_count * 10;
		$net_revenue    = $product_price * $qty_per_product * $orders_count - $coupons;
		$total_sales    = $net_revenue + $shipping;
		$gross_sales    = $net_revenue + $coupons;

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array_slice( $expected_intervals, 0, $per_page ),
			'total'     => 11,
			'pages'     => 2,
			'page_no'   => 1,
		);
		$actual         = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// Page 2.
		$query_args = array(
			'after'    => $minus_10_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'desc',
			'page'     => 2,
		);

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array_slice( $expected_intervals, $per_page ),
			'total'     => 11,
			'pages'     => 2,
			'page_no'   => 2,
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		/*
		 * Test for [-10 hours, now] -- 1 page full, 1 interval on 2nd page.
		 * ASC
		 *
		 * Currently, the intervals are in DESC order by orders_count:
		 *  - [0]  => 2 orders, hour - 1
		 *  - [1]  => 1 orders, hour - 0
		 *  - [2]  => 0 orders, hour - 10
		 *  - [3]  => 0 orders, hour - 9
		 *  ...
		 *  - [9]  => 0 orders, hour - 3
		 *  - [10] => 0 orders, hour - 2
		 * so first one needs to become the last one, second one the second last.
		 */
		$to_be_last        = array_shift( $expected_intervals );
		$to_be_second_last = array_shift( $expected_intervals );
		array_push( $expected_intervals, $to_be_second_last );
		array_push( $expected_intervals, $to_be_last );

		// Page 1.
		$query_args = array(
			'after'    => $minus_10_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'asc',
			'page'     => 1,
			'per_page' => $per_page,
		);

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array_slice( $expected_intervals, 0, $per_page ),
			'total'     => 11,
			'pages'     => 2,
			'page_no'   => 1,
		);

		$actual = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );
		$this->assertEquals( $expected_stats, $actual, 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );

		// Page 2.
		$query_args = array(
			'after'    => $minus_10_hours->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
			'orderby'  => 'orders_count',
			'order'    => 'asc',
			'page'     => 2,
			'per_page' => $per_page,
		);

		$expected_stats = array(
			'totals'    => array(
				'orders_count'        => $orders_count,
				'num_items_sold'      => $num_items_sold,
				'total_sales'         => $total_sales,
				'gross_sales'         => $gross_sales,
				'coupons'             => $coupons,
				'coupons_count'       => $coupons_count,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => $shipping,
				'net_revenue'         => $net_revenue,
				'avg_items_per_order' => $num_items_sold / $orders_count,
				'avg_order_value'     => $net_revenue / $orders_count,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array_slice( $expected_intervals, $per_page ),
			'total'     => 11,
			'pages'     => 2,
			'page_no'   => 2,
		);

		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true ), 'Query args: ' . $this->return_print_r( $query_args ) . "; query: {$wpdb->last_query}" );
	}

	/**
	 * Pass input variable through print_r.
	 *
	 * @since 3.5.4
	 * @param  string|array|object $data Variable data to be passed through print_r.
	 * @return string
	 */
	private function return_print_r( $data ) {
		// @codingStandardsIgnoreStart
		return print_r( $data, true );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Test new and returning guest customer.
	 */
	public function test_guest_returning_customer() {
		WC_Helper_Reports::reset_stats_dbs();

		// Create a test product for use in an order.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$data_store = new OrdersStatsDataStore();

		// All empty in the beginning.
		$query_args  = array(
			'interval' => 'hour',
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 0, $actual_data->totals->total_customers );

		// Create an order an hour before the order 1, so that the guest customer will become returning customer later.
		$order_1_time = time();

		$order_0_time = $order_1_time - HOUR_IN_SECONDS;

		$order_0 = WC_Helper_Order::create_order( 0, $product );
		$order_0->set_date_created( $order_0_time );
		$order_0->set_date_paid( $order_0_time );
		$order_0->set_status( 'processing' );
		$order_0->set_total( 100 );
		$order_0->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$start_time  = gmdate( 'Y-m-d H:00:00', $order_0->get_date_created()->getOffsetTimestamp() );
		$end_time    = gmdate( 'Y-m-d H:59:59', $order_0->get_date_created()->getOffsetTimestamp() );
		$query_args  = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Place an order 'one hour later', 2 orders, but still just one customer.
		$order_1 = WC_Helper_Order::create_order( 0, $product );
		$order_1->set_date_created( $order_1_time );
		$order_1->set_date_paid( $order_1_time );
		$order_1->set_status( 'processing' );
		$order_1->set_total( 100 );
		$order_1->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Time frame includes both orders -> customer is a new customer.
		$start_time = gmdate( 'Y-m-d H:00:00', $order_0->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order_1->get_date_created()->getOffsetTimestamp() );
		$query_args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);

		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Time frame includes only second order -> customer is a returning customer.
		$after_order_0 = new DateTime();
		$after_order_0->setTimestamp( $order_0_time + 1 );

		$start_time = gmdate( 'Y-m-d H:i:s', $order_0_time + 1 );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order_1->get_date_created()->getOffsetTimestamp() );
		$query_args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);

		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Wait a bit so that orders are not created at the same second.
		sleep( 1 );

		$order_2 = WC_Helper_Order::create_order( 0, $product );
		$order_2->set_date_created( $order_1_time );
		$order_2->set_date_paid( $order_1_time );
		$order_2->set_status( 'processing' );
		$order_2->set_total( 100 );
		$order_2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Time frame includes second and third order -> there is one returning customer.
		$start_time  = gmdate( 'Y-m-d H:i:s', $order_0_time + 1 );
		$end_time    = gmdate( 'Y-m-d H:59:59', $order_2->get_date_created()->getOffsetTimestamp() );
		$query_args  = array(
			'interval' => 'day', // to skip cache.
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		// It's still the same customer who ordered for the first time in this hour, they just placed 2 orders.
		$this->assertEquals( 1, $actual_data->totals->total_customers );

	}

	/**
	 * Test new and returning registered customer.
	 */
	public function test_registered_returning_customer() {
		WC_Helper_Reports::reset_stats_dbs();

		$customer_1 = WC_Helper_Customer::create_customer( 'cust_1_new', 'pwd_1', 'new_customer@mail.com' );

		// Create a test product for use in an order.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$data_store = new OrdersStatsDataStore();

		// All empty in the beginning.
		$query_args  = array(
			'interval' => 'hour',
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 0, $actual_data->totals->total_customers );

		// Create an order an hour before the order 1, so that the guest customer will become returning customer later.
		$order_1_time = time();

		$order_0_time = $order_1_time - HOUR_IN_SECONDS;

		$order_0 = WC_Helper_Order::create_order( $customer_1->get_id(), $product );
		$order_0->set_date_created( $order_0_time );
		$order_0->set_date_paid( $order_0_time );
		$order_0->set_status( 'processing' );
		$order_0->set_total( 100 );
		$order_0->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$start_time  = gmdate( 'Y-m-d H:00:00', $order_0->get_date_created()->getOffsetTimestamp() );
		$end_time    = gmdate( 'Y-m-d H:59:59', $order_0->get_date_created()->getOffsetTimestamp() );
		$query_args  = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Place an order 'one hour later', 2 orders, but still just one customer.
		$order_1 = WC_Helper_Order::create_order( $customer_1->get_id(), $product );
		$order_1->set_date_created( $order_1_time );
		$order_1->set_date_paid( $order_1_time );
		$order_1->set_status( 'processing' );
		$order_1->set_total( 100 );
		$order_1->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Time frame includes both orders -> customer is a new customer.
		$start_time = gmdate( 'Y-m-d H:00:00', $order_0->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order_1->get_date_created()->getOffsetTimestamp() );
		$query_args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);

		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Time frame includes only second order -> customer is a returning customer.
		$after_order_0 = new DateTime();
		$after_order_0->setTimestamp( $order_0_time + 1 );

		$start_time = gmdate( 'Y-m-d H:i:s', $order_0_time + 1 );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order_1->get_date_created()->getOffsetTimestamp() );
		$query_args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);

		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Wait a bit so that orders are not created at the same second.
		sleep( 1 );

		$order_2 = WC_Helper_Order::create_order( $customer_1->get_id(), $product );
		$order_2->set_date_created( $order_1_time );
		$order_2->set_date_paid( $order_1_time );
		$order_2->set_status( 'processing' );
		$order_2->set_total( 100 );
		$order_2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Time frame includes second and third order -> there is one returning customer.
		$start_time  = gmdate( 'Y-m-d H:i:s', $order_0_time + 1 );
		$end_time    = gmdate( 'Y-m-d H:59:59', $order_2->get_date_created()->getOffsetTimestamp() );
		$query_args  = array(
			'interval' => 'day', // to skip cache.
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		// It's still the same customer who ordered for the first time in this hour, they just placed 2 orders.
		$this->assertEquals( 1, $actual_data->totals->total_customers );
	}
}
