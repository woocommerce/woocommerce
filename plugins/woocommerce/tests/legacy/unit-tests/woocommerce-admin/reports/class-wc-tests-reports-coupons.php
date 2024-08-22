<?php
/**
 * Reports coupons tests.
 *
 * @package WooCommerce\Admin\Tests\Coupons
 */

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\ReportCSVExporter;
use Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore as CouponsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * Class WC_Admin_Tests_Reports_Coupons
 */
class WC_Admin_Tests_Reports_Coupons extends WC_Unit_Test_Case {

	/**
	 * Test the calculations and querying works correctly for the base case of 1 product.
	 */
	public function test_populate_and_query() {
		WC_Helper_Reports::reset_stats_dbs();

		// Simple product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Coupons.
		$coupon_1_amount = 3; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );
		$coupon_1->set_amount( $coupon_1_amount );
		$coupon_1->save();

		$coupon_2_amount = 7;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		// Order without coupon.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		// Order with 1 coupon.
		$order_1c = WC_Helper_Order::create_order( 1, $product );
		$order_1c->set_status( 'completed' );
		$order_1c->apply_coupon( $coupon_1 );
		$order_1c->calculate_totals();
		$order_1c->save();

		// Order with 2 coupons.
		$order_2c = WC_Helper_Order::create_order( 1, $product );
		$order_2c->set_status( 'completed' );
		$order_2c->apply_coupon( $coupon_1 );
		$order_2c->apply_coupon( $coupon_2 );
		$order_2c->calculate_totals();
		$order_2c->save();

		WC_Helper_Queue::run_all_pending();

		$data_store = new CouponsDataStore();
		$start_time = gmdate( 'Y-m-d 00:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d 23:59:59', $order->get_date_created()->getOffsetTimestamp() );
		$args       = array(
			'after'  => $start_time,
			'before' => $end_time,
		);

		// Test retrieving the stats through the data store.
		$coupon_1_response = array(
			'coupon_id'     => $coupon_1->get_id(),
			'amount'        => floatval( $coupon_1_amount * 2 ),
			'orders_count'  => 2,
			'extended_info' => new ArrayObject(),
		);
		$coupon_2_response = array(
			'coupon_id'     => $coupon_2->get_id(),
			'amount'        => floatval( $coupon_2_amount ),
			'orders_count'  => 1,
			'extended_info' => new ArrayObject(),
		);

		// Order by coupon id DESC is the default.
		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => $coupon_2_response,
				1 => $coupon_1_response,
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the generic query class.
		$query = new GenericQuery( $args, 'coupons' );
		$this->assertEquals( $expected_data, $query->get_data() );

		// Test order by orders_count DESC.
		$args = array(
			'after'   => $start_time,
			'before'  => $end_time,
			'orderby' => 'orders_count',
			'order'   => 'desc',
		);

		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => $coupon_1_response,
				1 => $coupon_2_response,
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test order by amount ASC.
		$args = array(
			'after'   => $start_time,
			'before'  => $end_time,
			'orderby' => 'amount',
			'order'   => 'asc',
		);

		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => $coupon_1_response,
				1 => $coupon_2_response,
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test filtering by coupon id: coupon_1.
		$args = array(
			'after'   => $start_time,
			'before'  => $end_time,
			'coupons' => array( $coupon_1->get_id() ),
		);

		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 1,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => $coupon_1_response,
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test filtering by coupon id: coupon_1 & coupon_2.
		$args = array(
			'after'   => $start_time,
			'before'  => $end_time,
			'coupons' => array( $coupon_1->get_id(), $coupon_2->get_id() ),
		);

		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => $coupon_2_response,
				1 => $coupon_1_response,
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test extended info.
		$gmt_timezone    = new DateTimeZone( 'UTC' );
		$c1_date_created = $coupon_1->get_date_created();
		if ( null === $c1_date_created ) {
			$c1_date_created     = '';
			$c1_date_created_gmt = '';
		} else {
			$c1_date_created_gmt = new DateTime( $c1_date_created );
			$c1_date_created_gmt->setTimezone( $gmt_timezone );

			$c1_date_created     = $c1_date_created->format( TimeInterval::$iso_datetime_format );
			$c1_date_created_gmt = $c1_date_created_gmt->format( TimeInterval::$iso_datetime_format );
		}

		$c1_date_expires = $coupon_1->get_date_expires();
		if ( null === $c1_date_expires ) {
			$c1_date_expires     = '';
			$c1_date_expires_gmt = '';
		} else {
			$c1_date_expires_gmt = new DateTime( $c1_date_expires );
			$c1_date_expires_gmt->setTimezone( $gmt_timezone );

			$c1_date_expires     = $c1_date_expires->format( TimeInterval::$iso_datetime_format );
			$c1_date_expires_gmt = $c1_date_expires_gmt->format( TimeInterval::$iso_datetime_format );
		}

