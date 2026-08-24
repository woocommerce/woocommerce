<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore as TaxesDataStore;
use Automattic\WooCommerce\Enums\OrderItemType;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\OrderTaxLookupMigrator;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
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

		$this->sut = wc_get_container()->get( OrderTaxLookupMigrator::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_calc_taxes', $this->original_calc_taxes );
		delete_option( OrderTaxLookupMigrator::CURSOR_OPTION );
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
	 * @testdox Processing a batch steps past an order that can no longer be loaded.
	 */
	public function test_process_batch_passes_over_an_order_it_cannot_load(): void {
		global $wpdb;

		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $order->get_id(), 0, 0.25 );

		$missing_order_id = $order->get_id() + 1000;
		$this->unmigrate_lookup_rows( $missing_order_id, 0, 3.0 );

		$this->sut->process_batch( $this->sut->get_next_batch_to_process( 10 ) );

		$this->assertSame( $missing_order_id, (int) get_option( OrderTaxLookupMigrator::CURSOR_OPTION ), 'The cursor should step past the whole batch.' );
		$this->assertSame( array(), $this->sut->get_next_batch_to_process( 10 ), 'An order that cannot be loaded should not hold the pass up.' );
		$this->assertSame( 1, $this->sut->get_total_pending_count(), 'An order that could not be rebuilt should still be counted.' );

		$wpdb->delete( TaxesDataStore::get_db_table_name(), array( 'order_id' => $missing_order_id ) );
	}

	/**
	 * @testdox Processing a batch carries on past an order whose rebuild fails, and leaves it pending.
	 */
	public function test_process_batch_leaves_an_order_it_could_not_rebuild_pending(): void {
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
		$this->assertSame( 1, $this->sut->get_total_pending_count(), 'The order that could not be rebuilt should still be counted, so the tool can offer another run.' );
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
			if ( $broken || 0 !== strpos( $query, "REPLACE INTO `{$table_name}`" ) ) {
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
	 * @testdox Starting the tool by hand revisits the orders an earlier pass stepped past.
	 */
	public function test_tool_starts_over_from_the_beginning(): void {
		$order = $this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );
		$this->unmigrate_lookup_rows( $order->get_id(), 0, 0.25 );

		update_option( OrderTaxLookupMigrator::CURSOR_OPTION, $order->get_id() + 1000 );
		$this->assertSame( array(), $this->sut->get_next_batch_to_process( 10 ), 'The cursor should be past the order to begin with.' );

		$this->sut->enqueue();

		$this->assertSame( array( $order->get_id() ), $this->sut->get_next_batch_to_process( 10 ), 'The order should be handed out again.' );
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
	 * @testdox The tool is disabled on a store with nothing to rebuild.
	 */
	public function test_tool_is_disabled_with_nothing_to_rebuild(): void {
		$this->seed_order_with_tax_lines( $this->tax_lines_sharing_a_rate_id() );

		$tools = $this->sut->handle_woocommerce_debug_tools( array() );

		$this->assertTrue( $tools['rebuild_analytics_tax_data']['disabled'], 'A store on the current shape should not be offered a rebuild.' );
	}
}
