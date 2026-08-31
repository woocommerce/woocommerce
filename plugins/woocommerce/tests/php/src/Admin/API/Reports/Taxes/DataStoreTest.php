<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Taxes;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as OrdersDataStore;
use Automattic\WooCommerce\Admin\ReportsSync;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore as StatsDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Order_Item_Tax;
use WC_Product_Simple;
use WC_Unit_Test_Case;

/**
 * Tests that the Taxes reports honour the configured woocommerce_date_type,
 * reconciling with the Orders and Revenue reports.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/63699
 */
class DataStoreTest extends WC_Unit_Test_Case {

	/**
	 * Original woocommerce_calc_taxes option value.
	 *
	 * @var string|false
	 */
	private $original_calc_taxes;

	/**
	 * Original woocommerce_date_type option value.
	 *
	 * @var string|false
	 */
	private $original_date_type;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_calc_taxes = get_option( 'woocommerce_calc_taxes' );
		$this->original_date_type  = get_option( 'woocommerce_date_type' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_calc_taxes', $this->original_calc_taxes );
		if ( false === $this->original_date_type ) {
			delete_option( 'woocommerce_date_type' );
		} else {
			update_option( 'woocommerce_date_type', $this->original_date_type );
		}
		parent::tearDown();
	}

	/**
	 * Insert a DE VAT tax rate and return its id.
	 *
	 * @return int
	 */
	private function insert_tax_rate(): int {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_id'       => 1,
				'tax_rate'          => '19',
				'tax_rate_country'  => 'DE',
				'tax_rate_state'    => '',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => 1,
				'tax_rate_order'    => 1,
			)
		);
		return 1;
	}

	/**
	 * Create a taxed, completed order and force its stats/lookup dates so that the
	 * order's created, paid and completed dates can each land in a chosen month.
	 *
	 * @param int         $rate_id       Tax rate id to attach.
	 * @param string      $created_gmt   Order creation datetime (GMT).
	 * @param string|null $paid_gmt      Order payment datetime (GMT), or null for an unpaid (e.g. manual) order.
	 * @param string|null $completed_gmt Order completion datetime (GMT). Defaults to $paid_gmt.
	 * @return int Order id.
	 */
	private function seed_order( int $rate_id, string $created_gmt, ?string $paid_gmt, ?string $completed_gmt = null ): int {
		global $wpdb;

		$product = new WC_Product_Simple();
		$product->set_name( 'Repro Product' );
		$product->set_regular_price( '100' );
		$product->save();

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( $rate_id );
		$tax_item->set_tax_total( 19 );
		$tax_item->set_shipping_tax_total( 0 );

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->add_item( $tax_item );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 119 );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$order_id = $order->get_id();

		// Ensure a tax lookup row exists for the rate, dated by order creation.
		$wpdb->replace(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => $order_id,
				'tax_rate_id'  => $rate_id,
				'date_created' => $created_gmt,
				'shipping_tax' => 0,
				'order_tax'    => 19,
				'total_tax'    => 19,
			)
		);

		// Force the created/paid/completed dates on the stats row.
		$wpdb->update(
			$wpdb->prefix . 'wc_order_stats',
			array(
				'date_created'     => $created_gmt,
				'date_created_gmt' => $created_gmt,
				'date_paid'        => $paid_gmt,
				'date_completed'   => null === $completed_gmt ? $paid_gmt : $completed_gmt,
			),
			array( 'order_id' => $order_id )
		);

		ReportsCache::invalidate();

		return $order_id;
	}

	/**
	 * Build query args for a whole-month Taxes report request.
	 *
	 * @param string $after   Period start (GMT).
	 * @param string $before  Period end (GMT).
	 * @param int    $rate_id Tax rate id to scope to.
	 * @return array
	 */
	private function taxes_query( string $after, string $before, int $rate_id ): array {
		return array(
			'after'    => $after,
			'before'   => $before,
			'taxes'    => array( $rate_id ),
			'per_page' => 100,
			'page'     => 1,
		);
	}

	/**
	 * @testdox Taxes table report counts an order in its paid month, not its created month, when reporting by date_paid.
	 */
	public function test_taxes_report_buckets_by_date_paid_when_configured(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		$this->seed_order( $rate_id, '2023-01-15 10:00:00', '2023-02-15 10:00:00' );

		$sut = new DataStore();

		$february = $sut->get_data( $this->taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59', $rate_id ) );
		$january  = $sut->get_data( $this->taxes_query( '2023-01-01 00:00:00', '2023-01-31 23:59:59', $rate_id ) );

		$february_count = isset( $february->data[0]['orders_count'] ) ? $february->data[0]['orders_count'] : 0;

		$this->assertSame( 1, $february_count, 'Order paid in February should be counted in the February Taxes report.' );
		$this->assertCount( 0, $january->data, 'Order paid in February should not appear in the January Taxes report.' );
	}

	/**
	 * @testdox Taxes stats report counts an order in its paid month, not its created month, when reporting by date_paid.
	 */
	public function test_taxes_stats_report_buckets_by_date_paid_when_configured(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		$this->seed_order( $rate_id, '2023-01-15 10:00:00', '2023-02-15 10:00:00' );

		$sut = new StatsDataStore();

		$february = $sut->get_data( $this->taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59', $rate_id ) + array( 'interval' => 'day' ) );
		$january  = $sut->get_data( $this->taxes_query( '2023-01-01 00:00:00', '2023-01-31 23:59:59', $rate_id ) + array( 'interval' => 'day' ) );

		$this->assertSame( 1, $february->totals->orders_count, 'Order paid in February should be counted in the February Taxes stats totals.' );
		$this->assertSame( 0, $january->totals->orders_count, 'Order paid in February should not appear in the January Taxes stats totals.' );

		// The order must land in its paid-date interval (Feb 15), not its created-date interval.
		$february_intervals_with_order = array_values(
			array_filter(
				$february->intervals,
				function ( $interval ) {
					return $interval['subtotals']->orders_count > 0;
				}
			)
		);
		$this->assertCount( 1, $february_intervals_with_order, 'Exactly one February interval should contain the order.' );
		$this->assertSame( '2023-02-15', $february_intervals_with_order[0]['interval'], 'The order should be bucketed into its paid date (Feb 15), not its created date.' );
		$this->assertSame( 1, $february_intervals_with_order[0]['subtotals']->orders_count );

		// No January interval should contain the order, since it is paid in February.
		foreach ( $january->intervals as $interval ) {
			$this->assertSame( 0, $interval['subtotals']->orders_count, 'No January interval should contain an order paid in February.' );
		}
	}

	/**
	 * @testdox Taxes table report counts an order in its completed month when reporting by date_completed.
	 */
	public function test_taxes_report_buckets_by_date_completed_when_configured(): void {
		update_option( 'woocommerce_date_type', 'date_completed' );
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		// Created and paid in January, but completed in February.
		$this->seed_order( $rate_id, '2023-01-15 10:00:00', '2023-01-15 10:00:00', '2023-02-15 10:00:00' );

		$sut = new DataStore();

		$february = $sut->get_data( $this->taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59', $rate_id ) );
		$january  = $sut->get_data( $this->taxes_query( '2023-01-01 00:00:00', '2023-01-31 23:59:59', $rate_id ) );

		$february_count = isset( $february->data[0]['orders_count'] ) ? $february->data[0]['orders_count'] : 0;

		$this->assertSame( 1, $february_count, 'Order completed in February should be counted in the February Taxes report.' );
		$this->assertCount( 0, $january->data, 'Order completed in February should not appear in the January Taxes report when reporting by date_completed.' );
	}

	/**
	 * @testdox Taxes table report excludes an order with no paid date when reporting by date_paid.
	 */
	public function test_taxes_report_excludes_orders_with_no_paid_date(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		// Manually-created order: created in February but never paid (date_paid NULL).
		$this->seed_order( $rate_id, '2023-02-15 10:00:00', null );

		$sut = new DataStore();

		$february = $sut->get_data( $this->taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59', $rate_id ) );

		$this->assertCount( 0, $february->data, 'An order with no paid date should be excluded from the date_paid based Taxes report, matching the Orders report.' );
	}

	/**
	 * @testdox Taxes stats report excludes an order with no paid date when reporting by date_paid.
	 */
	public function test_taxes_stats_report_excludes_orders_with_no_paid_date(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		// Manually-created order: created in February but never paid (date_paid NULL).
		$this->seed_order( $rate_id, '2023-02-15 10:00:00', null );

		$sut = new StatsDataStore();

		$february = $sut->get_data( $this->taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59', $rate_id ) + array( 'interval' => 'day' ) );

		$this->assertSame( 0, $february->totals->orders_count, 'An order with no paid date should be excluded from the date_paid based Taxes stats totals.' );
	}

	/**
	 * @testdox Changing the analytics date type is wired to invalidate the report cache so reports do not serve stale numbers.
	 */
	public function test_changing_date_type_invalidates_report_cache(): void {
		ReportsSync::init();

		$this->assertNotFalse(
			has_action( 'update_option_woocommerce_date_type', array( ReportsCache::class, 'invalidate' ) ),
			'Changing the analytics date type should invalidate the report cache so all report families reflect the new basis immediately.'
		);
		$this->assertNotFalse(
			has_action( 'add_option_woocommerce_date_type', array( ReportsCache::class, 'invalidate' ) ),
			'The very first save of the date type takes the add_option path and should invalidate the report cache too.'
		);
	}

	/**
	 * @testdox Taxes report and Orders report agree on the order count for the same tax rate and period.
	 */
	public function test_taxes_report_reconciles_with_orders_report(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		// Two orders paid in February, plus one manual order created in February with no paid date.
		$this->seed_order( $rate_id, '2023-02-10 10:00:00', '2023-02-12 10:00:00' );
		$this->seed_order( $rate_id, '2023-02-14 10:00:00', '2023-02-16 10:00:00' );
		$this->seed_order( $rate_id, '2023-02-18 10:00:00', null );

		$taxes  = new DataStore();
		$orders = new OrdersDataStore();

		$taxes_data  = $taxes->get_data( $this->taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59', $rate_id ) );
		$orders_data = $orders->get_data(
			array(
				'after'             => '2023-02-01 00:00:00',
				'before'            => '2023-02-28 23:59:59',
				'tax_rate_includes' => array( $rate_id ),
				'per_page'          => 100,
				'page'              => 1,
			)
		);

		$taxes_count = isset( $taxes_data->data[0]['orders_count'] ) ? $taxes_data->data[0]['orders_count'] : 0;

		$this->assertSame( 2, $taxes_count, 'Only the two paid orders should be counted in the Taxes report.' );
		$this->assertSame( $taxes_count, (int) $orders_data->total, 'Taxes and Orders reports should agree on the order count for the same tax rate and period.' );
	}
}
