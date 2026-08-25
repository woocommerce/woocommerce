<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Helper_Customer;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Product_Simple;
use WC_Unit_Test_Case;

/**
 * Tests for new vs returning customer counting in the Orders Stats DataStore.
 *
 * Migrated from the legacy WC_Admin_Tests_Reports_Orders_Stats class.
 */
class DataStoreReturningCustomersTest extends WC_Unit_Test_Case {

	/**
	 * Don't cache report data during these tests.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_analytics_report_should_use_cache', '__return_false' );
	}

	/**
	 * @testdox A guest customer with multiple orders is counted once, as new or returning depending on the time frame.
	 */
	public function test_guest_returning_customer(): void {
		$this->assert_returning_customer_counting( 0 );
	}

	/**
	 * @testdox A registered customer with multiple orders is counted once, as new or returning depending on the time frame.
	 */
	public function test_registered_returning_customer(): void {
		$customer = WC_Helper_Customer::create_customer( 'cust_1_new', 'pwd_1', 'new_customer@mail.com' );

		$this->assert_returning_customer_counting( $customer->get_id() );
	}

	/**
	 * Assert total_customers over a sequence of orders placed by one customer.
	 *
	 * @param int $customer_id The ordering customer, 0 for a guest.
	 */
	private function assert_returning_customer_counting( int $customer_id ): void {
		WC_Helper_Reports::reset_stats_dbs();

		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$data_store = new OrdersStatsDataStore();

		// All empty in the beginning.
		$query_args  = array(
			'interval' => 'hour',
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 0, $actual_data->totals->total_customers );

		// Create an order an hour before order 1, so that the customer will become a returning customer later.
		$order_1_time = time();
		$order_0_time = $order_1_time - HOUR_IN_SECONDS;

		$order_0 = $this->create_order_at( $customer_id, $product, $order_0_time );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$start_time  = gmdate( 'Y-m-d H:00:00', $order_0->get_date_created()->getOffsetTimestamp() );
		$end_time    = gmdate( 'Y-m-d H:59:59', $order_0->get_date_created()->getOffsetTimestamp() );
		$query_args  = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Place an order 'one hour later', 2 orders, but still just one customer.
		$order_1 = $this->create_order_at( $customer_id, $product, $order_1_time );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Time frame includes both orders -> customer is a new customer.
		$start_time = gmdate( 'Y-m-d H:00:00', $order_0->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order_1->get_date_created()->getOffsetTimestamp() );
		$query_args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);

		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Time frame includes only the second order -> customer is a returning customer.
		$start_time = gmdate( 'Y-m-d H:i:s', $order_0_time + 1 );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order_1->get_date_created()->getOffsetTimestamp() );
		$query_args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);

		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		$order_2 = $this->create_order_at( $customer_id, $product, $order_1_time );
		$order_2->set_date_modified( $order_1_time + 1 );
		$order_2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Time frame includes the second and third order -> there is one returning customer.
		$start_time  = gmdate( 'Y-m-d H:i:s', $order_0_time + 1 );
		$end_time    = gmdate( 'Y-m-d H:59:59', $order_2->get_date_created()->getOffsetTimestamp() );
		$query_args  = array(
			'interval'              => 'day',
			// To skip cache.
							'after' => $start_time,
			'before'                => $end_time,
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );

		$this->assertEquals(
			1,
			$actual_data->totals->total_customers,
			'The same customer placing multiple orders in the time frame should be counted once'
		);
	}

	/**
	 * Create a processing order for the customer at the given time.
	 *
	 * @param int               $customer_id The ordering customer, 0 for a guest.
	 * @param WC_Product_Simple $product     Product to order.
	 * @param int               $order_time  Order creation/payment timestamp.
	 * @return \WC_Order
	 */
	private function create_order_at( int $customer_id, WC_Product_Simple $product, int $order_time ) {
		$order = WC_Helper_Order::create_order( $customer_id, $product );
		$order->set_date_created( $order_time );
		$order->set_date_paid( $order_time );
		$order->set_status( OrderStatus::PROCESSING );
		$order->set_total( 100 );
		$order->save();

		return $order;
	}
}
