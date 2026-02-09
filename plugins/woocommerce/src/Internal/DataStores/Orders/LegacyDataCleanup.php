<?php
/**
 * LegacyDataCleanup class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles the background process in charge of cleaning up legacy data for orders when HPOS is authoritative.
 */
class LegacyDataCleanup implements BatchProcessorInterface {

	/**
	 * Option name for this feature.
	 *
	 * @deprecated 9.1.0
	 */
	public const OPTION_NAME = 'woocommerce_hpos_legacy_data_cleanup_in_progress';

	/**
	 * The default number of orders to process per batch.
	 */
	private const BATCH_SIZE = 25;

	/**
	 * The batch processing controller to use.
	 *
	 * @var BatchProcessingController
	 */
	private $batch_processing;

	/**
	 * The legacy handler to use for the actual cleanup.
	 *
	 * @var LegacyHandler
	 */
	private $legacy_handler;

	/**
	 * The data synchronizer object to use.
	 *
	 * @var DataSynchronizer
	 */
	private $data_synchronizer;

	/**
	 * Logger object to be used to log events.
	 *
	 * @var \WC_Logger
	 */
	private $error_logger;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param BatchProcessingController $batch_processing  The batch processing controller to use.
	 * @param LegacyDataHandler         $legacy_handler    Legacy order data handler instance.
	 * @param DataSynchronizer          $data_synchronizer Data synchronizer instance.
	 * @internal
	 */
	final public function init( BatchProcessingController $batch_processing, LegacyDataHandler $legacy_handler, DataSynchronizer $data_synchronizer ) {
		$this->legacy_handler    = $legacy_handler;
		$this->data_synchronizer = $data_synchronizer;
		$this->batch_processing  = $batch_processing;
		$this->error_logger      = wc_get_logger();
	}

	/**
	 * A user friendly name for this process.
	 *
	 * @return string Name of the process.
	 */
	public function get_name(): string {
		return 'Order legacy data cleanup';
	}

	/**
	 * A user friendly description for this process.
	 *
	 * @return string Description.
	 */
	public function get_description(): string {
		return 'Cleans up order data from legacy tables.';
	}

	/**
	 * Get total number of pending records that require update.
	 *
	 * @return int Number of pending records.
	 */
	public function get_total_pending_count(): int {
		return $this->can_run() ? $this->legacy_handler->count_orders_for_cleanup() : 0;
	}

	/**
	 * Returns the batch with records that needs to be processed for a given size.
	 *
	 * @param int $size Size of the batch.
	 * @return array Batch of records.
	 */
	public function get_next_batch_to_process( int $size ): array {
		return $this->can_run()
			? array_map( 'absint', $this->legacy_handler->get_orders_for_cleanup( array(), $size ) )
			: array();
	}

	/**
	 * Process data for current batch.
	 *
	 * @param array $batch Batch details.
	 */
	public function process_batch( array $batch ): void {
		// This is a destructive operation, so check if we need to bail out just in case.
		if ( ! $this->can_run() ) {
			$this->toggle_flag( false );
			return;
		}

		$batch_failed = true;

		foreach ( $batch as $order_id ) {
			try {
				$this->legacy_handler->cleanup_post_data( absint( $order_id ) );
				$batch_failed = false;
			} catch ( \Exception $e ) {
				$this->error_logger->error(
					sprintf(
						// translators: %1$d is an order ID, %2$s is an error message.
						__( 'Order %1$d legacy data could not be cleaned up during batch process. Error: %2$s', 'woocommerce' ),
						$order_id,
						$e->getMessage()
					)
				);
			}
		}

		if ( $batch_failed ) {
			$this->error_logger->error( __( 'Order legacy cleanup failed for an entire batch of orders. Aborting cleanup.', 'woocommerce' ) );
		}

		if ( ! $this->orders_pending() || $batch_failed ) {
			$this->toggle_flag( false );
		}
	}