		$coupon_1_response = array(
			'coupon_id'     => $coupon_1->get_id(),
			'amount'        => $coupon_1_amount * 2,
			'orders_count'  => 2,
			'extended_info' => array(
				'code'             => $coupon_1->get_code(),
				'date_created'     => $c1_date_created,
				'date_created_gmt' => $c1_date_created_gmt,
				'date_expires'     => $c1_date_expires,
				'date_expires_gmt' => $c1_date_expires_gmt,
				'discount_type'    => $coupon_1->get_discount_type(),
			),
		);

		$c2_date_created = $coupon_2->get_date_created();
		if ( null === $c2_date_created ) {
			$c2_date_created     = '';
			$c2_date_created_gmt = '';
		} else {
			$c2_date_created_gmt = new DateTime( $c2_date_created );
			$c2_date_created_gmt->setTimezone( $gmt_timezone );

			$c2_date_created     = $c2_date_created->format( TimeInterval::$iso_datetime_format );
			$c2_date_created_gmt = $c2_date_created_gmt->format( TimeInterval::$iso_datetime_format );
		}

		$c2_date_expires = $coupon_2->get_date_expires();
		if ( null === $c2_date_expires ) {
			$c2_date_expires     = '';
			$c2_date_expires_gmt = '';
		} else {
			$c2_date_expires_gmt = new DateTime( $c2_date_expires );
			$c2_date_expires_gmt->setTimezone( $gmt_timezone );

			$c2_date_expires     = $c2_date_expires->format( TimeInterval::$iso_datetime_format );
			$c2_date_expires_gmt = $c2_date_expires_gmt->format( TimeInterval::$iso_datetime_format );
		}

		$coupon_2_response = array(
			'coupon_id'     => $coupon_2->get_id(),
			'amount'        => $coupon_2_amount,
			'orders_count'  => 1,
			'extended_info' => array(
				'code'             => $coupon_2->get_code(),
				'date_created'     => $c2_date_created,
				'date_created_gmt' => $c2_date_created_gmt,
				'date_expires'     => $c2_date_expires,
				'date_expires_gmt' => $c2_date_expires_gmt,
				'discount_type'    => $coupon_2->get_discount_type(),
			),
		);
		$args              = array(
			'after'         => $start_time,
			'before'        => $end_time,
			'extended_info' => true,
		);

		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				0 => $coupon_2_response,
				1 => $coupon_1_response,
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test the CSV export.
		$expected_csv_columns = array(
			'"Coupon code"',
			'Orders',
			'"Amount discounted"',
			'Created',
			'Expires',
			'Type',
		);

		// Expected CSV for Coupon 2.
		$coupon_2_csv = array(
			$coupon_2_response['extended_info']['code'],
			$coupon_2_response['orders_count'],
			$coupon_2_response['amount'],
			$coupon_2_response['extended_info']['date_created'],
			$coupon_2_response['extended_info']['date_expires'] ? $coupon_2_response['extended_info']['date_expires'] : 'N/A',
			$coupon_2_response['extended_info']['discount_type'],
		);

		// Expected CSV for Coupon 1.
		$coupon_1_csv = array(
			$coupon_1_response['extended_info']['code'],
			$coupon_1_response['orders_count'],
			$coupon_1_response['amount'],
			$coupon_1_response['extended_info']['date_created'],
			$coupon_1_response['extended_info']['date_expires'] ? $coupon_1_response['extended_info']['date_expires'] : 'N/A',
			$coupon_1_response['extended_info']['discount_type'],
		);

		// Build the expected CSV data, including Excel header (see: WC_CSV_Exporter::export).
		$expected_csv  = chr( 239 ) . chr( 187 ) . chr( 191 );
		$expected_csv .= implode( ',', $expected_csv_columns ) . PHP_EOL;
		$expected_csv .= implode( ',', $coupon_2_csv ) . PHP_EOL;
		$expected_csv .= implode( ',', $coupon_1_csv ) . PHP_EOL;

		// Ensure our exporter and report controller have been loaded.
		do_action( 'rest_api_init' );

		// Run the export and compare values.
		$export = new ReportCSVExporter( 'coupons', $args );
		$export->generate_file();
		if ( method_exists( $export, 'get_headers_row_file' ) ) {
			$actual_csv = $export->get_headers_row_file() . $export->get_file();
		} else {
			$actual_csv = $export->get_file();
		}

