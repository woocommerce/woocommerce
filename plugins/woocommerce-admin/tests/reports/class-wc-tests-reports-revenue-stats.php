<?php

/**
 * Reports order stats tests.
 *
 * @package WooCommerce\Tests\Orders
 * @todo Finish up unit testing to verify bug-free order reports.
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
		$data_store::update( $order );

		$start_time = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time = date( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		$args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_stats = array(
			'totals' => array(
				'orders_count'        => 1,
				'num_items_sold'      => 4,
				'gross_revenue'       => 97,
				'coupons'             => 20,
				'refunds'             => 0,
				'taxes'               => 7,
				'shipping'            => 10,
				'net_revenue'         => 80,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 97,
			),
			'intervals' => array(
				array(
					'interval'       => date( 'Y-m-d G', $order->get_date_created()->getTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'orders_count'        => 1,
						'num_items_sold'      => 4,
						'gross_revenue'       => 97,
						'coupons'             => 20,
						'refunds'             => 0,
						'taxes'               => 7,
						'shipping'            => 10,
						'net_revenue'         => 80,
						'avg_items_per_order' => 4,
						'avg_order_value'     => 97,
					),
				),
			),
			'total'   => 1,
			'pages'   => 1,
			'page_no' => 1,
		);

		// Test retrieving the stats from the data store.
		$this->assertEquals( $expected_stats, json_decode( json_encode( $data_store->get_data( $args ) ), true ) );

		// Test retrieving the stats through the query class.
		$expected_stats = array(
			'totals' => array(
				'orders_count'        => 1,
				'num_items_sold'      => 4,
				'gross_revenue'       => 97,
				'coupons'             => 20,
				'refunds'             => 0,
				'taxes'               => 7,
				'shipping'            => 10,
				'net_revenue'         => 80,
			),
			'intervals' => array(
				array(
					'interval'       => date( 'Y-m-d G', $order->get_date_created()->getTimestamp() ),
					'date_start'     => $start_time,
					'date_start_gmt' => $start_time,
					'date_end'       => $end_time,
					'date_end_gmt'   => $end_time,
					'subtotals'      => array(
						'orders_count'        => 1,
						'num_items_sold'      => 4,
						'gross_revenue'       => 97,
						'coupons'             => 20,
						'refunds'             => 0,
						'taxes'               => 7,
						'shipping'            => 10,
						'net_revenue'         => 80,
					),
				),
			),
			'total'   => 1,
			'pages'   => 1,
			'page_no' => 1,
		);
		$query = new WC_Admin_Reports_Revenue_Query( $args );
		$this->assertEquals( $expected_stats, json_decode( json_encode( $query->get_data() ), true ) );
	}

	/**
	 * Test the calculations and querying works correctly for the case of multiple orders.
	 */
	/*public function test_populate_and_query_multiple_intervals() {
		// Populate all of the data.
		$product1 = new WC_Product_Simple();
		$product1->set_name( 'Test Product' );
		$product1->set_regular_price( 25 );
		$product1->save();

		$product2 = new WC_Product_Simple();
		$product2->set_name( 'Test Product 2' );
		$product2->set_regular_price( 10 );
		$product2->save();

		$order1_time = time() - ( 2 * HOUR_IN_SECONDS );

		$order1 = WC_Helper_Order::create_order( 1, $product1 );
		$order1->set_date_created( $order1_time );
		$order1->set_status( 'completed' );
		$order1->set_shipping_total( 10 );
		$order1->set_discount_total( 20 );
		$order1->set_discount_tax( 0 );
		$order1->set_cart_tax( 5 );
		$order1->set_shipping_tax( 2 );
		$order1->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order1->save();

		$order2_time = time() - HOUR_IN_SECONDS + 1;

		$order2 = WC_Helper_Order::create_order( 1, $product2 );
		$order2->set_date_created( $order2_time );
		$order2->set_status( 'processing' );
		$order2->set_shipping_total( 5 );
		$order2->set_discount_total( 0 );
		$order2->set_discount_tax( 0 );
		$order2->set_cart_tax( 3 );
		$order2->set_shipping_tax( 1 );
		$order2->set_total( 49 ); // $10x4 products + $5 shipping + $4 tax.
		$order2->save();

		// Test the calculations.
		$start_time = $order1_time;
		$end_time = $order2_time + HOUR_IN_SECONDS;

		// Test aggregate raw summary data for both orders.
		$data = WC_Order_Stats::summarize_orders( $start_time, $end_time );
		$expected_data = array(
			'num_orders'            => 2,
			'num_items_sold'        => 8,
			'orders_gross_total'    => 146.0,
			'orders_coupon_total'   => 20.0,
			'orders_refund_total'   => 0,
			'orders_tax_total'      => 11.0,
			'orders_shipping_total' => 15.0,
			'orders_net_total'      => 120.0,
		);
		$this->assertEquals( $expected_data, $data );

		// Calculate stats for each hour and save to DB.
		$data = WC_Order_Stats::summarize_orders( $order1_time, $order1_time + HOUR_IN_SECONDS );
		WC_Order_Stats::update( $order1_time, $data );
		$data = WC_Order_Stats::summarize_orders( $order2_time, $order2_time + HOUR_IN_SECONDS );
		WC_Order_Stats::update( $order2_time, $data );

		// Test querying by hourly intervals.
		$stats = WC_Order_Stats::query( $start_time, $end_time );
		$first_hour_stats = $stats[0];
		$expected_first_hour_stats = array(
			'start_time'            => date( 'Y-m-d H:00:00', $order1_time ),
			'num_orders'            => 1,
			'num_items_sold'        => 4,
			'orders_gross_total'    => 97,
			'orders_coupon_total'   => 20,
			'orders_refund_total'   => 0,
			'orders_tax_total'      => 7,
			'orders_shipping_total' => 10,
			'orders_net_total'      => 80,
		);
		$this->assertEquals( $expected_first_hour_stats, $first_hour_stats );

		$second_hour_stats = $stats[1];
		$expected_second_hour_stats = array(
			'start_time'            => date( 'Y-m-d H:00:00', $order2_time ),
			'num_orders'            => 1,
			'num_items_sold'        => 4,
			'orders_gross_total'    => 49,
			'orders_coupon_total'   => 0,
			'orders_refund_total'   => 0,
			'orders_tax_total'      => 4,
			'orders_shipping_total' => 5,
			'orders_net_total'      => 40,
		);
		$this->assertEquals( $expected_second_hour_stats, $second_hour_stats );

		// Test querying by a weekly interval.
		$stats = WC_Order_Stats::query( $start_time, $end_time, array( 'interval' => 'week' ) );
		$first_week_stats = $stats[0];
		$expected_first_week_stats = array(
			'start_time'            => date( 'Y-m-d H:00:00', $order1_time ),
			'num_orders'            => '2',
			'num_items_sold'        => '8',
			'orders_gross_total'    => '146',
			'orders_coupon_total'   => '20',
			'orders_refund_total'   => '0',
			'orders_tax_total'      => '11',
			'orders_shipping_total' => '15',
			'orders_net_total'      => '120',
		);
		$this->assertEquals( $expected_first_week_stats, $first_week_stats );
	}*/
}
