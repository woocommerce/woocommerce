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
	 * Class constructor.
	 */
	public function __construct() {
		self::add_filter( 'pre_update_option_' . self::OPTION_NAME, array( $this, 'pre_update_option' ), 999, 2 );
		self::add_action( 'add_option_' . self::OPTION_NAME, array( $this, 'process_added_option' ), 999, 2 );
		self::add_action( 'update_option_' . self::OPTION_NAME, array( $this, 'process_updated_option' ), 999, 2 );
		self::add_action( 'delete_option_' . self::OPTION_NAME, array( $this, 'process_deleted_option' ), 999 );
		self::add_action( 'shutdown', array( $this, 'maybe_reset_state' ) );
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
		return $this->should_run() ? $this->legacy_handler->count_orders_for_cleanup() : 0;
	}

	/**
	 * Returns the batch with records that needs to be processed for a given size.
	 *
	 * @param int $size Size of the batch.
	 * @return array Batch of records.
	 */
	public function get_next_batch_to_process( int $size ): array {
		return $this->should_run()
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
		if ( ! $this->should_run() ) {
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
	 * Checks whether the cleanup process should run. That is, it must be activated and {@see can_run()} must return TRUE.
	 *
	 * @return boolean TRUE if the cleanup process should be run, FALSE otherwise.
	 */
	public function should_run() {
		return $this->can_run() && $this->is_flag_set();
	}

	/**
	 * Whether the user has initiated the cleanup process.
	 *
	 * @return boolean TRUE if the user has initiated the cleanup process, FALSE otherwise.
	 */
	public function is_flag_set() {
		return 'yes' === get_option( self::OPTION_NAME, 'no' );
	}

	/**
	 * Sets the flag that indicates that the cleanup process should be initiated.
	 *
	 * @param boolean $enabled TRUE if the process should be initiated, FALSE if it should be canceled.
	 */
	public function toggle_flag( bool $enabled ) {
		if ( $enabled ) {
			update_option( self::OPTION_NAME, wc_bool_to_string( $enabled ) );
		} else {
			delete_option( self::OPTION_NAME );
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
	 * Hooked onto 'add_option' to enqueue the batch processor (if needed).
	 *
	 * @param string $option Name of the option to add.
	 * @param mixed  $value  Value of the option.
	 */
	private function process_added_option( string $option, $value ) {
		$this->process_updated_option( false, $value );
	}

	/**
	 * Hooked onto 'delete_option' to remove the batch processor.
	 */
	private function process_deleted_option() {
		$this->process_updated_option( false, false );
	}

	/**
	 * Hooked onto 'update_option' to enqueue the batch processor as needed.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 */
	private function process_updated_option( $old_value, $new_value ) {
		$enable = wc_string_to_bool( $new_value );

		if ( $enable ) {
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
		return $this->can_run() ? $new_value : 'no';
	}

	/**
	 * Checks whether there are any orders in need of cleanup and cleanup can run.
	 *
	 * @return bool TRUE if there are orders in need of cleanup, FALSE otherwise.
	 */
	private function orders_pending() {
		return ! empty( $this->get_next_batch_to_process( 1 ) );
	}

	/**
	 * Hooked onto 'shutdown' to clean up or set things straight in case of failures (timeouts, etc).
	 */
	private function maybe_reset_state() {
		$is_enqueued = $this->batch_processing->is_enqueued( self::class );
		$is_flag_set = $this->is_flag_set();

		if ( $is_enqueued xor $is_flag_set ) {
			$this->toggle_flag( false );
			$this->batch_processing->remove_processor( self::class );
		}
	}

}
