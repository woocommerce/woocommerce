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

/**
 * Tests for new vs returning customer counting in the Orders Stats DataStore.
 */
class DataStoreReturningCustomersTest extends OrdersStatsTestCase {

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

		// The first order lands an hour before the later ones, so its customer becomes a returning customer.
		$later_orders_time = time();
		$first_order_time  = $later_orders_time - HOUR_IN_SECONDS;

		$first_order = $this->create_order_at( $customer_id, $product, $first_order_time );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$start_time  = gmdate( 'Y-m-d H:00:00', $first_order->get_date_created()->getOffsetTimestamp() );
		$end_time    = gmdate( 'Y-m-d H:59:59', $first_order->get_date_created()->getOffsetTimestamp() );
		$query_args  = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Place a second order an hour later: 2 orders, but still just one customer.
		$second_order = $this->create_order_at( $customer_id, $product, $later_orders_time );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Time frame includes both orders -> customer is a new customer.
		$start_time = gmdate( 'Y-m-d H:00:00', $first_order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $second_order->get_date_created()->getOffsetTimestamp() );
		$query_args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);

		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		// Time frame includes only the second order -> customer is a returning customer.
		$start_time = gmdate( 'Y-m-d H:i:s', $first_order_time + 1 );
		$end_time   = gmdate( 'Y-m-d H:59:59', $second_order->get_date_created()->getOffsetTimestamp() );
		$query_args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);

		$actual_data = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ) );
		$this->assertEquals( 1, $actual_data->totals->total_customers );

		$third_order = $this->create_order_at( $customer_id, $product, $later_orders_time );
		$third_order->set_date_modified( $later_orders_time + 1 );
		$third_order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Time frame includes the second and third order -> there is one returning customer.
		$start_time  = gmdate( 'Y-m-d H:i:s', $first_order_time + 1 );
		$end_time    = gmdate( 'Y-m-d H:59:59', $third_order->get_date_created()->getOffsetTimestamp() );
		$query_args  = array(
			'interval' => 'day',
			'after'    => $start_time,
			'before'   => $end_time,
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
