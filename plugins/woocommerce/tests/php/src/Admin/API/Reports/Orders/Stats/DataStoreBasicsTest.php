<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query as OrdersStatsQuery;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Enums\OrderStatus;
use DateTime;
use WC_Coupon;
use WC_Helper_Coupon;
use WC_Helper_Customer;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Product_Simple;
use WC_Tax;

/**
 * Tests for the basic querying behavior of the Orders Stats DataStore.
 */
class DataStoreBasicsTest extends OrdersStatsTestCase {

	/**
	 * @testdox Stats for a single order are calculated correctly, both through the data store and the query class.
	 */
	public function test_populate_and_query(): void {
		WC_Helper_Reports::reset_stats_dbs();

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$coupon = WC_Helper_Coupon::create_coupon( 'test-coupon' );
		$coupon->set_amount( 20 );
		$coupon->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->apply_coupon( $coupon );
		$order->set_cart_tax( 5 );
		$order->set_shipping_tax( 2 );
		// Total: $25x4 products + $10 shipping - $20 discount + $7 tax.
		$order->set_total( 97 );
		$order->save();

		wc_create_refund(
			array(
				'amount'   => 12,
				'order_id' => $order->get_id(),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_stats = $this->expected_stats_single_interval(
			$this->expected_totals(
				array(
					'orders_count'        => 1,
					'num_items_sold'      => 4,
					'avg_items_per_order' => 4,
					'avg_order_value'     => 80,
					'total_sales'         => 85,
					'gross_sales'         => 100,
					'coupons'             => 20,
					'coupons_count'       => 1,
					'refunds'             => 12,
					'taxes'               => 7,
					'shipping'            => 10,
					'net_revenue'         => 68,
					'total_customers'     => 1,
					'products'            => 1,
				)
			),
			$start_time,
			$end_time
		);

		$this->assert_report_data( $expected_stats, $args );

		// The query class returns a reduced set of totals keys.
		$query          = new OrdersStatsQuery( $args );
		$expected_stats = $this->expected_stats_single_interval(
			array(
				'net_revenue'         => 68,
				'avg_order_value'     => 80,
				'orders_count'        => 1,
				'avg_items_per_order' => 4,
				'num_items_sold'      => 4,
				'coupons'             => 20,
				'coupons_count'       => 1,
				'total_customers'     => 1,
				'products'            => 1,
				'segments'            => array(),
			),
			$start_time,
			$end_time
		);
		$this->assertEquals( $expected_stats, json_decode( wp_json_encode( $query->get_data() ), true ) );
	}

	/**
	 * @testdox Querying statuses includes the default or query-specific statuses.
	 */
	public function test_populate_and_query_statuses(): void {
		WC_Helper_Reports::reset_stats_dbs();

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order_types = array(
			array(
				'status' => OrderStatus::REFUNDED,
				'total'  => 50,
			),
			array(
				'status' => OrderStatus::COMPLETED,
				'total'  => 100,
			),
			array(
				'status' => OrderStatus::FAILED,
				'total'  => 75,
			),
		);

		$time = time();

		foreach ( $order_types as $order_type ) {
			$order = WC_Helper_Order::create_order( 1, $product );
			$order->set_status( $order_type['status'] );
			$order->set_total( $order_type['total'] );
			$order->set_date_created( $time );
			$order->set_date_paid( $time );
			$order->set_shipping_total( 0 );
			$order->set_cart_tax( 0 );
			$order->save();
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		// Query default statuses that should not include excluded or refunded order statuses.
		$args           = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_stats = $this->expected_stats_single_interval(
			$this->expected_totals(
				array(
					'orders_count'        => 2,
					// 4 items sold in the completed order, none in failed and refunded.
					'num_items_sold'      => 4,
					'avg_items_per_order' => 4,
					'avg_order_value'     => 75,
					'total_sales'         => 100,
					'gross_sales'         => 150,
					'refunds'             => 50,
					'net_revenue'         => 100,
					'total_customers'     => 1,
					'products'            => 1,
				)
			),
			$start_time,
			$end_time
		);

		$this->assert_report_data( $expected_stats, $args );

		// Query an excluded status which should still return orders with the queried status.
		$args           = array(
			'interval'  => 'hour',
			'after'     => $start_time,
			'before'    => $end_time,
			'status_is' => array( OrderStatus::FAILED ),
		);
		$expected_stats = $this->expected_stats_single_interval(
			$this->expected_totals(
				array(
					'orders_count'        => 1,
					'num_items_sold'      => 4,
					'avg_items_per_order' => 4,
					'avg_order_value'     => 75,
					'total_sales'         => 75,
					'gross_sales'         => 75,
					'net_revenue'         => 75,
					'total_customers'     => 1,
					'products'            => 1,
				)
			),
			$start_time,
			$end_time
		);

		$this->assert_report_data( $expected_stats, $args );
	}

	/**
	 * @testdox Multiple coupons on orders are all included in the totals.
	 */
	public function test_populate_and_query_multiple_coupons(): void {
		WC_Helper_Reports::reset_stats_dbs();

		$customer      = WC_Helper_Customer::create_customer( 'cust_1', 'pwd_1', 'user_1@mail.com' );
		$product_price = 23.45;
		$product       = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( $product_price );
		$product->save();

		// Create 3 coupons valued 1, 2, 3.
		$coupons = array();
		foreach ( range( 1, 3 ) as $amount ) {
			$coupon = WC_Helper_Coupon::create_coupon( 'coupon_' . $amount );
			$coupon->set_amount( $amount );
			$coupon->save();
			$coupons[ $amount ] = $coupon;
		}

		// Create 3 orders with 3, 2, 1 coupons applied respectively.
		$minute_ago        = time() - MINUTE_IN_SECONDS;
		$report_start_time = $minute_ago - ( $minute_ago % HOUR_IN_SECONDS );
		$order_time        = $report_start_time + 1;
		$applied_amount    = 0;
		$orders_total      = 0;
		$orders            = array();

		foreach ( range( 1, 3 ) as $order_number ) {
			$order      = WC_Helper_Order::create_order( $customer->get_id(), $product );
			$order_date = $order_time++;
			$order->set_date_created( $order_date );
			$order->set_date_paid( $order_date );
			$order->set_status( OrderStatus::COMPLETED );

			foreach ( $coupons as $amount => $coupon ) {
				if ( $amount >= $order_number ) {
					$order->apply_coupon( $coupon );
					$applied_amount += $amount;
				}
			}

			$order->calculate_totals();
			$orders_total += $order->get_total();
			$order->save();

			$orders[] = $order;
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$current_hour_start = new DateTime();
		$current_hour_start->setTimestamp( $report_start_time );

		$current_hour_end = new DateTime();
		$current_hour_end->setTimestamp( $report_start_time + HOUR_IN_SECONDS - 1 );

		$query_args = array(
			'after'    => $current_hour_start->format( TimeInterval::$sql_datetime_format ),
			'before'   => $current_hour_end->format( TimeInterval::$sql_datetime_format ),
			'interval' => 'hour',
		);

		$orders_count   = count( $orders );
		$num_items_sold = $orders_count * self::QTY_PER_PRODUCT;
		$shipping       = $orders_count * self::SHIPPING_AMOUNT;
		$net_revenue    = $orders_total - $shipping;

		$expected_stats = $this->expected_stats_single_interval(
			$this->expected_totals(
				array(
					'orders_count'        => $orders_count,
					'num_items_sold'      => $num_items_sold,
					'total_sales'         => $orders_total,
					'gross_sales'         => $product_price * $num_items_sold,
					'coupons'             => $applied_amount,
					'coupons_count'       => count( $coupons ),
					'shipping'            => $shipping,
					'net_revenue'         => $net_revenue,
					'avg_items_per_order' => $num_items_sold / $orders_count,
					'avg_order_value'     => $net_revenue / $orders_count,
					'total_customers'     => 1,
					'products'            => 1,
				)
			),
			$current_hour_start->format( 'Y-m-d H:i:s' ),
			$current_hour_end->format( 'Y-m-d H:i:s' )
		);

		$this->assert_report_data( $expected_stats, $query_args );
	}

	/**
	 * @testdox Orders by different customers within the same hour count each customer.
	 */
	public function test_populate_and_query_multiple_customers_same_hour(): void {
		WC_Helper_Reports::reset_stats_dbs();

		$customer_1 = WC_Helper_Customer::create_customer( 'cust_multi_1', 'pwd_1', 'multi_user_1@mail.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'cust_multi_2', 'pwd_2', 'multi_user_2@mail.com' );

		// Two completed orders by different customers within the same hourly interval.
		// Set a time near the top of the hour so both orders stay within it.
		$order_datetime = new DateTime();
		$order_datetime->setTime( (int) $order_datetime->format( 'H' ), 10, 0 );
		$order_time = (int) $order_datetime->format( 'U' );

		$customer_1_order = WC_Helper_Order::create_order( $customer_1->get_id() );
		$customer_1_order->set_date_created( $order_time );
		$customer_1_order->set_status( OrderStatus::COMPLETED );
		$customer_1_order->save();

		// Offset by 1 second to keep both orders in the same hour but distinct.
		$customer_2_order = WC_Helper_Order::create_order( $customer_2->get_id() );
		$customer_2_order->set_date_created( $order_time + 1 );
		$customer_2_order->set_status( OrderStatus::COMPLETED );
		$customer_2_order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();
		$start_time = gmdate( 'Y-m-d H:00:00', $customer_1_order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $customer_1_order->get_date_created()->getOffsetTimestamp() );

		$data = json_decode(
			wp_json_encode(
				$data_store->get_data(
					array(
						'interval' => 'hour',
						'after'    => $start_time,
						'before'   => $end_time,
					)
				)
			),
			true
		);

		$this->assertEquals( 2, $data['totals']['orders_count'] );
		$this->assertEquals( 2, $data['totals']['total_customers'] );
		$this->assertCount( 1, $data['intervals'] );
		$this->assertEquals( 2, $data['intervals'][0]['subtotals']['orders_count'] );
		$this->assertEquals( 2, $data['intervals'][0]['subtotals']['total_customers'] );
	}

	/**
	 * @testdox Lookup tables are cleaned after deleting an order.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::delete_order
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::sync_on_order_delete
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::sync_on_order_delete
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore::sync_on_order_delete
	 */
	public function test_order_deletion(): void {
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		$tables = array(
			'wc_order_coupon_lookup',
			'wc_order_product_lookup',
			'wc_order_stats',
			'wc_order_tax_lookup',
		);

		// Enable taxes.
		$default_calc_taxes       = get_option( 'woocommerce_calc_taxes', 'no' );
		$default_customer_address = get_option( 'woocommerce_default_customer_address', 'geolocation' );
		$default_tax_based_on     = get_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'base' );

		$tax_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$coupon = new WC_Coupon();
		$coupon->set_code( '20fixed' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 20 );
		$coupon->save();

		$order = WC_Helper_Order::create_order();
		$order->add_product( $product, 1 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_shipping_total( 10 );
		$order->apply_coupon( $coupon );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Check if lookup tables are populated.
		foreach ( $tables as $table ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}{$table} WHERE order_id = %d", // phpcs:ignore
					$order->get_id()
				)
			);

			$this->assertTrue( is_array( $results ) && (bool) count( $results ) );
		}

		// Delete order and clean all other objects too.
		$order->delete( true );
		$product->delete( true );
		$coupon->delete( true );
		WC_Tax::_delete_tax_rate( $tax_id );

		// Check if results are empty now.
		foreach ( $tables as $table ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}{$table} WHERE order_id = %d",  // phpcs:ignore
					$order->get_id()
				)
			);

			$this->assertEmpty( $results );
		}

		// Reset taxes settings.
		update_option( 'woocommerce_calc_taxes', $default_calc_taxes );
		update_option( 'woocommerce_default_customer_address', $default_customer_address );
		update_option( 'woocommerce_tax_based_on', $default_tax_based_on );
	}
}
