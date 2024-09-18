<?php
/**
 * Reports order stats tests.
 *
 * @package WooCommerce\Admin\Tests\Orders
 * @todo Finish up unit testing to verify bug-free order reports.
 */

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Revenue\Query as RevenueQuery;

/**
 * Class WC_Admin_Tests_Reports_Revenue_Stats
 */
class WC_Admin_Tests_Reports_Revenue_Stats extends WC_Unit_Test_Case {

	/**
	 * Test the calculations and querying works correctly for the base case of 1 order.
	 *
	 * @since 3.5.0
	 */
	public function test_populate_and_query() {
		global $wpdb;

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

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// /reports/revenue/stats is mapped to Orders_Data_Store.
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
				'total_sales'         => 97,
				'gross_sales'         => 100,
				'coupons'             => 20,
				'coupons_count'       => 1,
				'refunds'             => 0,
				'taxes'               => 7,
				'shipping'            => 10,
				'net_revenue'         => 80,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 80,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'orders_count'        => 1,
						'num_items_sold'      => 4,
						'total_sales'         => 97,
						'gross_sales'         => 100,
						'coupons'             => 20,
						'coupons_count'       => 1,
						'refunds'             => 0,
						'taxes'               => 7,
						'shipping'            => 10,
						'net_revenue'         => 80,
						'avg_items_per_order' => 4,
						'avg_order_value'     => 80,
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
		$expected_stats = array(
			'totals'    => array(
				'orders_count'   => 1,
				'num_items_sold' => 4,
				'total_sales'    => 97,
				'gross_sales'    => 100,
				'coupons'        => 20,
				'coupons_count'  => 1,
				'refunds'        => 0,
				'taxes'          => 7,
				'shipping'       => 10,
				'net_revenue'    => 80,
				'products'       => '1',
				'segments'       => array(),
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d H', $order->get_date_created()->getTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'orders_count'   => 1,
						'num_items_sold' => 4,
						'total_sales'    => 97,
						'gross_sales'    => 100,
						'coupons'        => 20,
						'coupons_count'  => 1,
						'refunds'        => 0,
						'taxes'          => 7,
						'shipping'       => 10,
						'net_revenue'    => 80,
						'segments'       => array(),
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$query          = new RevenueQuery( $args );
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $query->get_data() ), true ) );
	}
}