		$this->assertEquals( 100, $export->get_percent_complete() );
		$this->assertEquals( 2, $export->get_total_exported() );
		$this->assertEquals( $expected_csv, $actual_csv );
	}

	/**
	 * Test that calculations and querying works correctly when coupons are deleted.
	 */
	public function test_deleted_coupons() {
		WC_Helper_Reports::reset_stats_dbs();

		// Simple product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Coupons.
		$coupon_1_amount = 3; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );
		$coupon_1->set_amount( $coupon_1_amount );
		$coupon_1->save();

		// Coupon that will be deleted, but order item will have metadata that will preserve its ID.
		$coupon_2_amount = 1;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();
		$coupon_2_id = $coupon_2->get_id();

		// Order with coupons.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->apply_coupon( $coupon_1 );
		$order->apply_coupon( $coupon_2 );
		$order->calculate_totals();
		$order->save();

		// Add coupon_2 metadata to its order item.
		$coupon_items = $order->get_items( 'coupon' );

		// This would normally happen at checkout.
		foreach ( $coupon_items as $coupon_item ) {
			if ( 'coupon_2' !== $coupon_item->get_code() ) {
				continue;
			}

			$coupon_2_short_info = $coupon_2->get_short_info();
			$coupon_item->add_meta_data( 'coupon_info', $coupon_2_short_info );
			$coupon_item->save();
		}

		// Delete the coupons.
		$coupon_2->delete( true );

		WC_Helper_Queue::run_all_pending();

		$data_store = new CouponsDataStore();
		$start_time = gmdate( 'Y-m-d 00:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d 23:59:59', $order->get_date_created()->getOffsetTimestamp() );
		$args       = array(
			'after'  => $start_time,
			'before' => $end_time,
		);

		// Test retrieving the stats through the data store.
		$coupon_1_response = array(
			'coupon_id'     => $coupon_1->get_id(),
			'amount'        => floatval( $coupon_1_amount ),
			'orders_count'  => 1,
			'extended_info' => new ArrayObject(),
		);
		$coupon_2_response = array(
			'coupon_id'     => $coupon_2_id, // coupon_2 was deleted, but has metadata containing the ID.
			'amount'        => floatval( $coupon_2_amount ),
			'orders_count'  => 1,
			'extended_info' => new ArrayObject(),
		);

		// Order by coupon id DESC is the default.
		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				// Order query is sorted by coupon ID descending.
				0 => $coupon_2_response,
				1 => $coupon_1_response,
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test extended info.
		$gmt_timezone    = new DateTimeZone( 'UTC' );
		$c1_date_created = $coupon_1->get_date_created();
		if ( null === $c1_date_created ) {
			$c1_date_created     = '';
			$c1_date_created_gmt = '';
		} else {
			$c1_date_created_gmt = new DateTime( $c1_date_created );
			$c1_date_created_gmt->setTimezone( $gmt_timezone );

			$c1_date_created     = $c1_date_created->format( TimeInterval::$iso_datetime_format );
			$c1_date_created_gmt = $c1_date_created_gmt->format( TimeInterval::$iso_datetime_format );
		}

		$c1_date_expires = $coupon_1->get_date_expires();
		if ( null === $c1_date_expires ) {
			$c1_date_expires     = '';
			$c1_date_expires_gmt = '';
		} else {
			$c1_date_expires_gmt = new DateTime( $c1_date_expires );
			$c1_date_expires_gmt->setTimezone( $gmt_timezone );

			$c1_date_expires     = $c1_date_expires->format( TimeInterval::$iso_datetime_format );
			$c1_date_expires_gmt = $c1_date_expires_gmt->format( TimeInterval::$iso_datetime_format );
		}

		$coupon_1_response = array(
			'coupon_id'     => $coupon_1->get_id(),
			'amount'        => floatval( $coupon_1_amount ),
			'orders_count'  => 1,
			'extended_info' => array(
				'code'             => $coupon_1->get_code(),
				'date_created'     => $c1_date_created,
				'date_created_gmt' => $c1_date_created_gmt,
				'date_expires'     => $c1_date_expires,
				'date_expires_gmt' => $c1_date_expires_gmt,
				'discount_type'    => $coupon_1->get_discount_type(),
			),
		);

		$coupon_2_response = array(
			'coupon_id'     => $coupon_2_id, // coupon_2 was deleted, but has metadata containing the ID.
			'amount'        => floatval( $coupon_2_amount ),
			'orders_count'  => 1,
			'extended_info' => array(
				'code'             => '(Deleted)',
				'date_created'     => '',
				'date_created_gmt' => '',
				'date_expires'     => '',
				'date_expires_gmt' => '',
				'discount_type'    => 'N/A',
			),
		);

		$args          = array(
			'after'         => $start_time,
			'before'        => $end_time,
			'extended_info' => true,
		);
		$data          = $data_store->get_data( $args );
		$expected_data = (object) array(
			'total'   => 2,
			'pages'   => 1,
			'page_no' => 1,
			'data'    => array(
				// Order query is sorted by coupon ID descending.
				0 => $coupon_2_response,
				1 => $coupon_1_response,
			),
		);
		$this->assertEquals( $expected_data, $data );
	}
}
