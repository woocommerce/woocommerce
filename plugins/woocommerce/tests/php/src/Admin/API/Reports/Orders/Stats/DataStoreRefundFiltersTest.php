<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Product_Simple;

/**
 * Tests for the refunds filter of the Orders Stats DataStore.
 */
class DataStoreRefundFiltersTest extends OrdersStatsTestCase {

	/**
	 * Remove the full-refund data format option after each test, including failed ones.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		parent::tearDown();
	}

	/**
	 * @testdox The refunds filter distinguishes all, none, partial and full refunds.
	 */
	public function test_populate_and_query_refunds(): void {
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
				'status' => OrderStatus::COMPLETED,
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

		// Add a partial refund on the first item of the last order.
		$item_id = current( $order->get_items() )->get_id();
		wc_create_refund(
			array(
				'amount'     => 10,
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$item_id => array(
						'qty'          => 0,
						'refund_total' => 10,
					),
				),
			)
		);

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$data_store = new OrdersStatsDataStore();

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', $order->get_date_created()->getOffsetTimestamp() );

		$refund_filter_expectations = array(
			'all'     => array(
				'num_items_sold'  => -4,
				'total_sales'     => -60,
				'refunds'         => 60,
				'net_revenue'     => -60,
				'total_customers' => 1,
			),
			'none'    => array(
				'orders_count'        => 3,
				'num_items_sold'      => 12,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 75,
				'total_sales'         => 225,
				'gross_sales'         => 225,
				'net_revenue'         => 225,
				'total_customers'     => 1,
			),
			'partial' => array(
				'total_sales'     => -10,
				'refunds'         => 10,
				'net_revenue'     => -10,
				'total_customers' => 1,
			),
			'full'    => array(
				'num_items_sold'  => -4,
				'total_sales'     => -50,
				'refunds'         => 50,
				'net_revenue'     => -50,
				'total_customers' => 1,
			),
		);

		foreach ( $refund_filter_expectations as $refund_filter => $expected_values ) {
			$args = array(
				'interval' => 'hour',
				'after'    => $start_time,
				'before'   => $end_time,
				'refunds'  => $refund_filter,
			);

			$expected_stats = $this->expected_stats_single_interval(
				$this->expected_totals( array_merge( $expected_values, array( 'products' => 1 ) ) ),
				$start_time,
				$end_time
			);

			$this->assertEquals(
				$expected_stats,
				json_decode( wp_json_encode( $data_store->get_data( $args ) ), true ),
				"Unexpected stats for refunds filter '{$refund_filter}'"
			);
		}
	}

	/**
	 * @testdox Full refunds via status change are reported with the old full refund data format.
	 */
	public function test_populate_and_query_refunds_with_old_full_refund_data(): void {
		WC_Helper_Reports::reset_stats_dbs();
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );

		$order = $this->create_orders_with_full_refund_via_status_change();

		$data_store = new OrdersStatsDataStore();

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', strtotime( '+1 day', $order->get_date_created()->getOffsetTimestamp() ) );

		$args            = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_totals = $this->expected_totals(
			array(
				'orders_count'        => 2,
				'num_items_sold'      => 8,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 55,
				'total_sales'         => 50,
				'gross_sales'         => 110,
				'refunds'             => 100,
				'taxes'               => 20,
				'shipping'            => 20,
				'net_revenue'         => 10,
				'total_customers'     => 1,
				'products'            => 1,
			)
		);

		$this->assertEquals( $expected_totals, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true )['totals'] );

		// Query full refunds.
		$args = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
			'refunds'  => 'full',
		);
		// num_items_sold, refunds and products expectations reflect the bug fixed by PR #58744.
		$expected_totals = $this->expected_totals(
			array(
				'total_sales'     => -100,
				'refunds'         => 100,
				'net_revenue'     => -100,
				'total_customers' => 1,
				'products'        => 0,
			)
		);

		$this->assertEquals( $expected_totals, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true )['totals'] );
	}

	/**
	 * @testdox Full refunds via status change are reported with the new full refund data format.
	 */
	public function test_populate_and_query_refunds_with_new_full_refund_data(): void {
		WC_Helper_Reports::reset_stats_dbs();

		$order = $this->create_orders_with_full_refund_via_status_change();

		$data_store = new OrdersStatsDataStore();

		$start_time = gmdate( 'Y-m-d H:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = gmdate( 'Y-m-d H:59:59', strtotime( '+1 day', $order->get_date_created()->getOffsetTimestamp() ) );

		$args            = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
		);
		$expected_totals = $this->expected_totals(
			array(
				'orders_count'        => 2,
				'num_items_sold'      => 4,
				'avg_items_per_order' => 4,
				'avg_order_value'     => 55,
				'total_sales'         => 50,
				'gross_sales'         => 110,
				'refunds'             => 100,
				'taxes'               => 10,
				'shipping'            => 10,
				'net_revenue'         => 30,
				'total_customers'     => 1,
				'products'            => 1,
			)
		);

		$this->assertEquals( $expected_totals, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true )['totals'] );

		// Query full refunds.
		$args            = array(
			'interval' => 'hour',
			'after'    => $start_time,
			'before'   => $end_time,
			'refunds'  => 'full',
		);
		$expected_totals = $this->expected_totals(
			array(
				'num_items_sold'  => -4,
				'total_sales'     => -100,
				'refunds'         => 100,
				'taxes'           => -10,
				'shipping'        => -10,
				'net_revenue'     => -80,
				'total_customers' => 1,
				'products'        => 1,
			)
		);

		$this->assertEquals( $expected_totals, json_decode( wp_json_encode( $data_store->get_data( $args ) ), true )['totals'] );
	}

	/**
	 * Create two completed orders and fully refund the second by changing its status.
	 *
	 * @return \WC_Order The refunded order.
	 */
	private function create_orders_with_full_refund_via_status_change() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order_types = array(
			array(
				'status' => OrderStatus::COMPLETED,
				'total'  => 50,
			),
			array(
				'status' => OrderStatus::COMPLETED,
				'total'  => 100,
			),
		);

		$time = time();

		foreach ( $order_types as $order_type ) {
			$order = WC_Helper_Order::create_order( 1, $product );
			$order->set_status( $order_type['status'] );
			$order->set_total( $order_type['total'] );
			$order->set_date_created( $time );
			$order->set_date_paid( $time );
			$order->set_shipping_total( 10 );
			$order->set_cart_tax( 10 );
			$order->save();
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Refund the order completely by changing the order status to refunded.
		$order->set_status( OrderStatus::REFUNDED );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		return $order;
	}
}
