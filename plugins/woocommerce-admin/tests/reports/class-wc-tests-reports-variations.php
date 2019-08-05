<?php
/**
 * Reports order stats tests.
 *
 * @package WooCommerce\Tests\Orders
 * @todo Finish up unit testing to verify bug-free order reports.
 */

use \Automattic\WooCommerce\Admin\API\Reports\Variations\DataStore as VariationsDataStore;
 
/**
 * Reports order stats tests class.
 *
 * @package WooCommerce\Tests\Orders
 * @todo Finish up unit testing to verify bug-free order reports.
 */
class WC_Tests_Reports_Variations extends WC_Unit_Test_Case {

	/**
	 * Test the calculations and querying works correctly for the base case of 1 order.
	 *
	 * @since 3.5.0
	 */
	public function test_populate_and_query() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Variable();
		$product->set_name( 'Test Product' );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_name( 'Test Variation' );
		$variation->set_parent_id( $product->get_id() );
		$variation->set_regular_price( 10 );
		$variation->set_attributes( array( 'pa_color' => 'green' ) );
		$variation->set_sku( 'test-sku' );
		$variation->save();

		$order = WC_Helper_Order::create_order( 1, $variation );
		$order->set_status( 'completed' );
		$order->save();

		WC_Helper_Queue::run_all_pending();

		$data_store = new VariationsDataStore();
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
					'variation_id'  => $variation->get_id(),
					'items_sold'    => 4,
					'net_revenue'   => 40.0, // $10 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the query class.
		$query = new WC_Admin_Reports_Variations_Query( $args );
		$this->assertEquals( $expected_data, $query->get_data() );
	}

	/**
	 * Test the extended ifo.
	 *
	 * @since 3.5.0
	 */
	public function test_extended_info() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Variable();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'pa_color' );
		$attribute->set_options( explode( WC_DELIMITER, 'green | red' ) );
		$attribute->set_visible( false );
		$attribute->set_variation( true );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_name( 'Test Variation' );
		$variation->set_parent_id( $product->get_id() );
		$variation->set_regular_price( 10 );
		$variation->set_attributes( array( 'pa_color' => 'green' ) );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 25 );
		$variation->save();

		$order = WC_Helper_Order::create_order( 1, $variation );
		$order->set_status( 'completed' );
		$order->save();

		WC_Helper_Queue::run_all_pending();

		$data_store = new VariationsDataStore();
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
					'variation_id'  => $variation->get_id(),
					'items_sold'    => 4,
					'net_revenue'   => 40.0, // $10 * 4.
					'orders_count'  => 1,
					'extended_info' => array(
						'name'             => $variation->get_name(),
						'image'            => $variation->get_image(),
						'permalink'        => $variation->get_permalink(),
						'price'            => (float) $variation->get_price(),
						'stock_status'     => $variation->get_stock_status(),
						'stock_quantity'   => $variation->get_stock_quantity() - 4, // subtract the ones purchased.
						'low_stock_amount' => get_option( 'woocommerce_notify_low_stock_amount' ),
						'attributes'       => array(
							0 => array(
								'id'     => 0,
								'name'   => 'color',
								'option' => 'green',
							),
						),
						'sku'              => $variation->get_sku(),
					),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );
	}
}
