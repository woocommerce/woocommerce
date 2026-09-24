<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore as TaxesDataStore;
use Automattic\WooCommerce\Enums\OrderItemType;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\OrderTaxLookupMigrator;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
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
 * Tests for the OrderTaxLookupMigrator class.
 */
class OrderTaxLookupMigratorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderTaxLookupMigrator
	 */
	private $sut;

	/**
	 * Original woocommerce_calc_taxes option value.
	 *
	 * @var string|false
	 */
	private $original_calc_taxes;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_calc_taxes = get_option( 'woocommerce_calc_taxes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		WC_Helper_Reports::reset_stats_dbs();
		delete_option( OrderTaxLookupMigrator::CURSOR_OPTION );
		delete_option( OrderTaxLookupMigrator::SPLIT_CURSOR_OPTION );
		delete_option( OrderTaxLookupMigrator::SPLIT_END_OPTION );
		delete_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION );

		$this->sut = wc_get_container()->get( OrderTaxLookupMigrator::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_calc_taxes', $this->original_calc_taxes );
		delete_option( OrderTaxLookupMigrator::CURSOR_OPTION );
		delete_option( OrderTaxLookupMigrator::SPLIT_CURSOR_OPTION );
		delete_option( OrderTaxLookupMigrator::SPLIT_END_OPTION );
		delete_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION );
		wc_get_container()->get( BatchProcessingController::class )->remove_processor( OrderTaxLookupMigrator::class );

		parent::tearDown();
	}

	/**
	 * Create an order carrying the given tax lines and let the analytics sync record it.
	 *
	 * @param array $lines Tax lines, each with a `code`, a `rate_id` and a `tax_total`.
	 * @return WC_Order
	 */
	private function seed_order_with_tax_lines( array $lines ): WC_Order {
		$product = new WC_Product_Simple();
		$product->set_name( 'Repro Product' );
		$product->set_regular_price( '100' );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );

		foreach ( $lines as $line ) {
			$tax_item = new WC_Order_Item_Tax();
			$tax_item->set_name( $line['code'] );
			$tax_item->set_label( $line['code'] );
			$tax_item->set_rate_id( $line['rate_id'] );
			$tax_item->set_rate_percent( 0 );
			$tax_item->set_tax_total( $line['tax_total'] );
			$tax_item->set_shipping_tax_total( 0 );

			$order->add_item( $tax_item );
		}

		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		// A rate id of 0 matches the property default, so the data store never writes the meta.
		$rate_ids_by_code = wp_list_pluck( $lines, 'rate_id', 'code' );
		foreach ( $order->get_items( OrderItemType::TAX ) as $item_id => $tax_item ) {
			wc_update_order_item_meta( $item_id, 'rate_id', $rate_ids_by_code[ $tax_item->get_name() ] );
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		return $order;
	}

	/**
	 * Create a completed DE order with two product units, a fee and shipping, all taxed at one
	 * registered rate, and let the analytics sync record it.
	 *
	 * @param string $fee_total Fee total, negative for a discount.
	 * @return WC_Order
	 */
	private function seed_taxed_order( string $fee_total = '10' ): WC_Order {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_shipping_tax_class', '' );

		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate_state'    => '',
				'tax_rate'          => '19',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);

		$product = new WC_Product_Simple();
		$product->set_name( 'Taxable Product' );
		$product->set_regular_price( '100' );
		$product->save();

		$order = wc_create_order();
		$order->set_billing_country( 'DE' );
		$order->set_shipping_country( 'DE' );
		$order->add_product( $product, 2 );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_name( 'Handling' );
		$fee->set_total( $fee_total );
		$fee->set_tax_status( 'taxable' );
		$order->add_item( $fee );

		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Flat rate' );
		$shipping->set_method_id( 'flat_rate' );
		$shipping->set_total( '5' );
		$order->add_item( $shipping );

		$order->calculate_totals();
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_date_paid( time() );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		return $order;
	}

	/**
	 * Two tax lines that share a rate id, the shape an automated tax plugin produces.
	 *
	 * @return array
	 */
	private function tax_lines_sharing_a_rate_id(): array {
		return array(
			array(
				'code'      => 'US-CA-STATE-TAX-1',
				'rate_id'   => 0,
				'tax_total' => 6.0,
			),
			array(
				'code'      => 'US-CA-COUNTY-TAX-1',
				'rate_id'   => 0,
				'tax_total' => 0.25,
			),
		);
	}

	/**
	 * Replace an order's lookup rows with the single row the table held before it kept one row per
	 * tax order item.
	 *
	 * @param int   $order_id    Order id.
	 * @param int   $tax_rate_id Tax rate id the row carries.
	 * @param float $total_tax   Tax amount the row carries.
	 */
	private function unmigrate_lookup_rows( int $order_id, int $tax_rate_id, float $total_tax ): void {
		global $wpdb;

		$table_name = TaxesDataStore::get_db_table_name();

		$wpdb->delete( $table_name, array( 'order_id' => $order_id ) );
		$wpdb->insert(
			$table_name,
			array(
				'order_id'      => $order_id,
				'tax_rate_id'   => $tax_rate_id,
				'order_item_id' => 0,
				'date_created'  => '2023-02-10 10:00:00',
				'shipping_tax'  => 0,
				'order_tax'     => $total_tax,
				'total_tax'     => $total_tax,
			)
		);
	}

	/**
	 * Clear the taxable amount split of an order's lookup rows and set the base they add up to,
	 * the shape the table held before it recorded the order and shipping parts.
	 *
	 * @param int   $order_id       Order id.
	 * @param float $taxable_amount Base the rows carry.
	 */
	private function unsplit_lookup_rows( int $order_id, float $taxable_amount ): void {
		global $wpdb;

		$table_name = TaxesDataStore::get_db_table_name();

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"UPDATE {$table_name} SET taxable_amount = %f, order_taxable_amount = 0, shipping_taxable_amount = 0 WHERE order_id = %d",
				$taxable_amount,
				$order_id
			)
		);
	}

	/**
	 * Read an order's lookup rows.
	 *
	 * @param int $order_id Order id.
	 * @return array
	 */
	private function lookup_rows( int $order_id ): array {
		global $wpdb;

		$table_name = TaxesDataStore::get_db_table_name();

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT order_item_id, total_tax FROM {$table_name} WHERE order_id = %d ORDER BY order_item_id ASC",
				$order_id
			),
			ARRAY_A
		);
	}

	/**
	 * Clear the data store's per-request cache of the lookup table's key shape, so a key change
	 * made by this test is seen.
	 */
	private function reset_lookup_key_cache(): void {
		$property = new \ReflectionProperty( TaxesDataStore::class, 'lookup_keyed_by_order_item' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * @testdox Only orders still holding rows without a tax order item are pending.
	 */
	public function test_only_orders_in_the_old_shape_are_pending(): void {
		$migrated = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$old      = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );

		$this->assertSame( 0, $this->sut->get_total_pending_count(), 'A store on the current shape has nothing to rebuild.' );

		$this->unmigrate_lookup_rows( $old->get_id(), 0, 6.25 );

		$this->assertSame( 1, $this->sut->get_total_pending_count(), 'Only the order left in the old shape should be pending.' );
		$this->assertSame( array( $old->get_id() ), $this->sut->get_next_batch_to_process( 10 ), 'The batch should hold only that order.' );
		$this->assertNotEmpty( $this->lookup_rows( $migrated->get_id() ), 'The rebuilt order should be left alone.' );
	}

	/**
	 * @testdox An order holding a base with no order and shipping split is pending, one with no base to split is not.
	 */
	public function test_orders_holding_an_unsplit_taxable_amount_are_pending(): void {
		$unsplit  = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$no_base  = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$order_id = $unsplit->get_id();

		$this->unsplit_lookup_rows( $order_id, 215.0 );
		// A row with nothing to split would be rewritten to the same zeros, so it is left alone.
		$this->unsplit_lookup_rows( $no_base->get_id(), 0.0 );

		$this->assertSame( 1, $this->sut->get_total_pending_count(), 'Only the order holding a base with no split should be pending.' );
		$this->assertSame( array( $order_id ), $this->sut->get_next_batch_to_process( 10 ), 'The batch should hold only that order.' );
	}

	/**
	 * @testdox Processing a batch records the order and shipping parts of the taxable amount.
	 */
	public function test_process_batch_records_the_taxable_amount_split(): void {
		global $wpdb;

		$order = $this->seed_taxed_order();
		$this->unsplit_lookup_rows( $order->get_id(), 215.0 );

		$this->sut->process_batch( array( $order->get_id() ) );

		$table_name = TaxesDataStore::get_db_table_name();
		$sums       = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT SUM(order_taxable_amount) AS order_part, SUM(shipping_taxable_amount) AS shipping_part FROM {$table_name} WHERE order_id = %d",
				$order->get_id()
			)
		);

		// 2 x 100 product + 10 fee on the order side, 5 shipping on the shipping side.
		$this->assertSame( 210.0, (float) $sums->order_part, 'The rebuild should record the order part.' );
		$this->assertSame( 5.0, (float) $sums->shipping_part, 'The rebuild should record the shipping part.' );
		$this->assertSame( 0, $this->sut->get_total_pending_count(), 'A rebuilt order should stop being pending.' );
	}

	/**
	 * @testdox Processing a batch gives every tax line of the order its own lookup row.
	 */
	public function test_process_batch_rebuilds_one_row_per_tax_line(): void {
		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );

		// The single row a store carried out of the old schema: both lines share a rate id, so
		// only the last one written survived.
		$this->unmigrate_lookup_rows( $order->get_id(), 0, 0.25 );

		$this->sut->process_batch( array( $order->get_id() ) );

		$rows = $this->lookup_rows( $order->get_id() );
		$this->assertCount( 2, $rows, 'Each tax line of the order should end up with its own row.' );

		$item_ids = array_map( 'absint', array_column( $rows, 'order_item_id' ) );
		$this->assertSame( array_keys( $order->get_items( OrderItemType::TAX ) ), $item_ids, 'Every row should point at its tax order item.' );
		$this->assertEqualsWithDelta( 6.25, array_sum( array_map( 'floatval', array_column( $rows, 'total_tax' ) ) ), 0.001, 'The rows should add up to the tax the order carries.' );

		$this->assertSame( 0, $this->sut->get_total_pending_count(), 'A rebuilt order should stop being pending.' );
	}

	/**
	 * @testdox Processing a batch drops the rows of an order no report can read.
	 */
	public function test_process_batch_drops_the_rows_of_an_order_no_report_can_read(): void {
		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $order->get_id(), 0, 0.25 );

		$missing_order_id = $order->get_id() + 1000;
		$this->unmigrate_lookup_rows( $missing_order_id, 0, 3.0 );

		$this->sut->process_batch( $this->sut->get_next_batch_to_process( 10 ) );

		$this->assertSame( $missing_order_id, (int) get_option( OrderTaxLookupMigrator::CURSOR_OPTION ), 'The cursor should step past the whole batch.' );
		$this->assertSame( array(), $this->lookup_rows( $missing_order_id ), 'No report can read the rows of an order that is gone, so they should be dropped.' );
		$this->assertCount( 2, $this->lookup_rows( $order->get_id() ), 'The rest of the batch should still be rebuilt.' );
		$this->assertSame( array(), $this->sut->get_next_batch_to_process( 10 ), 'An order that cannot be loaded should not hold the pass up.' );
		$this->assertSame( 0, $this->sut->get_total_pending_count(), 'Nothing should be left pending once the pass is through.' );
		$this->assertSame( array(), OrdersScheduler::get_failed_order_imports()['ids'], 'An order whose rows were dropped has nothing left to retry, so it should not be recorded as a failed import.' );

		$tools = $this->sut->handle_woocommerce_debug_tools( array() );
		$this->assertTrue( $tools['rebuild_analytics_tax_data']['disabled'], 'The tool should not go on offering a run that cannot change anything.' );
	}

	/**
	 * @testdox Processing a batch keeps the rows of an order the reports still read but `wc_get_order()` cannot load.
	 */
	public function test_process_batch_keeps_the_rows_of_an_order_whose_type_is_not_registered(): void {
		global $wpdb;

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $order->get_id(), 0, 6.25 );

		// A deactivated plugin leaves its order type unregistered, so `wc_get_order()` fails while
		// the order's stats row stays behind and the reports go on reading its lookup rows.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$wpdb->update( OrdersTableDataStore::get_orders_table_name(), array( 'type' => 'shop_unknown' ), array( 'id' => $order->get_id() ) );
		} else {
			$wpdb->update( $wpdb->posts, array( 'post_type' => 'shop_unknown' ), array( 'ID' => $order->get_id() ) );
		}
		wp_cache_flush();

		$this->assertFalse( wc_get_order( $order->get_id() ), 'The order should not be loadable once its type is not registered.' );

		$this->sut->process_batch( array( $order->get_id() ) );

		$this->assertCount( 1, $this->lookup_rows( $order->get_id() ), 'An order the reports still read should keep its rows.' );
		$this->assertSame( $order->get_id(), (int) get_option( OrderTaxLookupMigrator::CURSOR_OPTION ), 'The cursor should step past the order.' );

		$failed = OrdersScheduler::get_failed_order_imports();

		$this->assertSame( array( $order->get_id() ), $failed['ids'], 'The cursor steps past an order that could not be read, so it should be left where Analytics settings offers a retry over it. A row left behind reports no taxable amount split for its whole rate.' );
	}

	/**
	 * @testdox Processing a batch carries on past an order whose rebuild fails, and leaves its rows alone.
	 */
	public function test_process_batch_carries_on_past_an_order_it_could_not_rebuild(): void {
		global $wpdb;

		$failing = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $failing->get_id(), 0, 6.25 );

		$rebuilt = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $rebuilt->get_id(), 0, 6.25 );

		$rows_before = $this->lookup_rows( $failing->get_id() );

		$suppress = $wpdb->suppress_errors( true );
		$restore  = $this->break_next_lookup_write();
		$this->sut->process_batch( $this->sut->get_next_batch_to_process( 10 ) );
		$restore();
		$wpdb->suppress_errors( $suppress );

		$this->assertSame( $rows_before, $this->lookup_rows( $failing->get_id() ), 'An order whose rebuild failed should keep the rows it had.' );
		$this->assertCount( 2, $this->lookup_rows( $rebuilt->get_id() ), 'The rest of the batch should still be rebuilt.' );
		$this->assertSame( $rebuilt->get_id(), (int) get_option( OrderTaxLookupMigrator::CURSOR_OPTION ), 'The cursor should step past the whole batch.' );
		$this->assertSame( array(), $this->sut->get_next_batch_to_process( 10 ), 'An order that could not be rebuilt should not hold the pass up.' );

		$failed = OrdersScheduler::get_failed_order_imports();

		$this->assertSame( array( $failing->get_id() ), $failed['ids'], 'The cursor steps past an order the rebuild could not finish, so it should be left where Analytics settings offers a retry over it.' );
		$this->assertNotContains( $rebuilt->get_id(), $failed['ids'], 'An order that was rebuilt should not be recorded as a failed import.' );
	}

	/**
	 * @testdox The rebuild waits for the re-key instead of parking the cursor at the end of the table.
	 */
	public function test_rebuild_waits_until_the_lookup_is_keyed_by_order_item(): void {
		global $wpdb;

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $order->get_id(), 0, 6.25 );

		$table_name = TaxesDataStore::get_db_table_name();

		// The shape a failed re-key leaves behind: dbDelta added the column, the key change never
		// landed. Rebuilding here would write every row back at zero and step past it for good.
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP PRIMARY KEY, ADD PRIMARY KEY (order_id, tax_rate_id)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
		$this->reset_lookup_key_cache();

		try {
			$this->assertSame( array(), $this->sut->get_next_batch_to_process( 10 ), 'No orders should be handed out while the re-key has not landed.' );
			$this->assertSame( 0, $this->sut->get_total_pending_count(), 'No order should count as pending while the rebuild cannot change it.' );
			$this->assertFalse( get_option( OrderTaxLookupMigrator::CURSOR_OPTION ), 'The cursor should stay where it is.' );

			$tools = $this->sut->handle_woocommerce_debug_tools( array() );
			$this->assertTrue( $tools['rebuild_analytics_tax_data']['disabled'], 'The tool should not offer a run that cannot change anything.' );
			$this->assertStringContainsString( 'Verify base database tables', $tools['rebuild_analytics_tax_data']['desc'], 'The tool should say how to put the re-key right.' );
		} finally {
			$wpdb->query( "ALTER TABLE `{$table_name}` DROP PRIMARY KEY, ADD PRIMARY KEY (order_id, tax_rate_id, order_item_id)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
			$this->reset_lookup_key_cache();
		}

		$this->assertSame( array( $order->get_id() ), $this->sut->get_next_batch_to_process( 10 ), 'The rebuild should pick the order up once the re-key has landed.' );
		$this->assertSame( 1, $this->sut->get_total_pending_count(), 'The order should be back to pending once the re-key has landed.' );
	}

	/**
	 * @testdox The pending count stops at its limit rather than counting the whole table.
	 */
	public function test_pending_count_stops_at_its_limit(): void {
		global $wpdb;

		$table_name = TaxesDataStore::get_db_table_name();
		$rows       = array();

		for ( $order_id = 1; $order_id <= OrderTaxLookupMigrator::PENDING_COUNT_LIMIT + 10; $order_id++ ) {
			$rows[] = "({$order_id}, 0, 0, '2023-02-10 10:00:00', 0, 1, 1)";
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is not user input, and the values are order ids counted out above.
		$wpdb->query( "INSERT INTO {$table_name} (order_id, tax_rate_id, order_item_id, date_created, shipping_tax, order_tax, total_tax) VALUES " . implode( ', ', $rows ) );

		$this->assertSame(
			OrderTaxLookupMigrator::PENDING_COUNT_LIMIT,
			$this->sut->get_total_pending_count(),
			'Nothing indexes the column the count reads, so it should stop counting once it has enough to report.'
		);
	}

	/**
	 * @testdox Processing a batch passes over an order with no creation date.
	 */
	public function test_process_batch_passes_over_an_order_with_no_creation_date(): void {
		global $wpdb;

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $order->get_id(), 0, 0.25 );

		// '0000-00-00 00:00:00' reads as no date at all, which is what `WC_Data::set_date_prop()`
		// makes of it.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$wpdb->update(
				OrdersTableDataStore::get_orders_table_name(),
				array( 'date_created_gmt' => null ),
				array( 'id' => $order->get_id() )
			);
		} else {
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_date'     => '0000-00-00 00:00:00',
					'post_date_gmt' => '0000-00-00 00:00:00',
				),
				array( 'ID' => $order->get_id() )
			);
		}
		wp_cache_flush();

		$this->sut->process_batch( array( $order->get_id() ) );

		$this->assertSame(
			$order->get_id(),
			(int) get_option( OrderTaxLookupMigrator::CURSOR_OPTION ),
			'An order with no creation date should be stepped past. A batch that raises never reaches the cursor write, and is handed out again forever.'
		);
		$this->assertNotEmpty( $this->lookup_rows( $order->get_id() ), 'An order that was stepped past should keep its rows.' );
		$this->assertSame( array( $order->get_id() ), OrdersScheduler::get_failed_order_imports()['ids'], 'An order the rebuild kept but could not rewrite should be left where Analytics settings offers a retry over it.' );
	}

	/**
	 * Make the next write to the lookup table fail, the way a database error mid-rebuild would.
	 *
	 * @return callable Removes the filter again.
	 */
	private function break_next_lookup_write(): callable {
		$table_name = TaxesDataStore::get_db_table_name();
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
	 * @testdox The tool reports and rebuilds what is left of the pass, not what an earlier one went through.
	 */
	public function test_tool_resumes_from_where_the_last_pass_stopped(): void {
		$stepped_past = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $stepped_past->get_id(), 0, 0.25 );

		$left = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $left->get_id(), 0, 0.25 );

		update_option( OrderTaxLookupMigrator::CURSOR_OPTION, $stepped_past->get_id() );

		$this->assertSame( 1, $this->sut->get_total_pending_count(), 'The count should hold what is left of the pass.' );

		$this->sut->enqueue();

		$this->assertSame( array( $left->get_id() ), $this->sut->get_next_batch_to_process( 10 ), 'The rebuild should pick up where it stopped.' );
		$this->assertSame( $stepped_past->get_id(), (int) get_option( OrderTaxLookupMigrator::CURSOR_OPTION ), 'Starting the tool should not rewind the pass.' );
	}

	/**
	 * @testdox The tool on the Status page starts and stops the rebuild.
	 */
	public function test_tool_starts_and_stops_the_rebuild(): void {
		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $order->get_id(), 0, 0.25 );

		$batch_processor = wc_get_container()->get( BatchProcessingController::class );

		$tools = $this->sut->handle_woocommerce_debug_tools( array() );
		$this->assertArrayHasKey( 'rebuild_analytics_tax_data', $tools, 'A store with orders to rebuild should be offered the tool.' );

		$this->sut->enqueue();
		$this->assertTrue( $batch_processor->is_enqueued( OrderTaxLookupMigrator::class ), 'The tool should hand the rebuild to the batch processing controller.' );

		$tools = $this->sut->handle_woocommerce_debug_tools( array() );
		$this->assertArrayHasKey( 'stop_rebuild_analytics_tax_data', $tools, 'A rebuild in progress should be offered a stop button.' );

		$this->sut->dequeue();
		$this->assertFalse( $batch_processor->is_enqueued( OrderTaxLookupMigrator::class ), 'The tool should be able to stop the rebuild.' );
	}

	/**
	 * @testdox The database update queues the rebuild.
	 */
	public function test_database_update_queues_the_rebuild(): void {
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		$batch_processor->remove_processor( OrderTaxLookupMigrator::class );

		wc_update_11201_migrate_tax_lookup_order_items();

		$this->assertTrue( $batch_processor->is_enqueued( OrderTaxLookupMigrator::class ), 'The database update should hand the rebuild to the batch processing controller.' );
	}

	/**
	 * @testdox The taxable amount split update runs the rebuild over the whole table again, on a cursor of its own.
	 */
	public function test_taxable_amount_split_update_restarts_the_rebuild(): void {
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		$batch_processor->remove_processor( OrderTaxLookupMigrator::class );

		// An order the earlier pass has already been through, holding a base with no split.
		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unsplit_lookup_rows( $order->get_id(), 215.0 );
		update_option( OrderTaxLookupMigrator::CURSOR_OPTION, $order->get_id() + 1 );

		$cache_version = ReportsCache::get_version();

		wc_update_1130_split_tax_lookup_taxable_amount();

		// The earlier pass keeps its place, so an order it could not rebuild is not put back in
		// front of it, and the split pass still reaches that order because it starts at the top of
		// the table on a cursor of its own.
		$this->assertSame( $order->get_id() + 1, (int) get_option( OrderTaxLookupMigrator::CURSOR_OPTION ), 'The update should leave the cursor of the earlier pass alone.' );
		$this->assertFalse( get_option( OrderTaxLookupMigrator::SPLIT_CURSOR_OPTION ), 'The split pass should start at the top of the table.' );
		$this->assertSame( array( $order->get_id() ), $this->sut->get_next_batch_to_process( 10 ), 'The split pass should reach an order the earlier pass has stepped past.' );
		$this->assertTrue( $batch_processor->is_enqueued( OrderTaxLookupMigrator::class ), 'The update should hand the rebuild to the batch processing controller.' );
		$this->assertNotSame( $cache_version, ReportsCache::get_version(), 'The update should invalidate the cached report responses, which last a week.' );
	}

	/**
	 * @testdox The split pass rebuilds an order whose base nets to zero, up to where the table ended when the update ran.
	 */
	public function test_split_pass_rebuilds_a_base_that_nets_to_zero(): void {
		global $wpdb;

		// A negative fee takes the order part to -5, which offsets the 5 of shipping.
		$order = $this->seed_taxed_order( '-205' );
		$this->unsplit_lookup_rows( $order->get_id(), 0.0 );

		wc_update_1130_split_tax_lookup_taxable_amount();

		$later = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unsplit_lookup_rows( $later->get_id(), 0.0 );

		$this->assertSame( $order->get_id(), (int) get_option( OrderTaxLookupMigrator::SPLIT_END_OPTION ), 'The update should mark the highest order id the table held.' );
		$this->assertSame( array( $order->get_id() ), $this->sut->get_next_batch_to_process( 10 ), 'Only the order recorded before the update should be pending. A later one with no base really did apply to nothing.' );

		$this->sut->process_batch( array( $order->get_id() ) );

		$table_name = TaxesDataStore::get_db_table_name();
		$sums       = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT SUM(order_taxable_amount) AS order_part, SUM(shipping_taxable_amount) AS shipping_part FROM {$table_name} WHERE order_id = %d",
				$order->get_id()
			)
		);

		$this->assertSame( -5.0, (float) $sums->order_part, 'The rebuild should record the order part.' );
		$this->assertSame( 5.0, (float) $sums->shipping_part, 'The rebuild should record the shipping part.' );
		$this->assertSame( 0, $this->sut->get_total_pending_count(), 'Nothing should be left pending once the pass is through.' );
	}

	/**
	 * @testdox A batch steps each pass past the orders it covered, and leaves a pass that is already further along.
	 */
	public function test_each_pass_keeps_its_own_cursor(): void {
		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unsplit_lookup_rows( $order->get_id(), 215.0 );

		$stepped_past = $order->get_id() + 1000;
		update_option( OrderTaxLookupMigrator::CURSOR_OPTION, $stepped_past );

		$this->sut->process_batch( array( $order->get_id() ) );

		$this->assertSame( $order->get_id(), (int) get_option( OrderTaxLookupMigrator::SPLIT_CURSOR_OPTION ), 'The split pass should step past the order it went through.' );
		$this->assertSame( $stepped_past, (int) get_option( OrderTaxLookupMigrator::CURSOR_OPTION ), 'A pass already further along should not be rewound to the batch.' );
	}

	/**
	 * @testdox The tool is disabled on a store with nothing to rebuild.
	 */
	public function test_tool_is_disabled_with_nothing_to_rebuild(): void {
		$this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );

		$tools = $this->sut->handle_woocommerce_debug_tools( array() );

		$this->assertTrue( $tools['rebuild_analytics_tax_data']['disabled'], 'A store on the current shape should not be offered a rebuild.' );
	}
}
