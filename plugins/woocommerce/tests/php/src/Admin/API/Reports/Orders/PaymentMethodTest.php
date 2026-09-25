<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Query as OrdersQuery;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query as OrdersStatsQuery;
use WC_Helper_Order;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for reporting the Orders report on the payment gateway an order was paid with.
 */
class PaymentMethodTest extends WC_Unit_Test_Case {

	/**
	 * Orders created by the test, keyed by the gateway they were paid with.
	 *
	 * @var array
	 */
	private $orders = array();

	/**
	 * Set up one completed order per gateway.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( array( 'bacs', 'cheque', 'cod' ) as $gateway ) {
			$order = WC_Helper_Order::create_order();
			$order->set_payment_method( $gateway );
			$order->set_date_created( '2026-09-04 10:00:00' );
			$order->save();
			$order->update_status( 'completed' );

			OrdersStatsDataStore::sync_order( $order->get_id() );

			$this->orders[ $gateway ] = $order;
		}
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->orders as $order ) {
			WC_Helper_Order::delete_order( $order->get_id() );
		}
		$this->orders = array();

		parent::tearDown();
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
	 * @testdox payment_method_is limits the Orders report to the named gateways.
	 */
	public function test_payment_method_is_limits_the_report(): void {
		$order_ids = $this->get_report_order_ids( array( 'payment_method_is' => array( 'bacs', 'cod' ) ) );

		$this->assertEqualsCanonicalizing(
			array( $this->orders['bacs']->get_id(), $this->orders['cod']->get_id() ),
			$order_ids
		);
	}

	/**
	 * @testdox payment_method_is_not drops the named gateways from the Orders report.
	 */
	public function test_payment_method_is_not_drops_the_named_gateways(): void {
		$order_ids = $this->get_report_order_ids( array( 'payment_method_is_not' => array( 'bacs' ) ) );

		$this->assertNotContains( $this->orders['bacs']->get_id(), $order_ids );
		$this->assertContains( $this->orders['cheque']->get_id(), $order_ids );
		$this->assertContains( $this->orders['cod']->get_id(), $order_ids );
	}

	/**
	 * @testdox payment_method_is_not keeps orders that carry no gateway, which are not the excluded one either.
	 */
	public function test_payment_method_is_not_keeps_orders_without_a_gateway(): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'wc_order_stats',
			array( 'payment_method' => null ),
			array( 'order_id' => $this->orders['cod']->get_id() ),
			array( '%s' ),
			array( '%d' )
		);

		$order_ids = $this->get_report_order_ids( array( 'payment_method_is_not' => array( 'bacs' ) ) );

		$this->assertContains( $this->orders['cod']->get_id(), $order_ids );
	}

	/**
	 * @testdox The stats report totals honour the payment gateway filter, so they match the table.
	 */
	public function test_stats_totals_honour_the_payment_method_filter(): void {
		$unfiltered = $this->get_stats_orders_count( array() );
		$filtered   = $this->get_stats_orders_count( array( 'payment_method_is' => array( 'bacs' ) ) );

		$this->assertSame( 3, $unfiltered );
		$this->assertSame( 1, $filtered );
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
	 * Run the Orders report and return the order ids it holds.
	 *
	 * @param array $args Query arguments on top of the reporting period.
	 * @return array
	 */
	private function get_report_order_ids( array $args ): array {
		$query = new OrdersQuery( array_merge( $this->get_period_args(), $args ) );

		return wp_list_pluck( $query->get_data()->data, 'order_id' );
	}

	/**
	 * Run the Orders stats report and return its order count.
	 *
	 * @param array $args Query arguments on top of the reporting period.
	 * @return int
	 */
	private function get_stats_orders_count( array $args ): int {
		$query = new OrdersStatsQuery( array_merge( $this->get_period_args(), $args ) );

		return (int) $query->get_data()->totals->orders_count;
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
