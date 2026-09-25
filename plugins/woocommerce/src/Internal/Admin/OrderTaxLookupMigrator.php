<?php
/**
 * OrderTaxLookupMigrator class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrderStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore as TaxesDataStore;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Rebuilds the `wc_order_tax_lookup` rows of orders recorded before the table held the full tax
 * detail the reports read today, by re-syncing each order through the Taxes data store.
 *
 * It makes one pass per shape: rows written before the table held one row per tax order item
 * (`order_item_id = 0`), and rows written before the taxable amount was split into its order and
 * shipping parts. Reporting stays as it was while this runs, and an order the processor cannot
 * rebuild keeps reporting the way it did.
 *
 * Additionally, this class manages the "Rebuild analytics tax data" tool.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 * @since 11.2.0
 */
class OrderTaxLookupMigrator implements BatchProcessorInterface, RegisterHooksInterface {

	/**
	 * Option holding the highest order id the tax order item pass has been through.
	 *
	 * The cursor is what bounds progress, so it outlives the run. An order the processor could not
	 * rebuild keeps its rows at zero; without the cursor every later batch would pick that order up
	 * again and the processor would never reach the end of the table. Such an order is recorded as
	 * a failed analytics import instead, which is retried from Analytics settings. That is also why
	 * the option is left behind once the pass is done: clearing it would put those orders back in
	 * front of the next pass. Delete it by hand to run the pass over the whole table again.
	 *
	 * @var string
	 */
	const CURSOR_OPTION = 'woocommerce_order_tax_lookup_migration_last_order_id';

	/**
	 * Option holding the highest order id the taxable amount split pass has been through.
	 *
	 * A cursor of its own, since resetting the shared one would race a batch in flight, which
	 * writes back the cursor it read before the reset.
	 *
	 * @since 11.3.0
	 *
	 * @var string
	 */
	const SPLIT_CURSOR_OPTION = 'woocommerce_order_tax_lookup_split_migration_last_order_id';

	/**
	 * How far `get_total_pending_count()` counts before it reports "this many or more".
	 *
	 * Nothing indexes the tax order item column, so counting every order left to rebuild reads the
	 * lookup table end to end, and Status > Tools runs that count on every render. The tool only
	 * has to say whether there is work left and roughly how much of it, so stop counting once
	 * there is enough to report.
	 *
	 * @var int
	 */
	const PENDING_COUNT_LIMIT = 1000;

	/**
	 * Register this class instance to the appropriate hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_debug_tools', array( $this, 'handle_woocommerce_debug_tools' ), 999, 1 );
	}

	/**
	 * Get a user-friendly name for this processor.
	 *
	 * @return string Name of the processor.
	 */
	public function get_name(): string {
		return 'Order tax lookup tax order item migrator';
	}

	/**
	 * Get a user-friendly description for this processor.
	 *
	 * @return string Description of what this processor does.
	 */
	public function get_description(): string {
		return 'Rebuilds wc_order_tax_lookup rows recorded before the table held the full tax detail the reports read today, so that Analytics tax reports account for every tax line an order carries and for the amounts each rate applied to.';
	}

	/**
	 * The passes the rebuild makes over the lookup table. A pass is only offered once the columns
	 * it reads exist.
	 *
	 * @return array[] List of `array( 'condition' => string, 'cursor' => string )`.
	 */
	private function get_pending_passes(): array {
		$passes = array(
			array(
				'condition' => 'order_item_id = 0',
				'cursor'    => self::CURSOR_OPTION,
			),
		);

		if ( TaxesDataStore::has_taxable_amount_split_columns() ) {
			$passes[] = array(
				'condition' => 'order_taxable_amount IS NULL',
				'cursor'    => self::SPLIT_CURSOR_OPTION,
			);
		}

		return $passes;
	}

