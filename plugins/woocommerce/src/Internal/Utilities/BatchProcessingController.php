<?php
/**
 * Controller to manage processing of `BatchProcessor` type. Trigger the `enqueue_processor` method to start a batch process, and it will manage rest of the things.
 */

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\BatchProcessor;

/**
 * Class BatchProcessingController
 *
 * @package Automattic\WooCommerce\Internal\Updates.
 */
class BatchProcessingController {

	const PENDING_PROCESSES_OPTION_NAME = 'wc_pending_batch_processes';
	const CONTROLLER_CRON_NAME = 'wc_schedule_pending_batch_processes';
	const SINGLE_BATCH_PROCESS_ACTION = 'wc_run_batch_process';
	const ACTION_GROUP = 'wc_batch_processes';

	/**
	 * BatchProcessingController constructor.
	 *
	 * Schedules necessary actions to process batches.
	 */
	public function __construct() {
		add_action( self::CONTROLLER_CRON_NAME, array( $this, 'schedule_processes' ) );
		add_action( self::SINGLE_BATCH_PROCESS_ACTION, array( $this, 'process_single_batch' ), 10, 2 );
	}

	/**
	 * Starts an update.
	 *
	 * @param string $updater_class_name Fully qualified class name of an updater, must be child class of `BatchProcessor`.
	 */
	public function enqueue_processor( string $updater_class_name ) {
		$pending_updates = $this->get_pending();
		if ( ! in_array( $updater_class_name, array_keys( $pending_updates ) ) ) {
			$pending_updates[] = $updater_class_name;
			$this->set_pending_processes( $pending_updates );
		}
		$this->schedule_init_cron();
	}

	/**
	 * Helper method to start update cron.
	 */
	private function schedule_init_cron() {
		as_schedule_single_action(
			$this->get_start_timestamp_for_init_action(),
			self::CONTROLLER_CRON_NAME,
			$this->get_args_for_init_cron(),
			self::ACTION_GROUP
		);
	}

	/**
	 * Schedules update for all updaters that may be stuck. This method is called in CONTROLLER_CRON_NAME action.
	 */
	public function schedule_processes() {
		$pending_updates = $this->get_pending();
		if ( empty( $pending_updates ) ) {
			return;
		}
		foreach ( $pending_updates as $update_name ) {
			if ( ! $this->already_scheduled( $update_name ) ) {
				$this->schedule_next_batch( $update_name );
			}
		}
		$this->schedule_init_cron();
	}

	/**
	 * Process update for a scheduled updater.
	 *
	 * @param string $batch_process Fully qualified class name of the updater. Must be child class `BatchProcessor`.
	 */
	public function process_single_batch( string $batch_process ) {
		$batch_processor = $this->get_processor_instance( $batch_process );
		$this->process_batch( $batch_processor );
		if ( $batch_processor->get_total_pending_count() > 0 ) {
			$this->schedule_next_batch( $batch_process );
		} else {
			$this->mark_pending_process_complete( $batch_process );
			$batch_processor->mark_process_complete();
		}
	}

	/**
	 * Process next batch for given instance of `BatchProcessor`.
	 *
	 * @param BatchProcessor $batch_processor Batch processor instance.
	 *
	 * @return bool True if batch was processed, false if not.
	 */
	private function process_batch( BatchProcessor $batch_processor ) {
		$details    = $this->get_process_details( $batch_processor );
		$time_start = microtime( true );
		$batch      = $batch_processor->get_batch_data( $details['current_batch_size'], $details['last_processed'] );
		if ( empty( $batch ) ) {
			return false;
		}
		try {
			$batch_processor->process_for_batch( $batch );
			$time_taken = microtime( true ) - $time_start;
			$this->update_progress_status( $batch_processor, $batch, $time_taken );
			return true;
		} catch ( \Exception $exception ) {
			$this->log_error( $exception );
			$this->update_progress_status( $batch_processor, $batch, $time_taken, $exception );
			return false;
		}
	}

	/**
	 * Get status for this update.
	 *
	 * @param BatchProcessor $batch_processor Batch processor instance.
	 *
	 * @return mixed Update status.
	 */
	protected function get_process_details( BatchProcessor $batch_processor ) {
		return get_option(
			$this->get_process_option_name( $batch_processor ),
			array(
				'last_processed'     => null,
				'total_time_spent'   => 0,
				'current_batch_size' => $batch_processor->get_default_batch_size(),
				'last_error'         => null,
			)
		);
	}

	/**
	 * Name of the option where we will be saving state of this process.
	 *
	 * @param BatchProcessor $processor Batch processor instance.
	 *
	 * @return string
	 */
	private function get_process_option_name( BatchProcessor $processor ) {
		$class_name = get_class( $processor );
		$class_md5 = md5( $class_name );
		// truncate the class name so we know that it will fit in the option name column along with md5 hash and prefix.
		$class_name = substr( $class_name, 0, 140 );
		return 'wc_batch_' . $class_name . '_' . $class_md5;
	}

