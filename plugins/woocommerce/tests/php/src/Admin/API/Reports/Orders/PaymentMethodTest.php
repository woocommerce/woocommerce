<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Helper_Order;
use WC_REST_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;

/**
 * Tests for reporting the Orders report on the payment gateway an order was paid with.
 */
class PaymentMethodTest extends WC_REST_Unit_Test_Case {

	/**
	 * Orders created by the test, keyed by the gateway they were paid with.
	 *
	 * @var array
	 */
	private $orders = array();

	/**
	 * Date the fixture orders are created and paid on, inside the reporting period queried below.
	 */
	private const ORDER_DATE = '2026-09-04 10:00:00';

	/**
	 * Set up one completed order per gateway.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( array( 'bacs', 'cheque', 'cod' ) as $gateway ) {
			$order = WC_Helper_Order::create_order();
			$order->set_payment_method( $gateway );
			$order->set_status( OrderStatus::COMPLETED );
			$order->set_date_created( self::ORDER_DATE );
			// The report runs on date_paid by default, and completing an order otherwise stamps it with the current time.
			$order->set_date_paid( self::ORDER_DATE );
			$order->save();

			OrdersStatsDataStore::sync_order( $order->get_id() );

			$this->orders[ $gateway ] = $order;
		}
	}

	/**
	 * @testdox Syncing an order stores the gateway it was paid with.
	 */
	public function test_sync_stores_the_payment_method(): void {
		$this->assertSame( 'bacs', $this->get_stored_payment_method( $this->orders['bacs']->get_id() ) );
	}

	/**
	 * @testdox A refund is stored with the gateway of the order it refunds, which carries none of its own.
	 */
	public function test_refund_takes_the_payment_method_of_the_refunded_order(): void {
		$refund = wc_create_refund(
			array(
				'order_id' => $this->orders['cheque']->get_id(),
				'amount'   => 10.00,
			)
		);

		$this->assertNotInstanceOf( WP_Error::class, $refund );

		OrdersStatsDataStore::sync_order( $refund->get_id() );

		$this->assertSame( 'cheque', $this->get_stored_payment_method( $refund->get_id() ) );
	}

	/**
	 * @testdox Syncing an order placed without a gateway, or a refund of it, stores NULL, the value the backfill leaves on such a row.
	 */
	public function test_sync_stores_null_for_an_order_without_a_gateway(): void {
		global $wpdb;

		$order = $this->orders['cod'];
		$order->set_payment_method( '' );
		$order->save();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
			)
		);

		foreach ( array( $order->get_id(), $refund->get_id() ) as $id ) {
			OrdersStatsDataStore::sync_order( $id );

			$this->assertSame(
				'1',
				$wpdb->get_var(
					$wpdb->prepare(
						"SELECT payment_method IS NULL FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d",
						$id
					)
				),
				'No gateway should be stored as NULL, not as an empty string.'
			);
		}
	}

	/**
	 * @testdox The Orders report endpoint returns the gateway of each row.
	 */
	public function test_orders_endpoint_returns_the_payment_method_of_each_row(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$request = new WP_REST_Request( 'GET', '/wc-analytics/reports/orders' );
		$request->set_query_params( $this->get_period_args() );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$payment_methods = wp_list_pluck( $response->get_data(), 'payment_method', 'order_id' );
		foreach ( $this->orders as $gateway => $order ) {
			$this->assertSame( $gateway, $payment_methods[ $order->get_id() ] ?? null );
		}
	}

	/**
	 * Read the payment method stored for an order in the stats table.
	 *
	 * @param int $order_id Order id.
	 * @return string|null
	 */
	private function get_stored_payment_method( int $order_id ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT payment_method FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d",
				$order_id
			)
		);
	}

	/**
	 * The reporting period and cache settings shared by every query in this test.
	 *
	 * @return array
	 */
	private function get_period_args(): array {
		return array(
			'after'               => '2026-09-01T00:00:00',
			'before'              => '2026-09-30T23:59:59',
			'per_page'            => 100,
			'force_cache_refresh' => true,
		);
	}
}
