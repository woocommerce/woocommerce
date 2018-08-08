<?php

/**
 * Reports product stats tests.
 *
 * @package WooCommerce\Tests\Orders
 * @todo Finish up unit testing to verify bug-free product reports.
 */
class WC_Tests_Reports_Products extends WC_Unit_Test_Case {

	/**
	 * Test the calculations and querying works correctly for the base case of 1 product.
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

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		$data_store = new WC_Reports_Products_Data_Store();
		$start_time = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
		$args = array(
			'after'  => $start_time,
			'before' => $end_time,
		);

		// Test retrieving the stats through the data store.
		$data = $data_store->get_data( $args );
		$expected_data = array(
			array(
				'product_id'    => $product->get_id(),
				'items_sold'    => 4,
				'gross_revenue' => 100.0, // $25 * 4.
				'orders_count'  => 1,
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the query class.
		$query = new WC_Reports_Products_Query( $args );
		$this->assertEquals( $expected_data, $query->get_data() );
	}

}
