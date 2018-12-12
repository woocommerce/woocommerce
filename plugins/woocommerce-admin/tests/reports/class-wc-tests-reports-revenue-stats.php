<?php
/**
 * Reports order stats tests.
 *
 * @package WooCommerce\Tests\Orders
 * @todo Finish up unit testing to verify bug-free order reports.
 */

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

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		// /reports/revenue/stats is mapped to Orders_Data_Store.
		$data_store = new WC_Admin_Reports_Orders_Data_Store();

		$start_time = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = date( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_stats = array(
			'totals'    => array(
				'orders_count'            => 1,
				'num_items_sold'          => 4,
				'gross_revenue'           => 97,
				'coupons'                 => 20,
				'refunds'                 => 0,
				'taxes'                   => 7,
				'shipping'                => 10,
				'net_revenue'             => 80,
				'avg_items_per_order'     => 4,
				'avg_order_value'         => 97,
				'num_returning_customers' => 0,
				'num_new_customers'       => 1,
				'products'                => '1',
			),
			'intervals' => array(
				array(
					'interval'       => date( 'Y-m-d H', $order->get_date_created()->getTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'orders_count'            => 1,
						'num_items_sold'          => 4,
						'gross_revenue'           => 97,
						'coupons'                 => 20,
						'refunds'                 => 0,
						'taxes'                   => 7,
						'shipping'                => 10,
						'net_revenue'             => 80,
						'avg_items_per_order'     => 4,
						'avg_order_value'         => 97,
						'num_returning_customers' => 0,
						'num_new_customers'       => 1,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);

		// Test retrieving the stats from the data store.
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $args ) ), true ) );

		// Test retrieving the stats through the query class.
		$expected_stats = array(
			'totals'    => array(
				'orders_count'   => 1,
				'num_items_sold' => 4,
				'gross_revenue'  => 97,
				'coupons'        => 20,
				'refunds'        => 0,
				'taxes'          => 7,
				'shipping'       => 10,
				'net_revenue'    => 80,
				'products'       => '1',
			),
			'intervals' => array(
				array(
					'interval'       => date( 'Y-m-d H', $order->get_date_created()->getTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'orders_count'   => 1,
						'num_items_sold' => 4,
						'gross_revenue'  => 97,
						'coupons'        => 20,
						'refunds'        => 0,
						'taxes'          => 7,
						'shipping'       => 10,
						'net_revenue'    => 80,
					),
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
		$query          = new WC_Admin_Reports_Revenue_Query( $args );
		$this->assertEquals( $expected_stats, json_decode( json_encode( $query->get_data() ), true ) );
	}
}
