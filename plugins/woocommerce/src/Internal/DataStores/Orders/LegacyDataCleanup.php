<?php
/**
 * LegacyDataCleanup class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles the background process in charge of cleaning up legacy data for orders when HPOS is authoritative.
 */
class LegacyDataCleanup implements BatchProcessorInterface {

	use AccessiblePrivateMethods;

	/**
	 * Option name for this feature.
	 */
	public const FEATURE_OPTION_NAME = 'woocommerce_hpos_legacy_data_cleanup_enabled';

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
	 * Class constructor.
	 */
	public function __construct() {
		self::add_filter( 'pre_update_option_' . self::FEATURE_OPTION_NAME, array( $this, 'pre_update_option' ), 999, 2 );
		self::add_action( 'update_option_' . self::FEATURE_OPTION_NAME, array( $this, 'process_updated_option' ), 999, 2 );
	}

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
		return $this->legacy_handler->count_orders_for_cleanup();
	}

	/**
	 * Returns the batch with records that needs to be processed for a given size.
	 *
	 * @param int $size Size of the batch.
	 * @return array Batch of records.
	 */
	public function get_next_batch_to_process( int $size ): array {
		return array_map(
			'absint',
			$this->legacy_handler->get_orders_for_cleanup( array(), $size )
		);
	}

	/**
	 * Process data for current batch.
	 *
	 * @param array $batch Batch details.
	 */
	public function process_batch( array $batch ): void {
		// This is a destructive operation, so check if we need to bail out just in case.
		if ( ! $this->is_enabled() ) {
			return;
		}

		foreach ( $batch as $order_id ) {
			$this->legacy_handler->cleanup_post_data( absint( $order_id ) );
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
	 * Determine whether the cleanup process can be enabled. Legacy data cleanup requires HPOS to be authoritative and
	 * compatibility mode to be disabled.
	 *
	 * @return boolean TRUE if the cleanup process can be enabled. FALSE otherwise.
	 */
	public function can_be_enabled() {
		return $this->data_synchronizer->custom_orders_table_is_authoritative() && ! $this->data_synchronizer->data_sync_is_enabled();
	}

	/**
	 * Is the legacy data cleanup enabled?
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return $this->can_be_enabled() && 'yes' === get_option( self::FEATURE_OPTION_NAME );
	}

	/**
	 * Hooked onto 'updated_option' to enqueue the batch processor as needed.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 */
	private function process_updated_option( $old_value, $new_value ) {
		$enable = wc_string_to_bool( $new_value );

		if ( $enable ) {
			$this->batch_processing->remove_processor( $this->data_synchronizer::class );
			$this->batch_processing->enqueue_processor( self::class );
		} else {
			$this->batch_processing->remove_processor( self::class );
		}
	}

	/**
	 * Hooked onto 'pre_update_option' to prevent enabling of the cleanup process when conditions aren't met.
	 *
	 * @param mixed $new_value New option value.
	 * @param mixed $old_value Previous option value.
	 */
	private function pre_update_option( $new_value, $old_value ) {
		return $this->can_be_enabled() ? $new_value : 'no';
	}

}
