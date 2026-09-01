<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Taxes;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as OrdersDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\ReportsSync;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore as StatsDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Helper_Reports;
use WC_Order_Item_Fee;
use WC_Order_Item_Shipping;
use WC_Order_Item_Tax;
use WC_Product_Simple;
use WC_Tax;
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
	 * Original woocommerce_tax_based_on option value.
	 *
	 * @var string|false
	 */
	private $original_tax_based_on;

	/**
	 * Original woocommerce_shipping_tax_class option value.
	 *
	 * @var string|false
	 */
	private $original_shipping_tax_class;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_calc_taxes         = get_option( 'woocommerce_calc_taxes' );
		$this->original_date_type          = get_option( 'woocommerce_date_type' );
		$this->original_tax_based_on       = get_option( 'woocommerce_tax_based_on' );
		$this->original_shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		// Pin the tax location basis and the shipping tax class so the DE order fixtures
		// do not depend on the environment's store base address or on the class-inheritance
		// resolution, which varies with state left behind by the wider suite.
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_shipping_tax_class', '' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_calc_taxes', $this->original_calc_taxes );
		if ( false === $this->original_tax_based_on ) {
			delete_option( 'woocommerce_tax_based_on' );
		} else {
			update_option( 'woocommerce_tax_based_on', $this->original_tax_based_on );
		}
		if ( false === $this->original_shipping_tax_class ) {
			delete_option( 'woocommerce_shipping_tax_class' );
		} else {
			update_option( 'woocommerce_shipping_tax_class', $this->original_shipping_tax_class );
		}
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
	 * @param string $rate     Tax rate percentage.
	 * @param int    $priority Tax rate priority.
	 * @param int    $compound Whether the rate is compound.
	 * @return int
	 */
	private function insert_tax_rate( string $rate = '19', int $priority = 1, int $compound = 0 ): int {
		return (int) WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate_state'    => '',
				'tax_rate'          => $rate,
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => $priority,
				'tax_rate_compound' => $compound,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
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

	/**
	 * Create a completed DE order with two product units, a fee and shipping, all taxed.
	 *
	 * @return \WC_Order
	 */
	private function create_taxed_de_order(): \WC_Order {
		$product = new WC_Product_Simple();
		$product->set_name( 'Taxable Product' );
		$product->set_regular_price( '100' );
		$product->save();

		$order = wc_create_order();
		$order->set_billing_country( 'DE' );
		$order->set_shipping_country( 'DE' );
		$order->add_product( $product, 2 );

		$fee = new WC_Order_Item_Fee();
		$fee->set_name( 'Handling' );
		$fee->set_total( '10' );
		$fee->set_tax_status( 'taxable' );
		$order->add_item( $fee );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Flat rate' );
		$shipping->set_method_id( 'flat_rate' );
		$shipping->set_total( '5' );
		$order->add_item( $shipping );

		$order->calculate_totals();
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_date_paid( time() );
		$order->save();

		return $order;
	}

	/**
	 * @testdox Syncing an order records the net amount its tax rate applied to, and the reports expose it.
	 */
	public function test_sync_order_taxes_records_taxable_amount(): void {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		$order   = $this->create_taxed_de_order();

		OrdersStatsDataStore::sync_order( $order->get_id() );
		DataStore::sync_order_taxes( $order->get_id() );
		ReportsCache::invalidate();

		// 2 x 100 product + 10 fee + 5 shipping, all net of tax.
		$lookup_amount = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT taxable_amount FROM {$wpdb->prefix}wc_order_tax_lookup WHERE order_id = %d AND tax_rate_id = %d",
				$order->get_id(),
				$rate_id
			)
		);
		$this->assertSame( 215.0, (float) $lookup_amount, 'The lookup row should record the net total the rate applied to.' );

		$after  = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$before = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );

		$taxes_data = ( new DataStore() )->get_data( $this->taxes_query( $after, $before, $rate_id ) );
		$this->assertArrayHasKey( 'taxable_amount', $taxes_data->data[0] );
		$this->assertSame( 215.0, $taxes_data->data[0]['taxable_amount'], 'The Taxes report row should expose the taxable amount.' );
	}

	/**
	 * @testdox Syncing an order records the taxable amount for a zero-rated tax rate.
	 */
	public function test_sync_order_taxes_records_taxable_amount_for_zero_rate(): void {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate( '0' );
		$order   = $this->create_taxed_de_order();

		DataStore::sync_order_taxes( $order->get_id() );

		$lookup_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT total_tax, taxable_amount FROM {$wpdb->prefix}wc_order_tax_lookup WHERE order_id = %d AND tax_rate_id = %d",
				$order->get_id(),
				$rate_id
			)
		);
		$this->assertNotNull( $lookup_row, 'A zero-rated tax should still produce a lookup row.' );
		$this->assertSame( 0.0, (float) $lookup_row->total_tax );
		$this->assertSame( 215.0, (float) $lookup_row->taxable_amount, 'A zero-rated sale should record the base amount it was taxed on.' );
	}

	/**
	 * @testdox Syncing an order with a manual tax line no item carries records a zero taxable amount.
	 */
	public function test_sync_order_taxes_records_zero_taxable_amount_for_unapplied_rate(): void {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( $rate_id );
		$tax_item->set_tax_total( 19 );

		$order = wc_create_order();
		$order->add_item( $tax_item );
		$order->save();

		DataStore::sync_order_taxes( $order->get_id() );

		$lookup_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT taxable_amount FROM {$wpdb->prefix}wc_order_tax_lookup WHERE order_id = %d AND tax_rate_id = %d",
				$order->get_id(),
				$rate_id
			)
		);
		$this->assertNotNull( $lookup_row, 'A manual tax line should still produce a lookup row.' );
		$this->assertSame( 0.0, (float) $lookup_row->taxable_amount, 'A tax line applied to no order item should record a zero taxable amount.' );
	}

	/**
	 * @testdox Syncing an order records the base of a compound tax rate including the taxes it compounds over.
	 */
	public function test_sync_order_taxes_records_taxable_amount_for_compound_rate(): void {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$base_rate_id       = $this->insert_tax_rate( '5', 1 );
		$compound_rate_id   = $this->insert_tax_rate( '7', 2, 1 );
		$compound_rate_2_id = $this->insert_tax_rate( '10', 3, 1 );
		$order              = $this->create_taxed_de_order();

		DataStore::sync_order_taxes( $order->get_id() );

		$amounts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tax_rate_id, taxable_amount FROM {$wpdb->prefix}wc_order_tax_lookup WHERE order_id = %d",
				$order->get_id()
			),
			OBJECT_K
		);

		// 2 x 100 product + 10 fee + 5 shipping = 215 net; the first compound rate is
		// applied on top of the 5% tax (10.75), so its base is 225.75, and the second
		// compound rate is additionally applied on top of the first one's tax.
		$this->assertSame( 215.0, (float) $amounts[ $base_rate_id ]->taxable_amount, 'The non-compound rate base should be the net total.' );
		$this->assertSame( 225.75, (float) $amounts[ $compound_rate_id ]->taxable_amount, 'The compound rate base should include the taxes it compounds over.' );
		$this->assertEqualsWithDelta( 241.55, (float) $amounts[ $compound_rate_2_id ]->taxable_amount, 0.02, 'A second compound rate should also compound over the first one.' );
	}

	/**
	 * @testdox An order whose tax lines share one tax rate records the taxable amount once per rate.
	 */
	public function test_taxable_amount_is_recorded_once_for_tax_lines_sharing_a_rate(): void {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		$order   = $this->create_taxed_de_order();

		// Automated tax plugins can add several tax lines carrying the same rate id.
		$duplicate_tax_item = new WC_Order_Item_Tax();
		$duplicate_tax_item->set_rate( $rate_id );
		$duplicate_tax_item->set_tax_total( 0 );
		$order->add_item( $duplicate_tax_item );
		$order->save();

		DataStore::sync_order_taxes( $order->get_id() );

		// Pins the storage-layer invariant: however the lookup table is keyed, the base
		// written for one rate must not multiply across its rows - it is the amount the
		// rate applied to, counted once. The report query's join can still inflate
		// duplicated tax lines, but that is pre-existing and shared with total_tax.
		$summed = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(taxable_amount) FROM {$wpdb->prefix}wc_order_tax_lookup WHERE order_id = %d AND tax_rate_id = %d",
				$order->get_id(),
				$rate_id
			)
		);
		$this->assertSame( 215.0, (float) $summed, 'Tax lines sharing a rate id must not multiply the taxable amount.' );
	}

	/**
	 * @testdox While the taxable_amount column is missing, syncing still writes rows and the report still returns data.
	 */
	public function test_guards_apply_while_taxable_amount_column_is_missing(): void {
		global $wpdb;
		WC_Helper_Reports::reset_stats_dbs();

		$rate_id = $this->insert_tax_rate();
		$order   = $this->create_taxed_de_order();

		// Force the column-missing code paths through the static:: seam instead of
		// dropping the real column, which would break test transaction isolation.
		$sut       = new class() extends DataStore {
			/**
			 * Report the taxable_amount column as missing.
			 *
			 * @return bool
			 */
			public static function has_taxable_amount_column() {
				return false;
			}
		};
		$sut_class = get_class( $sut );

		$sut_class::sync_order_taxes( $order->get_id() );
		OrdersStatsDataStore::sync_order( $order->get_id() );
		ReportsCache::invalidate();

		$lookup_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT total_tax, taxable_amount FROM {$wpdb->prefix}wc_order_tax_lookup WHERE order_id = %d AND tax_rate_id = %d",
				$order->get_id(),
				$rate_id
			)
		);
		$this->assertNotNull( $lookup_row, 'The sync must still write lookup rows while the column is missing.' );
		$this->assertSame( 40.85, round( (float) $lookup_row->total_tax, 2 ), 'The tax columns must still be recorded.' );

		$after  = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$before = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		$query  = $this->taxes_query( $after, $before, $rate_id );

		$data = $sut->get_data( array_merge( $query, array( 'orderby' => 'taxable_amount' ) ) );
		$this->assertCount( 1, $data->data, 'Ordering by the missing column must fall back instead of erroring into an empty report.' );
		$this->assertArrayNotHasKey( 'taxable_amount', $data->data[0], 'The report must omit the column it cannot select.' );
	}
}
