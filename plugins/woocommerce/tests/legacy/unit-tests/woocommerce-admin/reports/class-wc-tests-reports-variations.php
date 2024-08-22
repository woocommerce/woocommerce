<?php
/**
 * Reports order stats tests.
 *
 * @package WooCommerce\Admin\Tests\Orders
 * @todo Finish up unit testing to verify bug-free order reports.
 */

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\Variations\DataStore as VariationsDataStore;

/**
 * Reports order stats tests class.
 *
 * @package WooCommerce\Admin\Tests\Orders
 * @todo Finish up unit testing to verify bug-free order reports.
 */
class WC_Admin_Tests_Reports_Variations extends WC_Unit_Test_Case {

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
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
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

		// Test retrieving the stats through the generic query class.
		$query = new GenericQuery( $args, 'variations' );
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
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
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

	/**
	 * Test the attribute filter.
	 */
	public function test_attribute_filtering() {
		WC_Helper_Reports::reset_stats_dbs();

		$global_attribute_data = WC_Helper_Product::create_attribute( 'size', array( 'Small', 'Medium', 'Large' ) );

		$local_attribute = new WC_Product_Attribute();
		$local_attribute->set_id( 0 );
		$local_attribute->set_name( 'Color' );
		$local_attribute->set_options( array( 'red', 'green', 'blue' ) );
		$local_attribute->set_visible( true );
		$local_attribute->set_variation( true );

		$global_attribute = new WC_Product_Attribute();
		$global_attribute->set_id( $global_attribute_data['attribute_id'] );
		$global_attribute->set_name( $global_attribute_data['attribute_taxonomy'] );
		$global_attribute->set_options( $global_attribute_data['term_ids'] );
		$global_attribute->set_visible( true );
		$global_attribute->set_variation( true );

		// Create a variable product with a variation that allows "any x".
		$product = new WC_Product_Variable();
		$product->set_name( 'Shirt' );
		$product->set_regular_price( 25 );
		$product->set_attributes( array( $global_attribute, $local_attribute ) );
		$product->save();

		$green = new WC_Product_Variation();
		$green->set_name( 'Green Shirt' );
		$green->set_parent_id( $product->get_id() );
		$green->set_attributes( array( 'color' => 'green' ) );
		$green->save();

		$red = new WC_Product_Variation();
		$red->set_name( 'Red Shirt' );
		$red->set_parent_id( $product->get_id() );
		$red->set_attributes( array( 'color' => 'red' ) );
		$red->save();

		// Create separate orders for two green shirts of different sizes.
		$line_item_1 = new WC_Order_Item_Product();
		$line_item_1->set_product( $green );
		$line_item_1->add_meta_data( 'pa_size', 'Small', true );

		$order_1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order_1->add_item( $line_item_1 );
		$order_1->set_status( 'completed' );
		$order_1->save();

		$line_item_2 = new WC_Order_Item_Product();
		$line_item_2->set_product( $green );
		$line_item_2->add_meta_data( 'pa_size', 'Large', true );

		$order_2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order_2->add_item( $line_item_2 );
		$order_2->set_status( 'completed' );
		$order_2->save();

		// Order a large red shirt.
		$line_item_3 = new WC_Order_Item_Product();
		$line_item_3->set_product( $red );
		$line_item_3->add_meta_data( 'pa_size', 'Large', true );

		$order_3 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order_3->add_item( $line_item_3 );
		$order_3->set_status( 'completed' );
		$order_3->save();

		WC_Helper_Queue::run_all_pending();

		$data_store = new VariationsDataStore();

		// Two orders of the green shirt.
		$data = $data_store->get_data(
			array(
				'attribute_is' => array(
					array( 'color', 'green' ),
				),
			)
		);

		$this->assertEquals( 1, $data->total );
		$this->assertEquals( 2, $data->data[0]['items_sold'] );
		$this->assertEquals( 2, $data->data[0]['orders_count'] );
		$this->assertEquals( $green->get_id(), $data->data[0]['variation_id'] );

		// One order of a Large green shirt.
		$data = $data_store->get_data(
			array(
				'attribute_is' => array(
					array( $global_attribute_data['attribute_id'], $global_attribute_data['term_ids'][2] ),
					array( 'color', 'green' ),
				),
			)
		);

		$this->assertEquals( 1, $data->total );
		$this->assertEquals( 1, $data->data[0]['items_sold'] );
		$this->assertEquals( 1, $data->data[0]['orders_count'] );
		$this->assertEquals( $green->get_id(), $data->data[0]['variation_id'] );

		// Two large shirts sold.
		$data = $data_store->get_data(
			array(
				'attribute_is' => array(
					array( $global_attribute_data['attribute_id'], $global_attribute_data['term_ids'][2] ),
				),
				'orderby'      => 'sku',
				'order'        => 'desc',
			)
		);

		$this->assertEquals( 2, $data->total );
		$this->assertEquals( 1, $data->data[0]['items_sold'] );
		$this->assertEquals( 1, $data->data[0]['orders_count'] );
		$this->assertEquals( $green->get_id(), $data->data[0]['variation_id'] );
		$this->assertEquals( 1, $data->data[1]['items_sold'] );
		$this->assertEquals( 1, $data->data[1]['orders_count'] );
		$this->assertEquals( $red->get_id(), $data->data[1]['variation_id'] );

		// All orders.
		$data = $data_store->get_data(
			array(
				'match'        => 'any',
				'attribute_is' => array(
					array( $global_attribute_data['attribute_id'], $global_attribute_data['term_ids'][2] ),
					array( 'color', 'green' ),
				),
				'orderby'      => 'items_sold',
				'order'        => 'desc',
			)
		);

		$this->assertEquals( 2, $data->total );
		$this->assertEquals( 2, $data->data[0]['items_sold'] );
		$this->assertEquals( 2, $data->data[0]['orders_count'] );
		$this->assertEquals( $green->get_id(), $data->data[0]['variation_id'] );
		$this->assertEquals( 1, $data->data[1]['items_sold'] );
		$this->assertEquals( 1, $data->data[1]['orders_count'] );
		$this->assertEquals( $red->get_id(), $data->data[1]['variation_id'] );
	}
}