	/**
	 * Default batch size to use.
	 *
	 * @return int Default batch size.
	 */
	public function get_default_batch_size(): int {
		return self::BATCH_SIZE;
	}

	/**
	 * Determine whether the cleanup process can be initiated. Legacy data cleanup requires HPOS to be authoritative and
	 * compatibility mode to be disabled.
	 *
	 * @return boolean TRUE if the cleanup process can be enabled, FALSE otherwise.
	 */
	public function can_run() {
		return $this->data_synchronizer->custom_orders_table_is_authoritative() && ! $this->data_synchronizer->data_sync_is_enabled() && ! $this->batch_processing->is_enqueued( get_class( $this->data_synchronizer ) );
	}

	/**
	 * Whether the user has initiated the cleanup process.
	 *
	 * @return boolean TRUE if the user has initiated the cleanup process, FALSE otherwise.
	 */
	public function is_flag_set() {
		return $this->batch_processing->is_enqueued( self::class );
	}

	/**
	 * Sets the flag that indicates that the cleanup process should be initiated.
	 *
	 * @param boolean $enabled TRUE if the process should be initiated, FALSE if it should be canceled.
	 * @return boolean Whether the legacy data cleanup was initiated or not.
	 */
	public function toggle_flag( bool $enabled ): bool {
		if ( $enabled && $this->can_run() ) {
			$this->batch_processing->enqueue_processor( self::class );
			return true;
		} else {
			$this->batch_processing->remove_processor( self::class );
			return $enabled ? false : true;
		}
	}

	/**
	 * Returns an array in format required by 'woocommerce_debug_tools' to register the cleanup tool in WC.
	 *
	 * @return array Tools entries to register with WC.
	 */
	public function get_tools_entries() {
		$orders_for_cleanup_exist = ! empty( $this->legacy_handler->get_orders_for_cleanup( array(), 1 ) );
		$entry_id                 = $this->is_flag_set() ? 'hpos_legacy_cleanup_cancel' : 'hpos_legacy_cleanup';
		$entry                    = array(
			'name'             => __( 'Clean up order data from legacy tables', 'woocommerce' ),
			'desc'             => __( 'This tool will clear the data from legacy order tables in WooCommerce.', 'woocommerce' ),
			'requires_refresh' => true,
			'button'           => __( 'Clear data', 'woocommerce' ),
			'disabled'         => ! ( $this->can_run() && ( $orders_for_cleanup_exist || $this->is_flag_set() ) ),
		);

		if ( ! $this->can_run() ) {
			$entry['desc'] .= '<br />';
			$entry['desc'] .= sprintf(
				'<strong class="red">%1$s</strong> %2$s',
				__( 'Note:', 'woocommerce' ),
				__( 'Only available when HPOS is authoritative and compatibility mode is disabled.', 'woocommerce' )
			);
		} else {
			if ( $this->is_flag_set() ) {
				$entry['status_text'] = sprintf(
					'%1$s %2$s',
					'<span class="dashicons dashicons-update spin"></span>',
					__( 'Clearing data...', 'woocommerce' )
				);
				$entry['button']      = __( 'Cancel', 'woocommerce' );
				$entry['callback']    = function() {
					$this->toggle_flag( false );
					return __( 'Order legacy data cleanup has been canceled.', 'woocommerce' );
				};
			} elseif ( ! $orders_for_cleanup_exist ) {
				$entry['button'] = __( 'No orders in need of cleanup', 'woocommerce' );
			} else {
				$entry['callback'] = function() {
					$this->toggle_flag( true );
					return __( 'Order legacy data cleanup process has been started.', 'woocommerce' );
				};
			}
		}

		return array( $entry_id => $entry );
	}

	/**
	 * Checks whether there are any orders in need of cleanup and cleanup can run.
	 *
	 * @return bool TRUE if there are orders in need of cleanup, FALSE otherwise.
	 */
	private function orders_pending() {
		return ! empty( $this->get_next_batch_to_process( 1 ) );
	}

}
