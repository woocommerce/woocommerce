<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Taxes;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as OrdersDataStore;
use Automattic\WooCommerce\Admin\ReportsSync;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore as StatsDataStore;
use Automattic\WooCommerce\Enums\OrderItemType;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Order;
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

		// The sync writes the lookup rows; only the date needs forcing.
		$wpdb->update(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array( 'date_created' => $created_gmt ),
			array( 'order_id' => $order_id )
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

	/**
	 * Create a completed, paid order whose tax lines carry an arbitrary `rate_id`, including one
	 * that has no `woocommerce_tax_rates` row and one shared by several lines.
	 *
	 * WC_Order_Item_Tax::set_rate() cannot be used for that shape because it reads the rate row.
	 *
	 * @param array  $lines       Tax lines. Each is `code`, `label`, `rate_id`, `tax_total`, and an optional `rate_percent`.
	 * @param string $created_gmt Order creation datetime (GMT).
	 * @param string $paid_gmt    Order payment datetime (GMT).
	 * @return WC_Order
	 */
	private function seed_order_with_tax_lines( array $lines, string $created_gmt, string $paid_gmt ): WC_Order {
		global $wpdb;

		$product = new WC_Product_Simple();
		$product->set_name( 'Repro Product' );
		$product->set_regular_price( '100' );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );

		foreach ( $lines as $line ) {
			$tax_item = new WC_Order_Item_Tax();
			$tax_item->set_name( $line['code'] );
			$tax_item->set_label( $line['label'] );
			$tax_item->set_rate_id( $line['rate_id'] );
			$tax_item->set_tax_total( $line['tax_total'] );
			$tax_item->set_shipping_tax_total( 0 );

			if ( isset( $line['rate_percent'] ) ) {
				$tax_item->set_rate_percent( $line['rate_percent'] );
			}

			$order->add_item( $tax_item );
		}

		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		// A rate id of 0 matches the default, so the data store never writes the meta.
		$rate_ids_by_code = wp_list_pluck( $lines, 'rate_id', 'code' );
		foreach ( $order->get_items( OrderItemType::TAX ) as $item_id => $tax_item ) {
			wc_update_order_item_meta( $item_id, 'rate_id', $rate_ids_by_code[ $tax_item->get_name() ] );
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$order_id = $order->get_id();

		$wpdb->update(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array( 'date_created' => $created_gmt ),
			array( 'order_id' => $order_id )
		);

		$wpdb->update(
			$wpdb->prefix . 'wc_order_stats',
			array(
				'date_created'     => $created_gmt,
				'date_created_gmt' => $created_gmt,
				'date_paid'        => $paid_gmt,
				'date_completed'   => $paid_gmt,
			),
			array( 'order_id' => $order_id )
		);

		ReportsCache::invalidate();

		return $order;
	}

	/**
	 * Four jurisdiction tax lines that all carry `rate_id = 0`, the shape produced by an
	 * integration that calculates tax without registering its rates with WooCommerce.
	 *
	 * @return array
	 */
	private function tax_lines_sharing_a_rate_id(): array {
		return array(
			array(
				'code'         => 'US-CA-STATE-TAX',
				'label'        => 'State Tax',
				'rate_id'      => 0,
				'rate_percent' => 6.0,
				'tax_total'    => 6.0,
			),
			array(
				'code'         => 'US-CA-COUNTY-TAX',
				'label'        => 'County Tax',
				'rate_id'      => 0,
				'rate_percent' => 0.25,
				'tax_total'    => 0.25,
			),
			array(
				'code'         => 'US-CA-CITY-TAX',
				'label'        => 'City Tax',
				'rate_id'      => 0,
				'rate_percent' => 1.25,
				'tax_total'    => 1.25,
			),
			array(
				'code'         => 'US-CA-DISTRICT-TAX',
				'label'        => 'District Tax',
				'rate_id'      => 0,
				'rate_percent' => 2.25,
				'tax_total'    => 2.25,
			),
		);
	}

	/**
	 * Read the lookup rows for an order.
	 *
	 * @param int $order_id Order id.
	 * @return array
	 */
	private function lookup_rows( int $order_id ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT tax_rate_id, order_item_id, total_tax FROM {$wpdb->prefix}wc_order_tax_lookup WHERE order_id = %d ORDER BY order_item_id ASC",
				$order_id
			),
			ARRAY_A
		);
	}

	/**
	 * Build query args for a whole-month report request covering every tax rate.
	 *
	 * @param string $after  Period start (GMT).
	 * @param string $before Period end (GMT).
	 * @return array
	 */
	private function all_taxes_query( string $after, string $before ): array {
		return array(
			'after'    => $after,
			'before'   => $before,
			'per_page' => 100,
			'page'     => 1,
		);
	}

	/**
	 * @testdox Sync writes one lookup row per tax line, so lines sharing a rate id no longer overwrite each other.
	 */
	public function test_sync_writes_one_lookup_row_per_tax_line(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id(), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$rows = $this->lookup_rows( $order->get_id() );

		$this->assertCount( 4, $rows, 'Each tax line should get its own lookup row even though they share a rate id.' );
		$this->assertSame( 9.75, array_sum( array_column( $rows, 'total_tax' ) ), 'The lookup rows should add up to the tax the order actually carries.' );
		$this->assertCount( 4, array_unique( array_column( $rows, 'order_item_id' ) ), 'Every lookup row should point at a distinct tax order item.' );
	}

	/**
	 * @testdox Taxes stats totals count every tax line of an order whose lines share a rate id.
	 */
	public function test_taxes_stats_totals_include_every_tax_line(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id(), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$sut  = new StatsDataStore();
		$data = $sut->get_data( $this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) + array( 'interval' => 'day' ) );

		$this->assertSame( 9.75, $data->totals->total_tax, 'The Taxes stats total should match the tax the order carries.' );
		$this->assertSame( 1, $data->totals->orders_count, 'The order should be counted once however many tax lines it carries.' );
	}

	/**
	 * @testdox Taxes table report gives each tax line its own row and amount instead of repeating one collapsed amount.
	 */
	public function test_taxes_report_rows_do_not_fan_out_across_lines_sharing_a_rate_id(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id(), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$sut  = new DataStore();
		$data = $sut->get_data( $this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) );

		$this->assertCount( 4, $data->data, 'Each tax line should render as its own report row.' );

		$amounts = array_column( $data->data, 'total_tax' );
		sort( $amounts );
		$this->assertSame( array( 0.25, 1.25, 2.25, 6.0 ), $amounts, 'Each row should carry its own amount, not the same collapsed amount repeated.' );
		$this->assertSame( 9.75, array_sum( $amounts ), 'The report rows should add up to the tax the order carries.' );
	}

	/**
	 * @testdox Taxes stats segmented by tax rate id sum every tax line sharing that rate id.
	 */
	public function test_taxes_stats_segments_by_tax_rate_id_sum_every_line(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		$lines   = array(
			array(
				'code'         => 'DE-VAT-1',
				'label'        => 'VAT',
				'rate_id'      => $rate_id,
				'rate_percent' => 19.0,
				'tax_total'    => 19.0,
			),
			array(
				'code'         => 'DE-VAT-REDUCED-1',
				'label'        => 'VAT reduced',
				'rate_id'      => $rate_id,
				'rate_percent' => 7.0,
				'tax_total'    => 7.0,
			),
		);

		$this->seed_order_with_tax_lines( $lines, '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$sut  = new StatsDataStore();
		$data = $sut->get_data(
			$this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) + array(
				'interval'  => 'day',
				'segmentby' => 'tax_rate_id',
			)
		);

		$segments = wp_list_pluck( $data->totals->segments, 'subtotals', 'segment_id' );

		$this->assertArrayHasKey( $rate_id, $segments, 'The rate should appear as a segment of the Taxes stats totals.' );

		$subtotals = (array) $segments[ $rate_id ];
		$this->assertSame( 26.0, (float) $subtotals['total_tax'], 'The segment should sum both tax lines carrying that rate id.' );
	}

	/**
	 * @testdox Sync writes a lookup row for a tax line whose rate id has no woocommerce_tax_rates row.
	 */
	public function test_sync_handles_a_rate_id_with_no_tax_rates_row(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$lines = array(
			array(
				'code'         => 'US-CA-STATE-TAX',
				'label'        => 'State Tax',
				'rate_id'      => 4242,
				'rate_percent' => 6.0,
				'tax_total'    => 6.0,
			),
		);

		$order = $this->seed_order_with_tax_lines( $lines, '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$rows = $this->lookup_rows( $order->get_id() );

		$this->assertCount( 1, $rows, 'A rate id with no tax rate row should still be synced.' );
		$this->assertSame( 4242, (int) $rows[0]['tax_rate_id'] );

		$sut  = new DataStore();
		$data = $sut->get_data( $this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) );

		$this->assertCount( 1, $data->data, 'The line should still be reported even though its rate is not registered with WooCommerce.' );
		$this->assertSame( 6.0, $data->data[0]['total_tax'] );
	}

	/**
	 * @testdox Sync writes a lookup row for a tax line that carries no rate_percent meta.
	 */
	public function test_sync_handles_a_tax_line_with_no_rate_percent_meta(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$lines = array(
			array(
				'code'      => 'US-CA-STATE-TAX',
				'label'     => 'State Tax',
				'rate_id'   => 4242,
				'tax_total' => 6.0,
			),
		);

		$order = $this->seed_order_with_tax_lines( $lines, '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$this->assertCount( 1, $this->lookup_rows( $order->get_id() ), 'A tax line with no rate_percent meta should still be synced.' );

		$sut  = new StatsDataStore();
		$data = $sut->get_data( $this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) + array( 'interval' => 'day' ) );

		$this->assertSame( 6.0, $data->totals->total_tax, 'The Taxes stats total should include a line that carries no rate_percent meta.' );
	}

	/**
	 * @testdox Sync removes the lookup row of a tax line that has been removed from an order.
	 */
	public function test_sync_removes_lookup_rows_for_removed_tax_lines(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id(), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$this->assertCount( 4, $this->lookup_rows( $order->get_id() ) );

		$tax_items = $order->get_items( OrderItemType::TAX );
		$removed   = array_shift( $tax_items );
		$order->remove_item( $removed->get_id() );
		$order->save();

		DataStore::sync_order_taxes( $order->get_id() );

		$rows = $this->lookup_rows( $order->get_id() );

		$this->assertCount( 3, $rows, 'Removing a tax line from an order should remove its lookup row.' );
		$this->assertNotContains( (string) $removed->get_id(), array_column( $rows, 'order_item_id' ), 'The removed line should leave no lookup row behind.' );
	}

	/**
	 * @testdox Sync removes the lookup row a tax line left behind when its rate id changed.
	 */
	public function test_sync_removes_the_lookup_row_of_a_tax_line_whose_rate_id_changed(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_on_distinct_rate_ids( 'US-CA', 101 ), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$this->assertCount( 2, $this->lookup_rows( $order->get_id() ) );

		$tax_items = $order->get_items( OrderItemType::TAX );
		$moved     = array_shift( $tax_items );
		$moved->set_rate_id( 999 );
		$order->save();

		DataStore::sync_order_taxes( $order->get_id() );

		$rows = $this->lookup_rows( $order->get_id() );

		$this->assertCount( 2, $rows, 'A tax line that changed rate id should hold one row, not one on each rate id.' );
		$this->assertNotContains( '101', array_column( $rows, 'tax_rate_id' ), 'The row on the rate id the line carried before should be gone.' );

		$sut  = new DataStore();
		$data = $sut->get_data( $this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) );

		$this->assertSame( 6.25, array_sum( array_column( $data->data, 'total_tax' ) ), 'The order should not be counted twice because one of its lines changed rate id.' );
	}

	/**
	 * @testdox Sync passes over an order with no creation date instead of failing on it.
	 */
	public function test_sync_passes_over_an_order_with_no_creation_date(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$order       = $this->seed_order_with_tax_lines( $this->tax_lines_on_distinct_rate_ids( 'US-CA', 101 ), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );
		$rows_before = $this->lookup_rows( $order->get_id() );

		$this->clear_order_date_created( $order->get_id() );

		$this->assertSame( -1, DataStore::sync_order_taxes( $order->get_id() ), 'An order with no creation date has nothing to date its rows by, so it should be passed over.' );
		$this->assertSame( $rows_before, $this->lookup_rows( $order->get_id() ), 'An order that was passed over should keep its rows.' );
	}

	/**
	 * Take an order's creation date away, the way a store holding a zero datetime has.
	 *
	 * `WC_Data::set_date_prop()` reads '0000-00-00 00:00:00' as no date at all.
	 *
	 * @param int $order_id Order id.
	 */
	private function clear_order_date_created( int $order_id ): void {
		global $wpdb;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$wpdb->update(
				OrdersTableDataStore::get_orders_table_name(),
				array( 'date_created_gmt' => null ),
				array( 'id' => $order_id )
			);
		} else {
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_date'     => '0000-00-00 00:00:00',
					'post_date_gmt' => '0000-00-00 00:00:00',
				),
				array( 'ID' => $order_id )
			);
		}

		wp_cache_flush();
	}

	/**
	 * The Orders list joins the tax lookup for its rate filter, so one row per tax line is one
	 * joined row per tax line. What keeps the order out of the list twice is the DISTINCT the
	 * report selects with, and the count above the list being over distinct order ids.
	 *
	 * @testdox Orders report lists an order once when several of its tax lines share the filtered rate.
	 */
	public function test_orders_report_lists_an_order_once_when_its_tax_lines_share_the_filtered_rate(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		$lines   = array_map(
			function ( $line ) use ( $rate_id ) {
				$line['rate_id'] = $rate_id;
				return $line;
			},
			$this->tax_lines_sharing_a_rate_id()
		);

		$order = $this->seed_order_with_tax_lines( $lines, '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$this->assertCount( 4, $this->lookup_rows( $order->get_id() ), 'The order should hold a row per tax line to begin with.' );

		$orders   = new OrdersDataStore();
		$included = $orders->get_data(
			array(
				'after'             => '2023-02-01 00:00:00',
				'before'            => '2023-02-28 23:59:59',
				'tax_rate_includes' => array( $rate_id ),
				'per_page'          => 100,
				'page'              => 1,
			)
		);

		$this->assertCount( 1, $included->data, 'An order carrying several tax lines on one rate should be listed once, not once per line.' );
		$this->assertSame( 1, (int) $included->total, 'The list and the count above it should agree.' );

		$excluded = $orders->get_data(
			array(
				'after'             => '2023-02-01 00:00:00',
				'before'            => '2023-02-28 23:59:59',
				'tax_rate_excludes' => array( $rate_id ),
				'per_page'          => 100,
				'page'              => 1,
			)
		);

		$this->assertCount( 0, $excluded->data, 'Excluding the rate the order carries should leave it out of the list.' );
	}

	/**
	 * @testdox Sync leaves the rows an order already had alone when one of its writes fails.
	 */
	public function test_sync_keeps_existing_rows_when_a_write_fails(): void {
		global $wpdb;

		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_on_distinct_rate_ids( 'US-CA', 101 ), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		// The shape the rebuild finds an order in: every row on the column default.
		$this->unmigrate_lookup_rows( $order->get_id() );
		$rows_before = $this->lookup_rows( $order->get_id() );

		$suppress = $wpdb->suppress_errors( true );
		$restore  = $this->break_next_lookup_write();
		$result   = DataStore::sync_order_taxes( $order->get_id() );
		$restore();
		$wpdb->suppress_errors( $suppress );

		$this->assertFalse( $result, 'A write that did not land should be reported as a failed sync.' );
		$this->assertSame( $rows_before, $this->lookup_rows( $order->get_id() ), 'The rows the order came in with should survive a failed sync.' );

		$sut  = new DataStore();
		$data = $sut->get_data( $this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) );

		$this->assertCount( 2, $data->data, 'A failed sync should leave both tax lines reportable.' );

		$amounts = array_column( $data->data, 'total_tax' );
		sort( $amounts );
		$this->assertSame( array( 0.25, 6.0 ), $amounts, 'No line should be lost or counted twice because a sync failed.' );
	}

	/**
	 * @testdox Sync writes every tax line of an order in one statement.
	 */
	public function test_sync_writes_every_tax_line_of_an_order_in_one_statement(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id(), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$writes  = 0;
		$counter = $this->count_lookup_writes( $writes );

		DataStore::sync_order_taxes( $order->get_id() );

		$counter();

		$this->assertCount( 4, $this->lookup_rows( $order->get_id() ), 'Every tax line of the order should hold a row.' );
		$this->assertSame( 1, $writes, 'An order that is rebuilt line by line can be left with only some of its lines rebuilt, so the whole order should go in one write.' );
	}

	/**
	 * @testdox Sync leaves alone a row a sync running beside it wrote.
	 */
	public function test_sync_keeps_the_rows_a_sync_running_beside_it_wrote(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_on_distinct_rate_ids( 'US-CA', 101 ), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		$restore = $this->write_a_row_beside_the_next_sync( $order->get_id(), 777, 888 );

		DataStore::sync_order_taxes( $order->get_id() );

		$restore();

		$rows = $this->lookup_rows( $order->get_id() );

		$this->assertContains( '888', array_column( $rows, 'order_item_id' ), 'A sync should prune the rows it read and no others, so a row written beside it survives.' );
		$this->assertCount( 3, $rows, 'The order should hold its own two rows and the one written beside them.' );
	}

	/**
	 * Write a row for the order while the next sync is between reading the order's rows and
	 * pruning them, the way a sync of the same order running beside it does.
	 *
	 * @param int $order_id      Order id.
	 * @param int $tax_rate_id   Tax rate id to write the row on.
	 * @param int $order_item_id Tax order item id to write the row on.
	 * @return callable Removes the filter again.
	 */
	private function write_a_row_beside_the_next_sync( int $order_id, int $tax_rate_id, int $order_item_id ): callable {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wc_order_tax_lookup';
		$written    = false;

		$filter = function ( $query ) use ( &$written, $table_name, $order_id, $tax_rate_id, $order_item_id ) {
			global $wpdb;

			if ( $written || 0 !== strpos( $query, "REPLACE INTO {$table_name}" ) ) {
				return $query;
			}

			$written = true;

			$wpdb->insert(
				$table_name,
				array(
					'order_id'      => $order_id,
					'date_created'  => '2023-02-10 10:00:00',
					'tax_rate_id'   => $tax_rate_id,
					'order_item_id' => $order_item_id,
					'shipping_tax'  => 0,
					'order_tax'     => 1.0,
					'total_tax'     => 1.0,
				)
			);

			return $query;
		};

		add_filter( 'query', $filter );

		return function () use ( $filter ) {
			remove_filter( 'query', $filter );
		};
	}

	/**
	 * Make the next write to the lookup table fail, the way a database error mid-sync would.
	 *
	 * @return callable Removes the filter again.
	 */
	private function break_next_lookup_write(): callable {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wc_order_tax_lookup';
		$broken     = false;

		$filter = function ( $query ) use ( &$broken, $table_name ) {
			if ( $broken || 0 !== strpos( $query, "REPLACE INTO {$table_name}" ) ) {
				return $query;
			}

			$broken = true;

			// A table that does not exist, so the write fails the way a database error would.
			return "REPLACE INTO `{$table_name}_missing` (order_id) VALUES (1)";
		};

		add_filter( 'query', $filter );

		return function () use ( $filter ) {
			remove_filter( 'query', $filter );
		};
	}

	/**
	 * Count the writes a sync makes to the lookup table.
	 *
	 * @param int $writes Counter to increment, by reference.
	 * @return callable Removes the filter again.
	 */
	private function count_lookup_writes( int &$writes ): callable {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wc_order_tax_lookup';

		$filter = function ( $query ) use ( &$writes, $table_name ) {
			if ( 0 === strpos( $query, "REPLACE INTO {$table_name}" ) ) {
				++$writes;
			}

			return $query;
		};

		add_filter( 'query', $filter );

		return function () use ( $filter ) {
			remove_filter( 'query', $filter );
		};
	}

	/**
	 * Put an order's lookup rows back into the shape the table held before it held one row per tax
	 * order item.
	 *
	 * @param int $order_id Order id.
	 */
	private function unmigrate_lookup_rows( int $order_id ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array( 'order_item_id' => 0 ),
			array( 'order_id' => $order_id )
		);

		ReportsCache::invalidate();
	}

	/**
	 * Two tax lines on distinct rate ids, the shape almost every store's history is in.
	 *
	 * @param string $prefix Tax code prefix, so lines from different orders group separately.
	 * @param int    $rate_id_base First rate id; the second line takes the next one.
	 * @return array
	 */
	private function tax_lines_on_distinct_rate_ids( string $prefix, int $rate_id_base ): array {
		return array(
			array(
				'code'         => "{$prefix}-STATE-TAX",
				'label'        => 'State Tax',
				'rate_id'      => $rate_id_base,
				'rate_percent' => 6.0,
				'tax_total'    => 6.0,
			),
			array(
				'code'         => "{$prefix}-COUNTY-TAX",
				'label'        => 'County Tax',
				'rate_id'      => $rate_id_base + 1,
				'rate_percent' => 0.25,
				'tax_total'    => 0.25,
			),
		);
	}

	/**
	 * @testdox Taxes report still reports rows written before the lookup was keyed by tax order item.
	 */
	public function test_taxes_report_reads_rows_written_before_the_grain_change(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_on_distinct_rate_ids( 'US-CA', 101 ), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );

		// The schema change lands with the plugin files; the rebuild that fills the new column runs
		// later off a queue. Between the two, every existing row sits at the column default.
		$this->unmigrate_lookup_rows( $order->get_id() );

		$sut  = new DataStore();
		$data = $sut->get_data( $this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) );

		$this->assertCount( 2, $data->data, 'Rows waiting on the migration should still be reported, not dropped.' );

		$amounts = array_column( $data->data, 'total_tax' );
		sort( $amounts );
		$this->assertSame( array( 0.25, 6.0 ), $amounts, 'Each line should still carry its own amount.' );
	}

	/**
	 * @testdox Taxes report counts each tax line once while the lookup table is half migrated.
	 */
	public function test_taxes_report_counts_each_line_once_while_half_migrated(): void {
		update_option( 'woocommerce_date_type', 'date_paid' );
		WC_Helper_Reports::reset_stats_dbs();

		$old_order = $this->seed_order_with_tax_lines( $this->tax_lines_on_distinct_rate_ids( 'US-CA', 101 ), '2023-02-10 10:00:00', '2023-02-10 10:00:00' );
		$this->seed_order_with_tax_lines( $this->tax_lines_on_distinct_rate_ids( 'US-NY', 201 ), '2023-02-11 10:00:00', '2023-02-11 10:00:00' );

		// Only the first order is left in the old shape, which is what a store looks like part way
		// through the rebuild. Matching on the rate id must not spill onto the rebuilt rows.
		$this->unmigrate_lookup_rows( $old_order->get_id() );

		$sut  = new DataStore();
		$data = $sut->get_data( $this->all_taxes_query( '2023-02-01 00:00:00', '2023-02-28 23:59:59' ) );

		$this->assertCount( 4, $data->data, 'Both orders should report both of their tax lines.' );

		$amounts = array_column( $data->data, 'total_tax' );
		sort( $amounts );
		$this->assertSame( array( 0.25, 0.25, 6.0, 6.0 ), $amounts, 'No line should be counted twice or lost.' );
		$this->assertSame( 12.5, array_sum( $amounts ), 'The report should add up to the tax both orders carry.' );
	}
}
