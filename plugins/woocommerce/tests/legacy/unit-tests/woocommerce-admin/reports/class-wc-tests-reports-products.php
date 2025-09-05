<?php
/**
 * Reports product stats tests.
 *
 * @package WooCommerce\Admin\Tests\Orders
 * @todo Finish up unit testing to verify bug-free product reports.
 */

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\Products\DataStore as ProductsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\ReportCSVExporter;
use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Reports product stats tests class
 *
 * @package WooCommerce\Admin\Tests\Orders
 * @todo Finish up unit testing to verify bug-free product reports.
 */
class WC_Admin_Tests_Reports_Products extends WC_Unit_Test_Case {

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
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new ProductsDataStore();
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
					'items_sold'    => 4,
					'net_revenue'   => 100.0, // $25 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the generic query class.
		$query = new GenericQuery( $args, 'products' );
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
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->set_date_created( $date_created );
		$order->save();

		$order_2 = WC_Helper_Order::create_order( 1, $product_2 );
		$order_2->set_status( OrderStatus::COMPLETED );
		$order_2->set_shipping_total( 10 );
		$order_2->set_discount_total( 20 );
		$order_2->set_discount_tax( 0 );
		$order_2->set_cart_tax( 5 );
		$order_2->set_shipping_tax( 2 );
		$order_2->set_total( 77 ); // $20x4 products + $10 shipping - $20 discount + $7 tax.
		$order_2->set_date_created( $date_created_2 );
		$order_2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new ProductsDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:00:00', $order_2->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
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
					'net_revenue'   => 80.0, // $20 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
				1 => array(
					'product_id'    => $product->get_id(),
					'items_sold'    => 4,
					'net_revenue'   => 100.0, // $25 * 4.
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
					'net_revenue'   => 100.0, // $25 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
				1 => array(
					'product_id'    => $product_2->get_id(),
					'items_sold'    => 4,
					'net_revenue'   => 80.0, // $20 * 4.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the generic query class.
		$query = new GenericQuery( $args, 'products' );
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

		$term = wp_insert_term( 'Test Category', 'product_cat' );
		wp_set_object_terms( $product->get_id(), $term['term_id'], 'product_cat' );

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new ProductsDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
		$args       = array(
			'after'         => $start_time,
			'before'        => $end_time,
			'extended_info' => 1,
		);
		// Test retrieving the stats through the data store.
		$data = $data_store->get_data( $args );
		// Get updated product data.
		$product       = wc_get_product( $product->get_id() );
		$expected_data = (object) array(
			'total'   => 1,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => array(
					'product_id'    => $product->get_id(),
					'items_sold'    => 4,
					'net_revenue'   => 100.0, // $25 * 4.
					'orders_count'  => 1,
					'extended_info' => array(
						'name'             => $product->get_name(),
						'image'            => $product->get_image(),
						'permalink'        => $product->get_permalink(),
						'price'            => (float) $product->get_price(),
						'stock_status'     => $product->get_stock_status(),
						'stock_quantity'   => $product->get_stock_quantity(),
						'manage_stock'     => $product->get_manage_stock(),
						'low_stock_amount' => $product->get_low_stock_amount(),
						'category_ids'     => array_values( $product->get_category_ids() ),
						'sku'              => $product->get_sku(),
					),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );
	}

	/**
	 * Test variable products extended info.
	 *
	 * @since 3.5.0
	 */
	public function test_variable_product_extended_info() {
		WC_Helper_Reports::reset_stats_dbs();
		// Populate all of the data.
		$product = new WC_Product_Variable();
		$product->set_name( 'Test Product' );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 25 );
		$product->set_low_stock_amount( 5 );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_name( 'Test Variation' );
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'pa_color' => 'green' ) );
		$variation->set_sku( 'test-sku' );
		$variation->set_regular_price( 25 );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 25 );
		$variation->set_low_stock_amount( 5 );
		$variation->save();

		$term = wp_insert_term( 'Test Category', 'product_cat' );
		wp_set_object_terms( $product->get_id(), $term['term_id'], 'product_cat' );

		$order = WC_Helper_Order::create_order( 1, $variation );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new ProductsDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
		$args       = array(
			'after'         => $start_time,
			'before'        => $end_time,
			'extended_info' => 1,
		);
		// Test retrieving the stats through the data store.
		$data = $data_store->get_data( $args );
		// Get updated product data.
		$product       = wc_get_product( $product->get_id() );
		$expected_data = (object) array(
			'total'   => 1,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => array(
					'product_id'    => $product->get_id(),
					'items_sold'    => 4,
					'net_revenue'   => 100.0, // $25 * 4.
					'orders_count'  => 1,
					'extended_info' => array(
						'name'             => $product->get_name(),
						'image'            => $product->get_image(),
						'permalink'        => $product->get_permalink(),
						'price'            => (float) $product->get_price(),
						'stock_status'     => $product->get_stock_status(),
						'stock_quantity'   => $product->get_stock_quantity(),
						'manage_stock'     => $product->get_manage_stock(),
						'low_stock_amount' => $product->get_low_stock_amount(),
						'category_ids'     => array_values( $product->get_category_ids() ),
						'sku'              => $product->get_sku(),
						'variations'       => $product->get_children(),
					),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );
	}

	/**
	 * Tests that line item (partial) refunds are reflected in product stats.
	 */
	public function test_populate_and_partial_refund() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		// Add a partial refund to the order.
		foreach ( $order->get_items() as  $item_key => $item_values ) {
			$item_data = $item_values->get_data();
			$refund    = wc_create_refund(
				array(
					'amount'     => 12,
					'order_id'   => $order->get_id(),
					'line_items' => array(
						$item_data['id'] => array(
							'qty'          => 1,
							'refund_total' => 10,
						),
					),
				)
			);
			break;
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new ProductsDataStore();
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
					'items_sold'    => 3,
					'net_revenue'   => 90.0, // $25 * 4 - $10 refund.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the generic query class.
		$query = new GenericQuery( $args, 'products' );
		$this->assertEquals( $expected_data, $query->get_data() );
	}

	/**
	 * Tests that full refunds are reflected in product stats.
	 *
	 * The full refunds here are the ones that change the order status to refunded.
	 * The refund type will be full but there will not be refund order line items.
	 */
	public function test_populate_and_full_refund() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::REFUNDED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new ProductsDataStore();
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
					'items_sold'    => 0,
					'net_revenue'   => 0.0,
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the generic query class.
		$query = new GenericQuery( $args, 'products' );
		$this->assertEquals( $expected_data, $query->get_data() );
	}

	/**
	 * Tests that line items that are all refunded are reflected in product stats.
	 *
	 * This is the case when all line items in an order are refunded each by 100%.
	 * The refund type will be full and there will be refund order line items.
	 */
	public function test_populate_and_refund_all_items_in_an_order() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		// Add a partial refund to the order.
		// This partial refund will refund all items in the order.
		foreach ( $order->get_items() as  $item_key => $item_values ) {
			$item_data = $item_values->get_data();
			$refund    = wc_create_refund(
				array(
					'amount'     => 97,
					'order_id'   => $order->get_id(),
					'line_items' => array(
						$item_data['id'] => array(
							'qty'          => 4,
							'refund_total' => 100,
						),
					),
				)
			);
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new ProductsDataStore();
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
					'items_sold'    => 0,
					'net_revenue'   => 0.0, // $25 * 4 - $100 refund.
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the generic query class.
		$query = new GenericQuery( $args, 'products' );
		$this->assertEquals( $expected_data, $query->get_data() );
	}

	/**
	 * Tests that shipping fee refunds are reflected in product stats.
	 * Shipping fee refund is a partial refund without line items.
	 */
	public function test_populate_and_shipping_fee_refund() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		// Add a partial refund without line items to the order.
		// Shipping fee refund is a partial refund without line items.
		foreach ( $order->get_items() as  $item_key => $item_values ) {
			$item_data = $item_values->get_data();
			$refund    = wc_create_refund(
				array(
					'amount'   => 10,
					'order_id' => $order->get_id(),
				)
			);
			break;
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new ProductsDataStore();
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
					'items_sold'    => 4,
					'net_revenue'   => 100.0,
					'orders_count'  => 1,
					'extended_info' => new ArrayObject(),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the generic query class.
		$query = new GenericQuery( $args, 'products' );
		$this->assertEquals( $expected_data, $query->get_data() );
	}

	/**
	 * Test that filters get properly parsed for CSV exports.
	 * See: https://github.com/woocommerce/woocommerce-admin/issues/5503.
	 *
	 * @since 3.5.0
	 */
	public function test_report_export_arguments() {
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$term = wp_insert_term( 'Unused Category', 'product_cat' );

		$data_store = new ProductsDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() + HOUR_IN_SECONDS );
		$args       = array(
			'after'             => $start_time,
			'before'            => $end_time,
			'category_includes' => array( $term['term_id'] ),
		);

		// Test retrieving the stats through the data store.
		$data = $data_store->get_data( $args );

		$expected_data = (object) array(
			'total'   => 0,
			'pages'   => 0,
			'page_no' => 1,
			'data'    => array(),
		);
		$this->assertEquals( $expected_data, $data );

		// Ensures the report params get mapped and sanitized for exports.
		do_action( 'rest_api_init' );

		$args = array(
			'after'      => $start_time,
			'before'     => $end_time,
			'categories' => $term['term_id'],
		);

		$expected_csv  = chr( 239 ) . chr( 187 ) . chr( 191 );
		$expected_csv .= '"Product title",SKU,"Items sold","N. Revenue",Orders,Category,Variations,Status,Stock';
		$expected_csv .= PHP_EOL;

		$export = new ReportCSVExporter( 'products', $args );
		$export->generate_file();
		if ( method_exists( $export, 'get_headers_row_file' ) ) {
			$actual_csv = $export->get_headers_row_file() . $export->get_file();
		} else {
			$actual_csv = $export->get_file();
		}

		$this->assertEquals( 100, $export->get_percent_complete() );
		$this->assertEquals( 0, $export->get_total_exported() );
		$this->assertEquals( $expected_csv, $actual_csv );
	}

	/**
	 * Tests the data stored in the wc_order_product_lookup table when a full refund is made.
	 *
	 * The full refunds here are the ones that change the order status to refunded.
	 * The refund type will be full but there will not be refund order line items.
	 */
	public function test_sync_order_products_full_refund() {
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		// Enable Tax.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Create a 10% tax rate.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate'          => '10',
			'tax_rate_name'     => 'tax',
			'tax_rate_order'    => '0',
			'tax_rate_shipping' => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product 1 with price $25.
		$product_1 = new WC_Product_Simple();
		$product_1->set_name( 'Test Product 1' );
		$product_1->set_regular_price( 25 );
		$product_1->save();

		// Create product 2 with price $30.
		$product_2 = new WC_Product_Simple();
		$product_2->set_name( 'Test Product 2' );
		$product_2->set_regular_price( 30 );
		$product_2->save();

		// Create an order and add product_1 as the order item. The quantity is set to 4.
		$order = WC_Helper_Order::create_order( 1, $product_1 );

		// Add product_2 as the second order item. The quantity is set to 2.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product_2,
				'quantity' => 2,
				'subtotal' => wc_get_price_excluding_tax( $product_2, array( 'qty' => 2 ) ),
				'total'    => wc_get_price_excluding_tax( $product_2, array( 'qty' => 2 ) ),
			)
		);
		$item->save();
		$order->add_item( $item );

		// Add a flat rate shipping method to the order. The shipping cost is $100.
		$rate          = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '100', array(), 'flat_rate' );
		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$shipping_item->add_meta_data( $key, $value, true );
		}
		// Remove existing shipping items that created by WC_Helper_Order::create_order.
		$order->remove_order_items( 'shipping' );
		$order->add_item( $shipping_item );

		$order->set_status( OrderStatus::COMPLETED );
		$order->calculate_totals( true );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Refund the order completely by changing the order status to refunded.
		$order->set_status( OrderStatus::REFUNDED );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Get the last order item from the order.
		$order_items   = $order->get_items();
		$order_item_id = end( $order_items )->get_id();

		// Get the refund order id.
		$refund_order_id = $order->get_refunds()[0]->get_id();

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_product_lookup WHERE order_item_id = %d AND order_id = %d",
				$order_item_id,
				$refund_order_id
			)
		);

		$this->assertEquals( '-2', $result[0]->product_qty );
		$this->assertEquals( -60.000000, $result[0]->product_net_revenue );    // -($30 product_2 * 2).
		$this->assertEquals( -33.333333, $result[0]->shipping_amount );        // -($100 shipping / 6 total items * 2 product_2 ).
		$this->assertEquals( -3.333333, $result[0]->shipping_tax_amount );     // -($10 shipping tax / 6 total items * 2 product_2 ).
		$this->assertEquals( -6, $result[0]->tax_amount );                     // -($30 product_2 * 10% tax * 2 quantity).
		$this->assertEquals( 0, $result[0]->coupon_amount );
		$this->assertEquals( -102.666667, $result[0]->product_gross_revenue ); // product_net_revenue + shipping_amount + shipping_tax_amount + tax_amount.
	}

	/**
	 * Tests the data stored in the wc_order_product_lookup table when an order item is refunded then a full refund is made.
	 *
	 * The full refunds here are the ones that change the order status to refunded.
	 * The refund type will be full but there will not be refund order line items.
	 */
	public function test_sync_order_products_refund_one_product_then_full_refund() {
		$this->markTestSkipped( 'Mark flaky test for revisit as first assertion occasionally fails with null value.' );
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		// Enable Tax.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Create a 10% tax rate.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate'          => '10',
			'tax_rate_name'     => 'tax',
			'tax_rate_order'    => '0',
			'tax_rate_shipping' => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product 1 with price $25.
		$product_1 = new WC_Product_Simple();
		$product_1->set_name( 'Test Product 1' );
		$product_1->set_regular_price( 25 );
		$product_1->save();

		// Create product 2 with price $30.
		$product_2 = new WC_Product_Simple();
		$product_2->set_name( 'Test Product 2' );
		$product_2->set_regular_price( 30 );
		$product_2->save();

		// Create product 3 with price $40.
		$product_3 = new WC_Product_Simple();
		$product_3->set_name( 'Test Product 3' );
		$product_3->set_regular_price( 40 );
		$product_3->save();

		// Create an order and add product_1 as the order item. The quantity is set to 4.
		$order = WC_Helper_Order::create_order( 1, $product_1 );

		// Add product_2 as the second order item with quantity set to 2.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product_2,
				'quantity' => 2,
				'subtotal' => wc_get_price_excluding_tax( $product_2, array( 'qty' => 2 ) ),
				'total'    => wc_get_price_excluding_tax( $product_2, array( 'qty' => 2 ) ),
			)
		);
		$item->save();
		$order->add_item( $item );

		// Add product_3 as the third order item with quantity set to 3.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product_3,
				'quantity' => 3,
				'subtotal' => wc_get_price_excluding_tax( $product_3, array( 'qty' => 3 ) ),
				'total'    => wc_get_price_excluding_tax( $product_3, array( 'qty' => 3 ) ),
			)
		);
		$item->save();
		$order->add_item( $item );

		// Add a flat rate shipping method to the order. The shipping cost is $100.
		$rate          = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '100', array(), 'flat_rate' );
		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$shipping_item->add_meta_data( $key, $value, true );
		}
		// Remove existing shipping items that created by WC_Helper_Order::create_order.
		$order->remove_order_items( 'shipping' );
		$order->add_item( $shipping_item );

		$order->set_status( OrderStatus::COMPLETED );
		$order->calculate_totals( true );
		$order->save();

		// Refund the first order item completely.
		foreach ( $order->get_items() as  $item_key => $item_values ) {
			$item_data = $item_values->get_data();
			$refund    = wc_create_refund(
				array(
					'amount'     => 100,
					'order_id'   => $order->get_id(),
					'line_items' => array(
						$item_data['id'] => array(
							'qty'          => 4,
							'refund_total' => 100,
						),
					),
				)
			);
			break;
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Refund the order completely by changing the order status to refunded.
		$order->set_status( OrderStatus::REFUNDED );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Get the last order item (product_3) from the order.
		$order_items   = $order->get_items();
		$order_item_id = end( $order_items )->get_id();

		// Get the last refund order id.
		$refunds         = $order->get_refunds();
		$refund_order_id = end( $refunds )->get_id();

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_product_lookup WHERE order_item_id = %d AND order_id = %d",
				$order_item_id,
				$refund_order_id
			)
		);

		$this->assertEquals( '-3', $result[0]->product_qty );
		$this->assertEquals( -120.000000, $result[0]->product_net_revenue ); // -($40 product_3 * 3).
		$this->assertEquals( -60.000000, $result[0]->shipping_amount );      // -($100 shipping / ( 9 total items - 4 refunded items ) * 3 product_3 ).
		$this->assertEquals( -6.000000, $result[0]->shipping_tax_amount );   // -($10 shipping tax / ( 9 total items - 4 refunded items ) * 3 product_3 ).
		$this->assertEquals( -12, $result[0]->tax_amount );                  // -($40 product_3 * 10% tax * 3 quantity ).
		$this->assertEquals( 0, $result[0]->coupon_amount );
		$this->assertEquals( -198, $result[0]->product_gross_revenue );      // product_net_revenue + shipping_amount + shipping_tax_amount + tax_amount.
	}

	/**
	 * Tests the data stored in the wc_order_product_lookup table when a full refund is made.
	 *
	 * The full refunds here are the ones that change the order status to refunded.
	 * The refund type will be full but there will not be refund order line items.
	 */
	public function test_sync_order_products_full_refund_compound_tax() {
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		// Enable Tax.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Create a 10% tax rate for all countries.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate'          => '10',
			'tax_rate_name'     => 'tax',
			'tax_rate_shipping' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_priority' => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create a 5% tax rate for US.
		$us_tax_rate = array(
			'tax_rate_country'  => 'US',
			'tax_rate'          => '5',
			'tax_rate_name'     => 'US tax',
			'tax_rate_shipping' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_order'    => '2',
			'tax_rate_priority' => '2',
		);
		WC_Tax::_insert_tax_rate( $us_tax_rate );

		// Create product 1 with price $25.
		$product_1 = new WC_Product_Simple();
		$product_1->set_name( 'Test Product 1' );
		$product_1->set_regular_price( 25 );
		$product_1->save();

		// Create product 2 with price $30.
		$product_2 = new WC_Product_Simple();
		$product_2->set_name( 'Test Product 2' );
		$product_2->set_regular_price( 30 );
		$product_2->save();

		// Create an order and add product_1 as the order item. The quantity is set to 4.
		$order = WC_Helper_Order::create_order( 1, $product_1 );

		// Add product_2 as the second order item. The quantity is set to 2.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product_2,
				'quantity' => 2,
				'subtotal' => wc_get_price_excluding_tax( $product_2, array( 'qty' => 2 ) ),
				'total'    => wc_get_price_excluding_tax( $product_2, array( 'qty' => 2 ) ),
			)
		);
		$item->save();
		$order->add_item( $item );

		// Add a flat rate shipping method to the order. The shipping cost is $100.
		$rate          = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '100', array(), 'flat_rate' );
		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$shipping_item->add_meta_data( $key, $value, true );
		}
		// Remove existing shipping items that created by WC_Helper_Order::create_order.
		$order->remove_order_items( 'shipping' );
		$order->add_item( $shipping_item );

		$order->set_status( OrderStatus::COMPLETED );
		$order->calculate_totals( true );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Refund the order completely by changing the order status to refunded.
		$order->set_status( OrderStatus::REFUNDED );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Get the last order item from the order.
		$order_items   = $order->get_items();
		$order_item_id = end( $order_items )->get_id();

		// Get the refund order id.
		$refund_order_id = $order->get_refunds()[0]->get_id();

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_product_lookup WHERE order_item_id = %d AND order_id = %d",
				$order_item_id,
				$refund_order_id
			)
		);

		$this->assertEquals( '-2', $result[0]->product_qty );
		$this->assertEquals( -60.000000, $result[0]->product_net_revenue ); // -($30 product_2 * 2).
		$this->assertEquals( -33.333333, $result[0]->shipping_amount );     // -($100 shipping / 6 total items * 2 product_2 ).

		// 10% tax: $100 shipping * 0.1 = $10.
		// 5% compound tax: $100 shipping * 1.1 * 0.05 = $5.5.
		// -($10 + $5.5) / 6 total items * 2 product_2 = -$5.166667.
		$this->assertEquals( -5.166667, $result[0]->shipping_tax_amount );

		// 10% tax: $30 product_2 * 0.1 = $3.
		// 5% compound tax: $30 product_2 * 1.1 * 0.05 = $1.65.
		// -($3 + $1.65) * 2 quantity = -$9.3.
		$this->assertEquals( -9.3, $result[0]->tax_amount );

		$this->assertEquals( 0, $result[0]->coupon_amount );
		$this->assertEquals( -107.8, $result[0]->product_gross_revenue ); // product_net_revenue + shipping_amount + shipping_tax_amount + tax_amount.
	}
}
