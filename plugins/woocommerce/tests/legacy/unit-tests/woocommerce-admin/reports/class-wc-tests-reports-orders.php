<?php
/**
 * Orders Report tests.
 *
 * @package WooCommerce\Admin\Tests\Orders
 */

use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as OrdersDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;

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
	 * @param WC_Product $product      Product to add to the order.
	 * @param string     $email        Billing email used to identify the guest customer.
	 * @param int|null   $date_created Optional timestamp to place the order at.
	 * @return WC_Order
	 */
	private function create_guest_order( $product, $email, $date_created = null ) {
		$order = WC_Helper_Order::create_order( 0, $product );
		$order->set_billing_email( $email );
		$order->set_total( 25 );
		$order->set_status( OrderStatus::COMPLETED );

		if ( $date_created ) {
			// The report is filtered by date_paid, so both dates are pinned to keep the
			// reporting time frame of the order independent of when the test runs.
			$order->set_date_created( $date_created );
			$order->set_date_paid( $date_created );
		}

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
	 * The customer type filter still matches orders only, so that the report table keeps agreeing
	 * with the totals from the orders stats endpoint, which excludes refunds from that filter too.
	 *
	 * @testdox Should match only orders, not their refunds, when filtering by customer type.
	 */
	public function test_customer_type_filter_matches_orders_only() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order = $this->create_guest_order( $simple_product, 'guest-33410-filter@example.org' );

		wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$new_customer_rows = $this->get_customer_types_by_order_id( $order, array( 'customer_type' => 'new' ) );

		$this->assertEqualSets(
			array( $order->get_id() ),
			array_keys( $new_customer_rows ),
			'Filtering by new customers should return the order without its refund'
		);

		$returning_customer_rows = $this->get_customer_types_by_order_id( $order, array( 'customer_type' => 'returning' ) );

		$this->assertEmpty( $returning_customer_rows, 'Filtering by returning customers should not return the order or its refund' );
	}

	/**
	 * @testdox Should report the customer type of refunds when filtering by refunds.
	 */
	public function test_refunds_filter_returns_refunds_with_their_customer_type() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order = $this->create_guest_order( $simple_product, 'guest-33410-refunds@example.org' );

		$refund = wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$refund_rows = $this->get_customer_types_by_order_id( $order, array( 'refunds' => 'all' ) );

		$this->assertEqualSets( array( $refund->get_id() ), array_keys( $refund_rows ), 'Filtering by refunds should return the refund only' );
		$this->assertEquals( 'new', $refund_rows[ $refund->get_id() ], 'A refund should be reported with the customer type of the refunded order' );

		$order_rows = $this->get_customer_types_by_order_id( $order, array( 'refunds' => 'none' ) );

		$this->assertEqualSets( array( $order->get_id() ), array_keys( $order_rows ), 'Excluding refunds should return the order only' );
	}

	/**
	 * Recalculating a customer's first order runs an UPDATE across all of the customer's stats
	 * rows, which must not overwrite the NULL returning_customer of refund rows — the report
	 * relies on that NULL to fall back to the refunded order's customer type.
	 *
	 * @testdox Should keep reporting a refund with the refunded order's customer type after the customer's first order is recalculated.
	 */
	public function test_refund_keeps_customer_type_after_first_order_recalculation() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$first_order  = $this->create_guest_order( $simple_product, 'guest-33410-recalc@example.org' );
		$second_order = $this->create_guest_order( $simple_product, 'guest-33410-recalc@example.org' );

		$refund = wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $second_order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Cancelling the first order makes the second order the customer's first order,
		// triggering the returning_customer recalculation across the customer's rows.
		$first_order->set_status( OrderStatus::CANCELLED );
		$first_order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_types = $this->get_customer_types_by_order_id( $second_order );

		$this->assertEquals( 'new', $customer_types[ $second_order->get_id() ], 'The second order should be reported as new once the first order is cancelled' );
		$this->assertEquals( 'new', $customer_types[ $refund->get_id() ], 'A refund should keep reporting the customer type of the refunded order after the recalculation' );

		$returning_customer_rows = $this->get_customer_types_by_order_id( $second_order, array( 'customer_type' => 'returning' ) );

		$this->assertEmpty( $returning_customer_rows, 'Filtering by returning customers should not match the refund after the recalculation' );
	}

	/**
	 * Orders can be given a parent through set_parent_id(), so a parent alone does not make a
	 * stats row a refund: such orders still count as orders of the customer.
	 *
	 * @testdox Should keep counting a customer's orders that have a parent order.
	 */
	public function test_orders_with_a_parent_are_still_the_customers_orders() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$parent_order = $this->create_guest_order( $simple_product, 'guest-parent-order@example.org' );

		$first_order = $this->create_guest_order( $simple_product, 'guest-child-order@example.org' );
		$first_order->set_parent_id( $parent_order->get_id() );
		$first_order->save();

		$second_order = $this->create_guest_order( $simple_product, 'guest-child-order@example.org' );
		$second_order->set_parent_id( $parent_order->get_id() );
		$second_order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_types = $this->get_customer_types_by_order_id( $first_order );

		$this->assertEquals( 'new', $customer_types[ $first_order->get_id() ], "The customer's first order should be reported as new" );
		$this->assertEquals( 'returning', $customer_types[ $second_order->get_id() ], 'The second order of the customer should be reported as returning' );
	}

	/**
	 * A refund carries the customer of the order it refunds, but it is not one of that
	 * customer's orders and must never be picked as their first one — the recalculation would
	 * then flag every actual order of the customer as returning.
	 *
	 * @testdox Should keep reporting a customer's only order as new after its date is moved past its refund.
	 */
	public function test_refund_is_not_treated_as_the_customers_first_order() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$order = $this->create_guest_order( $simple_product, 'guest-refund-first-order@example.org' );

		wc_create_refund(
			array(
				'amount'   => 25,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Moving the order past its refund makes the refund the oldest row of the customer,
		// which triggers the first order recalculation.
		$moved_to = $order->get_date_created()->getTimestamp() + HOUR_IN_SECONDS;
		$order->set_date_created( $moved_to );
		$order->set_date_paid( $moved_to );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_types = $this->get_customer_types_by_order_id( $order );

		$this->assertEquals( 'new', $customer_types[ $order->get_id() ], "The customer's only order should still be reported as new" );
	}

	/**
	 * Orders are imported asynchronously and stats dates only have second resolution, so two
	 * orders placed within the same second carry the same date_created. Which one is the
	 * customer's first order has to be decided by ID rather than by import order.
	 *
	 * @testdox Should report the same customer types when a customer's orders are imported newest first.
	 */
	public function test_customer_type_does_not_depend_on_the_order_import_order() {
		WC_Helper_Reports::reset_stats_dbs();

		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Simple Product' );
		$simple_product->set_regular_price( 25 );
		$simple_product->save();

		$date_created = time();
		$first_order  = $this->create_guest_order( $simple_product, 'guest-import-order@example.org', $date_created );
		$second_order = $this->create_guest_order( $simple_product, 'guest-import-order@example.org', $date_created );

		// Import the newer order first, which is what an unordered queue can do.
		WC_Helper_Queue::cancel_all_pending();
		OrdersScheduler::import( $second_order->get_id() );
		OrdersScheduler::import( $first_order->get_id() );

		$customer_types = $this->get_customer_types_by_order_id( $first_order );

		$this->assertEquals( 'new', $customer_types[ $first_order->get_id() ], 'The first order should be reported as new even when imported last' );
		$this->assertEquals( 'returning', $customer_types[ $second_order->get_id() ], 'The second order should be reported as returning even when imported first' );
	}
}