	/**
	 * SQL matching the lookup rows the rebuild would rewrite, each pass from its own cursor.
	 *
	 * @return array `array( 'where' => string, 'values' => int[] )`, the values in placeholder order.
	 */
	private function get_pending_rows_sql(): array {
		$clauses = array();
		$values  = array();

		foreach ( $this->get_pending_passes() as $pass ) {
			$clauses[] = "( order_id > %d AND ( {$pass['condition']} ) )";
			$values[]  = $this->get_cursor( $pass['cursor'] );
		}

		return array(
			'where'  => '( ' . implode( ' OR ', $clauses ) . ' )',
			'values' => $values,
		);
	}

	/**
	 * Get the number of orders left to go through that still hold rows in an outdated shape, up to
	 * PENDING_COUNT_LIMIT.
	 *
	 * Counts from the cursor, the same place `get_next_batch_to_process()` reads from, so the
	 * number the tool shows is the number the rebuild will actually get through. Counting the whole
	 * table instead would leave the tool offering a run over orders every pass steps past.
	 *
	 * @return int Number of orders pending processing, at most PENDING_COUNT_LIMIT.
	 */
	public function get_total_pending_count(): int {
		global $wpdb;

		// While the lookup is not keyed by tax order item there is nothing the rebuild can change,
		// so no order counts as pending. See get_next_batch_to_process().
		if ( ! TaxesDataStore::lookup_is_keyed_by_order_item() ) {
			return 0;
		}

		$table_name = TaxesDataStore::get_db_table_name();
		$pending    = $this->get_pending_rows_sql();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT COUNT(*) FROM ( SELECT DISTINCT order_id FROM {$table_name} WHERE {$pending['where']} LIMIT %d ) AS pending",
				array_merge( $pending['values'], array( self::PENDING_COUNT_LIMIT ) )
			)
		);
	}

	/**
	 * Returns the ids of the next orders to rebuild.
	 *
	 * @param int $size Maximum size of the batch to be returned.
	 *
	 * @throws Exception On a database error, so that an empty batch is never mistaken for the end
	 *                   of the table.
	 *
	 * @return array Batch of order ids, containing $size or less items.
	 */
	public function get_next_batch_to_process( int $size ): array {
		global $wpdb;

		// On a table the re-key in `WC_Install::create_tables()` never reached, the sync would
		// write every row back at zero and the pass would park the cursor at the end of the table
		// with nothing rebuilt. Hand out nothing instead: an empty batch retires the processor
		// with the cursor where it stands, so the rebuild is still on offer once the re-key has
		// landed.
		if ( ! TaxesDataStore::lookup_is_keyed_by_order_item() ) {
			return array();
		}

		$table_name = TaxesDataStore::get_db_table_name();
		$pending    = $this->get_pending_rows_sql();

		$order_ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT DISTINCT order_id FROM {$table_name} WHERE {$pending['where']} ORDER BY order_id ASC LIMIT %d",
				array_merge( $pending['values'], array( $size ) )
			)
		);

		if ( $wpdb->last_error ) {
			// An empty batch reads as "nothing left to do" and retires the processor, which would
			// leave the rest of the table behind. Report the failure instead, which fails the
			// scheduled action and leaves the controller's watchdog to schedule another attempt.
			// The controller only counts failures its process_batch() call throws, so a database
			// that stays broken is retried rather than retired.
			throw new Exception( $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return array_map( 'absint', $order_ids );
	}

	/**
	 * Re-sync the orders in the batch, which writes one row per tax order item and, once they are
	 * all written, drops whatever the order no longer carries.
	 *
	 * @param array $batch Batch of order ids, as returned by 'get_next_batch_to_process'.
	 *
	 * @return void
	 */
	public function process_batch( array $batch ): void {
		global $wpdb;

		if ( empty( $batch ) ) {
			return;
		}

		foreach ( $batch as $order_id ) {
			$order_id = (int) $order_id;
			$synced   = TaxesDataStore::sync_order_taxes( $order_id );

			// The reports only see lookup rows they can join to a `wc_order_stats` row, so an order
			// without one is gone as far as Analytics is concerned and its rows can go. A failed
			// `wc_get_order()` is not the same thing: it also fails while the plugin that registers
			// the order's type is deactivated, and that order still has its stats row.
			if ( -1 === $synced && ! $this->order_has_stats_row( $order_id ) ) {
				$wpdb->delete( TaxesDataStore::get_db_table_name(), array( 'order_id' => $order_id ), array( '%d' ) );
				continue;
			}

			// The cursor steps past an order that could not be rebuilt, so record it as a failed
			// analytics import, which Analytics settings offers to retry.
			if ( true !== $synced ) {
				$reason = -1 === $synced
					? 'The order could not be read (which is what a deactivated order type plugin looks like) or has no creation date to report it by.'
					: 'The write did not land.';

				wc_get_logger()->error(
					"Could not rebuild the analytics tax lookup rows of order {$order_id}. {$reason} The order keeps the rows it had and reports the way it did before. It is recorded as a failed analytics import, so it can be retried from Analytics settings.",
					array( 'source' => 'wc-order-tax-lookup-migration' )
				);

				OrdersScheduler::record_failed_order_import( $order_id );
			}
		}

		// Step past every order in the batch, including any that could not be rebuilt, which are
		// left to the failed import retry. See CURSOR_OPTION.
		$this->advance_cursors( array_map( 'absint', $batch ) );

		ReportsCache::invalidate();
	}

	/**
	 * Step every pass past the orders the batch covered.
	 *
	 * A pass never moves back, and stops short of any order it still has to rebuild that the batch
	 * left out. That happens when the pass came on offer while the batch was in flight.
	 *
	 * @param non-empty-array<int> $batch Order ids of the batch.
	 */
	private function advance_cursors( array $batch ): void {
		global $wpdb;

		$table_name   = TaxesDataStore::get_db_table_name();
		$last         = max( $batch );
		$placeholders = implode( ', ', array_fill( 0, count( $batch ), '%d' ) );

		foreach ( $this->get_pending_passes() as $pass ) {
			$cursor = $this->get_cursor( $pass['cursor'] );

			if ( $cursor >= $last ) {
				continue;
			}

			$skipped = $wpdb->get_var(
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The values come as one array.
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
					"SELECT MIN(order_id) FROM {$table_name} WHERE order_id > %d AND order_id < %d AND order_id NOT IN ( {$placeholders} ) AND ( {$pass['condition']} )",
					array_merge( array( $cursor, $last ), $batch )
				)
			);

			if ( $wpdb->last_error ) {
				continue;
			}

			update_option( $pass['cursor'], null === $skipped ? $last : (int) $skipped - 1, false );
		}
	}

	/**
	 * Default (preferred) batch size to pass to 'get_next_batch_to_process'.
	 *
	 * A batch is a `wc_get_order()` and a handful of writes per order, so it is sized like the
	 * analytics order importer rather than like a single-query migration.
	 *
	 * @return int Default batch size.
	 */
	public function get_default_batch_size(): int {
		return 100;
	}

	/**
	 * Add the tool to start or stop the background rebuild.
	 *
	 * @param array $tools Old tools array.
	 * @return array Updated tools array.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function handle_woocommerce_debug_tools( array $tools ): array {
		// A failed re-key would otherwise go unseen here: with no order counting as pending, the
		// tool would say there is nothing to rebuild. Say what is actually missing instead.
		if ( ! TaxesDataStore::lookup_is_keyed_by_order_item() ) {
			$tools['rebuild_analytics_tax_data'] = array(
				'name'     => __( 'Rebuild analytics tax data', 'woocommerce' ),
				'button'   => __( 'Rebuild', 'woocommerce' ),
				'disabled' => true,
				'desc'     => __( 'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept the full tax detail it reports today. The database change the rebuild needs is missing on this store. Run "Verify base database tables" to apply it, then come back here.', 'woocommerce' ),
			);

			return $tools;
		}

		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		$pending_count   = $this->get_total_pending_count();

		// The count stops at PENDING_COUNT_LIMIT, so say "or more" rather than a number the store
		// has already gone past.
		$pending_label = $pending_count < self::PENDING_COUNT_LIMIT
			? number_format_i18n( $pending_count )
			/* translators: %s: number of orders, where there are at least that many. */
			: sprintf( __( '%s+', 'woocommerce' ), number_format_i18n( self::PENDING_COUNT_LIMIT ) );

		if ( 0 === $pending_count ) {
			$tools['rebuild_analytics_tax_data'] = array(
				'name'     => __( 'Rebuild analytics tax data', 'woocommerce' ),
				'button'   => __( 'Rebuild', 'woocommerce' ),
				'disabled' => true,
				'desc'     => __( 'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept the full tax detail it reports today. There are currently no orders to rebuild.', 'woocommerce' ),
			);
		} elseif ( $batch_processor->is_enqueued( self::class ) ) {
			$tools['stop_rebuild_analytics_tax_data'] = array(
				'name'             => __( 'Stop rebuilding analytics tax data', 'woocommerce' ),
				'button'           => __( 'Stop rebuilding', 'woocommerce' ),
				'requires_refresh' => true,
				'desc'             => sprintf(
					/* translators: %s: number of orders still to rebuild. */
					_n(
						'This will stop the background process that rebuilds the Analytics tax data of orders recorded before WooCommerce kept the full tax detail it reports today. There is currently %s order left to rebuild.',
						'This will stop the background process that rebuilds the Analytics tax data of orders recorded before WooCommerce kept the full tax detail it reports today. There are currently %s orders left to rebuild.',
						$pending_count,
						'woocommerce'
					),
					$pending_label
				),
				'callback'         => array( $this, 'dequeue' ),
			);
		} else {
			$tools['rebuild_analytics_tax_data'] = array(
				'name'             => __( 'Rebuild analytics tax data', 'woocommerce' ),
				'button'           => __( 'Rebuild', 'woocommerce' ),
				'requires_refresh' => true,
				'desc'             => sprintf(
					/* translators: %s: number of orders to rebuild. */
					_n(
						'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept the full tax detail it reports today. The rebuild happens over time in the background (via Action Scheduler). There is currently %s order to rebuild.',
						'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept the full tax detail it reports today. The rebuild happens over time in the background (via Action Scheduler). There are currently %s orders to rebuild.',
						$pending_count,
						'woocommerce'
					),
					$pending_label
				),
				'callback'         => array( $this, 'enqueue' ),
			);
		}

		return $tools;
	}

	/**
	 * Start the background rebuild.
	 *
	 * @return string Informative string to show after the tool is triggered in UI.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function enqueue(): string {
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );

		if ( $batch_processor->is_enqueued( self::class ) ) {
			return __( 'Background process for rebuilding analytics tax data already started, nothing done.', 'woocommerce' );
		}

		$batch_processor->enqueue_processor( self::class );

		return __( 'Background process for rebuilding analytics tax data started.', 'woocommerce' );
	}

	/**
	 * Stop the background rebuild.
	 *
	 * @return string Informative string to show after the tool is triggered in UI.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function dequeue(): string {
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );

		if ( ! $batch_processor->is_enqueued( self::class ) ) {
			return __( 'Background process for rebuilding analytics tax data not started, nothing done.', 'woocommerce' );
		}

		$batch_processor->remove_processor( self::class );

		return __( 'Background process for rebuilding analytics tax data stopped.', 'woocommerce' );
	}

	/**
	 * Whether the order has a row in the order stats table, which is the table every analytics
	 * report reads orders through.
	 *
	 * @param int $order_id Order id.
	 * @return bool
	 */
	private function order_has_stats_row( int $order_id ): bool {
		global $wpdb;

		$table_name = OrderStatsDataStore::get_db_table_name();

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT 1 FROM {$table_name} WHERE order_id = %d",
				$order_id
			)
		);
	}

	/**
	 * Highest order id a pass has been through.
	 *
	 * @param string $option Cursor option of the pass.
	 * @return int
	 */
	private function get_cursor( string $option ): int {
		return (int) get_option( $option, 0 );
	}
}
