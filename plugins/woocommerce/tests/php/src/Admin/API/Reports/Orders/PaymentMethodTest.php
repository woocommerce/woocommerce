<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Query as OrdersQuery;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query as OrdersStatsQuery;
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
	 * @testdox The include and exclude rules narrow together under match=any, so the table and the totals agree.
	 */
	public function test_payment_method_rules_narrow_together_under_match_any(): void {
		$args = array(
			'payment_method_is'     => array( 'bacs' ),
			'payment_method_is_not' => array( 'cheque' ),
			'match'                 => 'any',
		);

		$this->assertSame( array( $this->orders['bacs']->get_id() ), $this->get_report_order_ids( $args ) );
		$this->assertSame( 1, $this->get_stats_orders_count( $args ) );
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
	 * @testdox The Orders report endpoint applies both payment gateway rules and returns the gateway of each row.
	 */
	public function test_orders_endpoint_applies_the_payment_method_rules(): void {
		$rows = $this->get_rest_report( '/wc-analytics/reports/orders' );

		$this->assertSame( array( $this->orders['bacs']->get_id() ), wp_list_pluck( $rows, 'order_id' ) );
		$this->assertSame( 'bacs', $rows[0]['payment_method'] );
	}

	/**
	 * @testdox The Orders stats endpoint applies both payment gateway rules to its totals.
	 */
	public function test_orders_stats_endpoint_applies_the_payment_method_rules(): void {
		$report = $this->get_rest_report( '/wc-analytics/reports/orders/stats' );

		$this->assertSame( 1, $report['totals']['orders_count'] );
	}

	/**
	 * Request a report endpoint as an administrator, with rules that leave only the bacs order.
	 *
	 * @param string $endpoint REST route of the report.
	 * @return array
	 */
	private function get_rest_report( string $endpoint ): array {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$request = new WP_REST_Request( 'GET', $endpoint );
		$request->set_query_params(
			array_merge(
				$this->get_period_args(),
				// Comma separated, as the client sends them, so a rule the endpoint does not parse matches no gateway.
				// The cheque order is in neither list, so the result changes when either rule is not applied.
				array(
					'payment_method_is'     => 'bacs,cod',
					'payment_method_is_not' => 'cod,paypal',
				)
			)
		);
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		return $response->get_data();
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
