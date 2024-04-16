<?php
/**
 * Orders Report tests.
 *
 * @package WooCommerce\Admin\Tests\Orders
 */

use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as OrdersDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Query as OrdersQuery;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * Class WC_Admin_Tests_Reports_Orders
 */
class WC_Admin_Tests_Reports_Orders extends WC_Unit_Test_Case {
	/**
	 * Test that extended info handles variations correctly.
	 */
	public function test_extended_info() {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$parent_product = new WC_Product_Variable();
		$parent_product->set_name( 'Variable Product' );
		$parent_product->set_regular_price( 25 );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'pa_color' );
		$attribute->set_options( explode( WC_DELIMITER, 'green | red' ) );
		$attribute->set_visible( false );
		$attribute->set_variation( true );
		$parent_product->set_attributes( array( $attribute ) );
		$parent_product->save();

		$variation = new WC_Product_Variation();
		$variation->set_name( 'Test Variation' );
		$variation->set_parent_id( $parent_product->get_id() );
		$variation->set_regular_price( 10 );
		$variation->set_attributes( array( 'pa_color' => 'green' ) );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 25 );
		$variation->save();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order = WC_Helper_Order::create_order( 1, $variation );
		// Add simple product.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $simple_product,
				'quantity' => 1,
				'subtotal' => wc_get_price_excluding_tax( $simple_product, array( 'qty' => 1 ) ),
				'total'    => wc_get_price_excluding_tax( $simple_product, array( 'qty' => 1 ) ),
			)
		);
		$item->save();
		$order->add_item( $item );
		// Fix totals.
		$order->set_total( 75 ); // ( 4 * 10 ) + 25 + 10 shipping (in helper).
		$order->set_status( 'completed' );
		$order->save();

		WC_Helper_Queue::run_all_pending();

		$data_store = new OrdersDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );
		$args       = array(
			'after'         => $start_time,
			'before'        => $end_time,
			'extended_info' => 1,
		);
		// Test retrieving the stats through the data store.
		$data     = $data_store->get_data( $args );
		$expected = (object) array(
			'total'   => 1,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => array(
					'order_id'         => $order->get_id(),
					'parent_id'        => 0,
					'status'           => 'completed',
					'net_total'        => 65.0,
					'total_sales'      => 75.0,
					'num_items_sold'   => 5,
					'customer_id'      => $data->data[0]['customer_id'], // Not under test.
					'customer_type'    => 'new',
					'date_created'     => $data->data[0]['date_created'], // Not under test.
					'date_created_gmt' => $data->data[0]['date_created_gmt'], // Not under test.
					'date'             => $data->data[0]['date'], // Not under test.
					'extended_info'    => array(
						'products' => array(
							array(
								'id'       => $variation->get_id(),
								'name'     => $variation->get_name(),
								'quantity' => 4,
							),
							array(
								'id'       => $simple_product->get_id(),
								'name'     => $simple_product->get_name(),
								'quantity' => 1,
							),
						),
						'coupons'  => array(),
						'customer' => $data->data[0]['extended_info']['customer'], // Not under test.
						'origin'   => 'Unknown',
					),
				),
			),
		);
		$this->assertEquals( $expected, $data );
	}

	/**
	 * Test that refunded orders in list have products.
	 */
	public function test_products_in_orders() {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product 2' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order = WC_Helper_Order::create_order( 1, $simple_product );

		$order->set_total( 25 );
		$order->set_status( 'completed' );
		$order->save();

		wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending();

		$data_store = new OrdersDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );
		$args       = array(
			'after'         => $start_time,
			'before'        => $end_time,
			'extended_info' => 1,
		);
		// Retrieving orders with products through the data store.
		$data     = $data_store->get_data( $args );
		$expected = array(
			array(
				'id'       => $simple_product->get_id(),
				'name'     => $simple_product->get_name(),
				'quantity' => '4',
			),
		);
		$this->assertEquals( $expected, $data->data[0]['extended_info']['products'] );

		$args = array(
			'after'         => $start_time,
			'before'        => $end_time,
			'extended_info' => 1,
			'status_is'     => array(
				'refunded',
			),
		);
		// Retrieving an order with products (when receiving a single refunded order).
		$data_2 = $data_store->get_data( $args );
		$this->assertEquals( $expected, $data_2->data[0]['extended_info']['products'] );
	}

	/**
	 * Test that product includes count returns correctly when multiple variations of the same product is added.
	 */
	public function test_product_and_variation_includes_count() {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$parent_product = new WC_Product_Variable();
		$parent_product->set_name( 'Variable Product' );
		$parent_product->set_regular_price( 25 );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'pa_color' );
		$attribute->set_options( explode( WC_DELIMITER, 'green | red' ) );
		$attribute->set_visible( false );
		$attribute->set_variation( true );
		$parent_product->set_attributes( array( $attribute ) );
		$parent_product->save();

		$variation = new WC_Product_Variation();
		$variation->set_name( 'Test Variation - Green' );
		$variation->set_parent_id( $parent_product->get_id() );
		$variation->set_regular_price( 10 );
		$variation->set_attributes( array( 'pa_color' => 'green' ) );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 25 );
		$variation->save();

		$variation2 = new WC_Product_Variation();
		$variation2->set_name( 'Test Variation - Red' );
		$variation2->set_parent_id( $parent_product->get_id() );
		$variation2->set_regular_price( 10 );
		$variation2->set_attributes( array( 'pa_color' => 'red' ) );
		$variation2->set_manage_stock( true );
		$variation2->set_stock_quantity( 25 );
		$variation2->save();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order  = WC_Helper_Order::create_order( 1, $variation );
		$order2 = WC_Helper_Order::create_order( 1, $simple_product );
		// Add simple product.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $simple_product,
				'quantity' => 1,
				'subtotal' => wc_get_price_excluding_tax( $simple_product, array( 'qty' => 1 ) ),
				'total'    => wc_get_price_excluding_tax( $simple_product, array( 'qty' => 1 ) ),
			)
		);
		$item->save();
		$order->add_item( $item );
		$item2 = new WC_Order_Item_Product();
		$item2->set_props(
			array(
				'product'  => $variation2,
				'quantity' => 2,
				'subtotal' => wc_get_price_excluding_tax( $variation2, array( 'qty' => 1 ) ),
				'total'    => wc_get_price_excluding_tax( $variation2, array( 'qty' => 1 ) ),
			)
		);
		$item2->save();
		$order->add_item( $item2 );
		// Fix totals.
		$order->set_total( 95 ); // ( 6 * 10 ) + 25 + 10 shipping (in helper).
		$order->set_status( 'completed' );
		$order->save();

		$order2->set_total( 45 ); // ( 1 * 10 ) + 25 + 10 shipping (in helper).
		$order2->set_status( 'completed' );
		$order2->save();

		WC_Helper_Queue::run_all_pending();

		$data_store = new OrdersDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order2->get_date_created()->getOffsetTimestamp() );
		$args       = array(
			'after'            => $start_time,
			'before'           => $end_time,
			'extended_info'    => 1,
			'product_includes' => array( $parent_product->get_id() ),
		);
		// Test retrieving the stats through the data store.
		$data = $data_store->get_data( $args );
		$this->assertEquals( 1, $data->total );

		$args_variation = array(
			'after'              => $start_time,
			'before'             => $end_time,
			'variation_includes' => array( $variation->get_id() ),
		);
		// Test retrieving the stats through the data store.
		$data_variation = $data_store->get_data( $args_variation );
		$this->assertEquals( 1, $data_variation->total );
	}

	/**
	 * Test that excluding specific coupons doesn't exclude orders without coupons.
	 * See: https://github.com/woocommerce/woocommerce-admin/issues/6824.
	 */
	public function test_coupon_exclusion_includes_orders_without_coupons() {
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		$coupon = WC_Helper_Coupon::create_coupon( 'coupon_1' );
		$coupon->set_amount( 2 );
		$coupon->save();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order = WC_Helper_Order::create_order( 1, $simple_product );
		$order->set_total( 25 );
		$order->set_status( 'completed' );
		$order->apply_coupon( $coupon );
		$order->calculate_totals();
		$order->save();

		$order_2 = WC_Helper_Order::create_order( 1, $simple_product );
		$order_2->set_total( 25 );
		$order_2->set_status( 'completed' );
		$order_2->save();

		WC_Helper_Queue::run_all_pending();

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		$data_store = new OrdersDataStore();
		$data       = $data_store->get_data(
			array(
				'after'           => $start_time,
				'before'          => $end_time,
				'coupon_excludes' => array( $coupon->get_id() ),
			)
		);

		$this->assertEquals( 1, $data->total );
		$this->assertEquals( $order_2->get_id(), $data->data[0]['order_id'] );
	}
}
