<?php
/**
 * Reports product stats tests.
 *
 * @package WooCommerce\Admin\Tests\Orders
 * @todo Finish up unit testing to verify bug-free product reports.
 */

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\Products\DataStore as ProductsDataStore;
use Automattic\WooCommerce\Admin\ReportCSVExporter;

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
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending();

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

		WC_Helper_Queue::run_all_pending();

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
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending();

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
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending();

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
	 * Tests that line item refunds are reflected in product stats.
	 */
	public function test_populate_and_refund() {
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

		WC_Helper_Queue::run_all_pending();

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
		$order->set_status( 'completed' );
		$order->set_shipping_total( 10 );
		$order->set_discount_total( 20 );
		$order->set_discount_tax( 0 );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		$order->set_total( 97 ); // $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->save();

		WC_Helper_Queue::run_all_pending();

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
}
