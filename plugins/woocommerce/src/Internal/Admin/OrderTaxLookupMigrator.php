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
 * costs nothing beyond staying as it was.
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
	 * The cursor is what bounds progress, so it outlives the run. An order that `wc_get_order()`
	 * cannot resolve keeps its rows at zero; without the cursor every later batch would pick that
	 * order up again and the processor would never reach the end of the table.
	 *
	 * @var string
	 */
	const CURSOR_OPTION = 'woocommerce_order_tax_lookup_migration_last_order_id';

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
	 * Get the number of orders still holding rows in the shape that predates the tax order item
	 * column.
	 *
	 * Deliberately blind to the cursor, unlike the batch itself: an order a pass could not rebuild
	 * is still an order in the old shape, and the tool has to be able to say so and offer another
	 * run. `BatchProcessingController` decides when a processor is finished from
	 * `get_next_batch_to_process()`, not from this count, so it cannot be kept alive by one.
	 *
	 * @return int Number of orders pending processing.
	 */
	public function get_total_pending_count(): int {
		global $wpdb;

		$table_name = TaxesDataStore::get_db_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
		return (int) $wpdb->get_var( "SELECT COUNT( DISTINCT order_id ) FROM {$table_name} WHERE order_item_id = 0" );
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
			// leave the rest of the table behind. Report the failure instead: the controller
			// counts it, and its watchdog schedules another attempt.
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
	 * @throws Exception On a database error while looking for further work.
	 *
	 * @return void
	 */
	public function process_batch( array $batch ): void {
		if ( empty( $batch ) ) {
			return;
		}

		foreach ( $batch as $order_id ) {
			$order_id = (int) $order_id;

			// `-1` is an order `wc_get_order()` could not resolve, which the cursor is here to step
			// past. `false` is a write that did not land, which leaves the order holding the rows
			// it came in with: `get_total_pending_count()` goes on counting it, so the tool can
			// offer another run over it once whatever the database objected to is out of the way.
			if ( false === TaxesDataStore::sync_order_taxes( $order_id ) ) {
				wc_get_logger()->error(
					"Could not rebuild the analytics tax lookup rows of order {$order_id}. The order keeps the rows it had and can be rebuilt again from WooCommerce > Status > Tools.",
					array( 'source' => 'wc-order-tax-lookup-migration' )
				);
			}
		}

		// Step past every order in the batch, including any that could not be loaded. See
		// CURSOR_OPTION.
		update_option( self::CURSOR_OPTION, max( array_map( 'absint', $batch ) ), false );

		if ( ! $this->has_pending_orders() ) {
			ReportsCache::invalidate();
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
		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		$pending_count   = $this->get_total_pending_count();

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
					/* translators: %d: number of orders still to rebuild. */
					_n(
						'This will stop the background process that rebuilds the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. There is currently %d order left to rebuild.',
						'This will stop the background process that rebuilds the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. There are currently %d orders left to rebuild.',
						$pending_count,
						'woocommerce'
					),
					$pending_count
				),
				'callback' => array( $this, 'dequeue' ),
			);
		} else {
			$tools['rebuild_analytics_tax_data'] = array(
				'name'     => __( 'Rebuild analytics tax data', 'woocommerce' ),
				'button'   => __( 'Rebuild', 'woocommerce' ),
				'desc'     => sprintf(
					/* translators: %d: number of orders to rebuild. */
					_n(
						'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. The rebuild happens over time in the background (via Action Scheduler). There is currently %d order to rebuild.',
						'This will rebuild the Analytics tax data of orders recorded before WooCommerce kept a record of every tax line. The rebuild happens over time in the background (via Action Scheduler). There are currently %d orders to rebuild.',
						$pending_count,
						'woocommerce'
					),
					$pending_count
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

		// Start over, so that a run started by hand revisits the orders an earlier pass stepped
		// past. Reaching an order a second time costs one re-sync and nothing else.
		delete_option( self::CURSOR_OPTION );

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
	 * Whether the current pass has any order left to go through.
	 *
	 * @return bool
	 */
	private function has_pending_orders(): bool {
		global $wpdb;

		$table_name = TaxesDataStore::get_db_table_name();

		return null !== $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is not user input.
				"SELECT order_id FROM {$table_name} WHERE order_id > %d AND order_item_id = 0 LIMIT 1",
				$this->get_cursor()
			)
		);
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
