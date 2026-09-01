<?php
/**
 * OrderTaxLookupMigrator class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore as TaxesDataStore;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Rebuilds the `wc_order_tax_lookup` rows of orders recorded before the table held one row per tax
 * order item, by re-syncing each order through the Taxes data store.
 *
 * Rows written before then carry the zero default of the `order_item_id` column, and the Taxes
 * report keeps matching those on their tax rate id alone, the way it did before the column
 * existed. So reporting stays as it was while this runs, and an order the processor cannot rebuild
 * keeps reporting the way it did.
 *
 * Additionally, this class manages the "Rebuild analytics tax data" tool.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 * @since 11.2.0
 */
class OrderTaxLookupMigrator implements BatchProcessorInterface, RegisterHooksInterface {

	/**
	 * Option holding the highest order id the processor has been through.
	 *
	 * The cursor is what bounds progress, so it outlives the run. An order the processor could not
	 * rebuild keeps its rows at zero; without the cursor every later batch would pick that order up
	 * again and the processor would never reach the end of the table. That is also why the option
	 * is left behind once the pass is done: clearing it would put those orders back in front of the
	 * next pass. Delete it by hand to run the rebuild over the whole table again.
	 *
	 * @var string
	 */
	const CURSOR_OPTION = 'woocommerce_order_tax_lookup_migration_last_order_id';

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
		return 'Rebuilds wc_order_tax_lookup rows recorded before the table held one row per tax order item, so that Analytics tax reports account for every tax line an order carries.';
	}

	/**
	 * Get the number of orders left to go through that still hold rows in the shape that predates
	 * the tax order item column, up to PENDING_COUNT_LIMIT.
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

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT COUNT(*) FROM ( SELECT DISTINCT order_id FROM {$table_name} WHERE order_id > %d AND order_item_id = 0 LIMIT %d ) AS pending",
				$this->get_cursor(),
				self::PENDING_COUNT_LIMIT
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

		$order_ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT DISTINCT order_id FROM {$table_name} WHERE order_id > %d AND order_item_id = 0 ORDER BY order_id ASC LIMIT %d",
				$this->get_cursor(),
				$size
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

			// Rows left behind by an order `wc_get_order()` cannot resolve are rows no report can
			// read: the Taxes report and its stats both join `wc_order_stats`, which an order that
			// is gone has no row in. Drop them rather than carrying them for good.
			if ( -1 === $synced && ! wc_get_order( $order_id ) ) {
				$wpdb->delete( TaxesDataStore::get_db_table_name(), array( 'order_id' => $order_id ), array( '%d' ) );
				continue;
			}

			// A write that did not land leaves the order holding the rows it came in with, which
			// report the way they did before. Log the order id so the failure can be looked at.
			if ( false === $synced ) {
				wc_get_logger()->error(
					"Could not rebuild the analytics tax lookup rows of order {$order_id}. The order keeps the rows it had and reports the way it did before.",
					array( 'source' => 'wc-order-tax-lookup-migration' )
				);
			}
		}

		// Step past every order in the batch, including any that could not be rebuilt. See
		// CURSOR_OPTION.
		update_option( self::CURSOR_OPTION, max( array_map( 'absint', $batch ) ), false );

		ReportsCache::invalidate();
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
				'desc'     => __( 'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. The database change the rebuild needs is missing on this store. Run "Verify base database tables" to apply it, then come back here.', 'woocommerce' ),
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
				'desc'     => __( 'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. There are currently no orders to rebuild.', 'woocommerce' ),
			);
		} elseif ( $batch_processor->is_enqueued( self::class ) ) {
			$tools['stop_rebuild_analytics_tax_data'] = array(
				'name'     => __( 'Stop rebuilding analytics tax data', 'woocommerce' ),
				'button'   => __( 'Stop rebuilding', 'woocommerce' ),
				'desc'     => sprintf(
					/* translators: %s: number of orders still to rebuild. */
					_n(
						'This will stop the background process that rebuilds the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. There is currently %s order left to rebuild.',
						'This will stop the background process that rebuilds the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. There are currently %s orders left to rebuild.',
						$pending_count,
						'woocommerce'
					),
					$pending_label
				),
				'callback' => array( $this, 'dequeue' ),
			);
		} else {
			$tools['rebuild_analytics_tax_data'] = array(
				'name'     => __( 'Rebuild analytics tax data', 'woocommerce' ),
				'button'   => __( 'Rebuild', 'woocommerce' ),
				'desc'     => sprintf(
					/* translators: %s: number of orders to rebuild. */
					_n(
						'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. The rebuild happens over time in the background (via Action Scheduler). There is currently %s order to rebuild.',
						'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. The rebuild happens over time in the background (via Action Scheduler). There are currently %s orders to rebuild.',
						$pending_count,
						'woocommerce'
					),
					$pending_label
				),
				'callback' => array( $this, 'enqueue' ),
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
	 * Highest order id the processor has been through.
	 *
	 * @return int
	 */
	private function get_cursor(): int {
		return (int) get_option( self::CURSOR_OPTION, 0 );
	}
}
