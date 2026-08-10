<?php
/**
 * Orders Report tests.
 *
 * @package WooCommerce\Admin\Tests\Orders
 */

use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as OrdersDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

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
					'status'           => OrderStatus::COMPLETED,
					'net_total'        => 65.0,
					'total_sales'      => 75.0,
					'num_items_sold'   => 5,
					'customer_id'      => $data->data[0]['customer_id'], // Not under test.
					'customer_type'    => 'new',
					'date_created'     => $data->data[0]['date_created'], // Not under test.
					'date_created_gmt' => $data->data[0]['date_created_gmt'], // Not under test.
					'date'             => $data->data[0]['date'], // Not under test.
					'extended_info'    => array(
						'products'    => array(
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
						'coupons'     => array(),
						'customer'    => $data->data[0]['extended_info']['customer'], // Not under test.
						'attribution' => array(
							'origin' => 'Unknown',
						),
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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

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
				OrderStatus::REFUNDED,
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
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$order2->set_total( 45 ); // ( 1 * 10 ) + 25 + 10 shipping (in helper).
		$order2->set_status( OrderStatus::COMPLETED );
		$order2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->apply_coupon( $coupon );
		$order->calculate_totals();
		$order->save();

		$order_2 = WC_Helper_Order::create_order( 1, $simple_product );
		$order_2->set_total( 25 );
		$order_2->set_status( OrderStatus::COMPLETED );
		$order_2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

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

	/**
	 * Creates a completed order for a guest customer, using a billing email that is not attached to
	 * any registered user.
	 *
	 * @param WC_Product $product Product to add to the order.
	 * @param string     $email   Billing email used to identify the guest customer.
	 * @return WC_Order
	 */
	private function create_guest_order( $product, $email ) {
		$order = WC_Helper_Order::create_order( 0, $product );
		$order->set_billing_email( $email );
		$order->set_total( 25 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		return $order;
	}

	/**
	 * Returns the customer type of every row in the report, keyed by order ID.
	 *
	 * @param WC_Order $order Order used to derive the reporting time frame.
	 * @param array    $args  Extra query arguments.
	 * @return array
	 */
	private function get_customer_types_by_order_id( $order, $args = array() ) {
		$data_store = new OrdersDataStore();
		$data       = $data_store->get_data(
			array_merge(
				array(
					'after'  => gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() ),
					'before' => gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() ),
				),
				$args
			)
		);

		return wp_list_pluck( $data->data, 'customer_type', 'order_id' );
	}

	/**
	 * @testdox Should report a refund of a customer's first order as a new customer.
	 *
	 * Refunds are stored without a customer type of their own, so they should report the customer
	 * type of the order they refund instead of always being reported as returning.
	 *
	 * See: https://github.com/woocommerce/woocommerce/issues/33410.
	 */
	public function test_refund_of_first_order_is_reported_as_new_customer() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order = $this->create_guest_order( $simple_product, 'guest-33410@example.org' );

		$refund = wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_types = $this->get_customer_types_by_order_id( $order );

		$this->assertEquals( 'new', $customer_types[ $order->get_id() ], 'A guest customer\'s first order should be reported as new' );
		$this->assertEquals( 'new', $customer_types[ $refund->get_id() ], 'A refund should be reported with the customer type of the refunded order' );
	}

	/**
	 * @testdox Should report a refund of a returning customer's order as a returning customer.
	 */
	public function test_refund_of_later_order_is_reported_as_returning_customer() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$first_order  = $this->create_guest_order( $simple_product, 'guest-33410-returning@example.org' );
		$second_order = $this->create_guest_order( $simple_product, 'guest-33410-returning@example.org' );

		$refund = wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $second_order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_types = $this->get_customer_types_by_order_id( $first_order );

		$this->assertEquals( 'new', $customer_types[ $first_order->get_id() ], 'The first order should be reported as new' );
		$this->assertEquals( 'returning', $customer_types[ $second_order->get_id() ], 'The second order should be reported as returning' );
		$this->assertEquals( 'returning', $customer_types[ $refund->get_id() ], 'A refund should be reported with the customer type of the refunded order' );
	}

	/**
	 * @testdox Should keep refunds together with the order they refund when filtering by customer type.
	 */
	public function test_customer_type_filter_includes_refunds_of_matching_orders() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order = $this->create_guest_order( $simple_product, 'guest-33410-filter@example.org' );

		$refund = wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$new_customer_rows = $this->get_customer_types_by_order_id( $order, array( 'customer_type' => 'new' ) );

		$this->assertEqualSets(
			array( $order->get_id(), $refund->get_id() ),
			array_keys( $new_customer_rows ),
			'Filtering by new customers should return the order and its refund'
		);

		$returning_customer_rows = $this->get_customer_types_by_order_id( $order, array( 'customer_type' => 'returning' ) );

		$this->assertEmpty( $returning_customer_rows, 'Filtering by returning customers should not return the order or its refund' );
	}
}
