<?php
/**
 * Reports product stats tests.
 *
 * @package WooCommerce\Tests\Orders
 * @todo Finish up unit testing to verify bug-free product reports.
 */

/**
 * Reports product stats tests class
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

		$data_store = new WC_Admin_Reports_Products_Data_Store();
		$start_time = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
		$args       = array(
			'after'  => $start_time,
			'before' => $end_time,
		);

		// Test retrieving the stats through the data store.
		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 1,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => array(
					'product_id'    => $product->get_id(),
					'items_sold'    => 4,
					'gross_revenue' => 100.0, // $25 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the query class.
		$query = new WC_Admin_Reports_Products_Query( $args );
		$this->assertEquals( $expected_data, $query->get_data() );
	}

	/**
	 * Test the ordering of results by product name
	 *
	 * @since 3.5.0
	 */
	public function test_order_by_product_name() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'A Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_name( 'B Test Product' );
		$product_2->set_regular_price( 20 );
		$product_2->save();

		$date_created   = time();
		$date_created_2 = $date_created + 5;

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->set_date_created( $date_created );
		$order->save();

		$order_2 = WC_Helper_Order::create_order( 1, $product_2 );
		$order_2->set_status( 'completed' );
		$order_2->set_shipping_total( 10 );
		$order_2->set_discount_total( 20 );
		$order_2->set_discount_tax( 0 );
		$order_2->set_cart_tax( 5 );
		$order_2->set_shipping_tax( 2 );
		$order_2->set_total( 77 ); // $20x4 products + $10 shipping - $20 discount + $7 tax.
		$order_2->set_date_created( $date_created_2 );
		$order_2->save();

		$data_store = new WC_Admin_Reports_Products_Data_Store();
		$start_time = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = date( 'Y-m-d H:00:00', $order_2->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
		// Test retrieving the stats through the data store, default order by date/time desc.
		$args = array(
			'after'  => $start_time,
			'before' => $end_time,
		);

		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => array(
					'product_id'    => $product_2->get_id(),
					'items_sold'    => 4,
					'gross_revenue' => 80.0, // $20 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
				1 => array(
					'product_id'    => $product->get_id(),
					'items_sold'    => 4,
					'gross_revenue' => 100.0, // $25 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the data store, order by product name asc.
		$args = array(
			'after'    => $start_time,
			'before'   => $end_time,
			'order_by' => 'product_name',
			'order'    => 'asc',
		);

		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => array(
					'product_id'    => $product->get_id(),
					'items_sold'    => 4,
					'gross_revenue' => 100.0, // $25 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
				1 => array(
					'product_id'    => $product_2->get_id(),
					'items_sold'    => 4,
					'gross_revenue' => 80.0, // $20 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the query class.
		$query = new WC_Admin_Reports_Products_Query( $args );
		$this->assertEquals( $expected_data, $query->get_data() );
	}

	/**
	 * Test the extended info.
	 *
	 * @since 3.5.0
	 */
	public function test_extended_info() {
		WC_Helper_Reports::reset_stats_dbs();
		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 25 );
		$product->set_low_stock_amount( 5 );
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
		$data_store = new WC_Admin_Reports_Products_Data_Store();
		$start_time = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = date( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
		$args       = array(
			'after'         => $start_time,
			'before'        => $end_time,
			'extended_info' => 1,
		);
		// Test retrieving the stats through the data store.
		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 1,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => array(
					'product_id'    => $product->get_id(),
					'items_sold'    => 4,
					'gross_revenue' => 100.0, // $25 * 4.
					'orders_count'  => 1,
					'extended_info' => array(
						'name'             => $product->get_name(),
						'image'            => $product->get_image(),
						'permalink'        => $product->get_permalink(),
						'price'            => (float) $product->get_price(),
						'stock_status'     => $product->get_stock_status(),
						'stock_quantity'   => $product->get_stock_quantity() - 4, // subtract the ones purchased.
						'low_stock_amount' => $product->get_low_stock_amount(),
					),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );
	}
}