	/**
	 * Update the progress status after a batch has completed processing.
	 *
	 * @param BatchProcessor  $batch_processor Batch processor instance.
	 * @param array           $batch Batch that finished processing.
	 * @param float           $time_taken Time take by the batch to complete processing.
	 * @param \Exception|null $last_error Exception object in processing the batch, if there was one.
	 */
	protected function update_progress_status( BatchProcessor $batch_processor, array $batch, float $time_taken, \Exception $last_error = null ) {
		$current_status                      = $this->get_process_details( $batch_processor );
		$current_status['total_time_spent'] += $time_taken;
		$current_status['last_processed']    = null !== $last_error ? end( $batch ) : $current_status['last_processed'];
		$current_status['last_error']        = null !== $last_error ? $last_error->getMessage() : null;
		update_option( $this->get_process_option_name( $batch_processor ), $current_status, false );
	}

	/**
	 * Scheduler next update batch.
	 *
	 * @param string $process_name Fully qualified class name of the processor.
	 *
	 * @return int Action ID.
	 */
	private function schedule_next_batch( string $process_name ) : int {
		return as_schedule_single_action( time(), self::SINGLE_BATCH_PROCESS_ACTION, array( $process_name ) );
	}

	/**
	 * Whether the given updater is already scheduled.
	 * Differs from `as_has_scheduled_action` in that this excludes actions in progress.
	 *
	 * @param string $process_name Fully qualified class name of the batch processor.
	 *
	 * @return bool Whether it's already scheduled.
	 */
	public function already_scheduled( string $process_name ) {
		return as_has_scheduled_action( self::SINGLE_BATCH_PROCESS_ACTION, array( $process_name ) );
	}

	/**
	 * Get instance of updater for given class name,
	 *
	 * @param string $processor_class_name Class name of batch processor.
	 *
	 * @return BatchProcessor Instance of batch processor.
	 * @throws \Exception If processor class doesn't exist.
	 */
	private function get_processor_instance( string $processor_class_name ) : BatchProcessor {
		$processor = wc_get_container()->get( $processor_class_name );

		/**
		 * Filters the instance of processor for current class name.
		 *
		 * @since 6.7.0.
		 */
		$processor = apply_filters( 'woocommerce_get_batch_processor', $processor, $processor_class_name );
		if ( ! isset( $processor ) && class_exists( $processor_class_name ) ) {
			// This is a fallback for when the batch processor is not registered in the container.
			$processor = new $processor_class_name();
		}
		if ( ! is_a( $processor, BatchProcessor::class ) ) {
			throw new \Exception( 'Unable to initialize batch processor instance.' );
		}
		return $processor;
	}

	/**
	 * Helper method to get list of all pending processes.
	 *
	 * @return array List of pending processes.
	 */
	public function get_pending() : array {
		return get_option( self::PENDING_PROCESSES_OPTION_NAME, array() );
	}

	/**
	 * Mark a pending process complete.
	 *
	 * @param string $processor Name of processor class which will be marked compete.
	 */
	protected function mark_pending_process_complete( string $processor ) {
		$pending_processes = $this->get_pending();
		if ( in_array( $processor, $pending_processes ) ) {
			$pending_processes = array_diff( $pending_processes, array( $processor ) );
			$this->set_pending_processes( $pending_processes );
		}
	}

	/**
	 * Helper method set pending processes.
	 *
	 * @param array $processes List of pending processes.
	 */
	private function set_pending_processes( array $processes ) {
		update_option( self::PENDING_PROCESSES_OPTION_NAME, $processes, false );
	}

	/**
	 * Start timestamp for first recurring action.
	 *
	 * @return int time internval.
	 */
	private function get_start_timestamp_for_init_action() {
		return time() + MINUTE_IN_SECONDS;
	}

	/**
	 * Arguments for recurring action.
	 *
	 * @return array
	 */
	private function get_args_for_init_cron() {
		return array();
	}

	/**
	 * Check if a particular process is pending.
	 *
	 * @param string $process_id Fully qualified class name of process.
	 *
	 * @return bool Whether the process is in progress.
	 */
	public function is_batch_process_pending( string $process_id ) : bool {
		return in_array( $process_id, $this->get_pending() );
	}

	/**
	 * Stops all pending processes, and clears current memory state.
	 */
	public function force_clear_all_processes() {
		as_unschedule_all_actions( self::SINGLE_BATCH_PROCESS_ACTION );
		as_unschedule_action( self::CONTROLLER_CRON_NAME );
		update_option( self::PENDING_PROCESSES_OPTION_NAME, array(), false );
	}

	/**
	 * Log an error if happens during migration processing.
	 *
	 * @param \Exception $error Exception object.
	 */
	protected function log_error( \Exception $error ) : void {
		// TODO: Implement error logging.
	}


}
