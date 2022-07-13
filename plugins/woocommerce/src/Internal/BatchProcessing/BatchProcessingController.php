<?php
/**
 * Controller to manage processing of `BatchProcessor` type. Trigger the `enqueue_processor` method to start a batch process, and it will manage rest of the things.
 */

namespace Automattic\WooCommerce\Internal\BatchProcessing;

/**
 * Class BatchProcessingController
 *
 * @package Automattic\WooCommerce\Internal\Updates.
 */
class BatchProcessingController {

	const PENDING_PROCESSES_OPTION_NAME = 'wc_pending_batch_processes';
	const CONTROLLER_CRON_NAME          = 'wc_schedule_pending_batch_processes';
	const SINGLE_BATCH_PROCESS_ACTION   = 'wc_run_batch_process';
	const ACTION_GROUP                  = 'wc_batch_processes';

	/**
	 * Instance of WC_Logger class.
	 *
	 * @var \WC_Logger_Interface
	 */
	private $logger;

	/**
	 * BatchProcessingController constructor.
	 *
	 * Schedules necessary actions to process batches.
	 */
	public function __construct() {
		add_action(
			self::CONTROLLER_CRON_NAME,
			function () {
				$this->schedule_processes();
			}
		);
		add_action(
			self::SINGLE_BATCH_PROCESS_ACTION,
			function ( $batch_process ) {
				$this->process_single_batch( $batch_process );
			},
			10,
			2
		);
		$this->logger = wc_get_logger();
	}

	/**
	 * Starts an update.
	 *
	 * @param string $processor_class_name Fully qualified class name of the processor, must be child class of `BatchProcessor`.
	 */
	public function enqueue_processor( string $processor_class_name ) {
		$pending_updates = $this->get_pending();
		if ( ! in_array( $processor_class_name, array_keys( $pending_updates ), true ) ) {
			$pending_updates[] = $processor_class_name;
			$this->set_pending_processes( $pending_updates );
		}
		$this->schedule_init_cron( false, true );
	}

	/**
	 * Helper method to start update cron.
	 *
	 * @param bool $with_delay Whether to delay the cron start. Send true when rescheduling, false when starting.
	 * @param bool $unique     Whether to make the cron unique.
	 */
	private function schedule_init_cron( bool $with_delay = false, bool $unique = false ) {
		$time = $with_delay ? time() + HOUR_IN_SECONDS : time();
		as_schedule_single_action(
			$time,
			self::CONTROLLER_CRON_NAME,
			array(),
			self::ACTION_GROUP,
			$unique
		);
	}

	/**
	 * Schedules update for all updaters that may be stuck. This method is called in CONTROLLER_CRON_NAME action.
	 */
	private function schedule_processes() {
		$pending_processes = $this->get_pending();
		if ( empty( $pending_processes ) ) {
			return;
		}
		foreach ( $pending_processes as $process_name ) {
			if ( ! $this->is_scheduled( $process_name ) ) {
				$this->schedule_next_batch( $process_name );
			}
		}
		$this->schedule_init_cron( true );
	}

	/**
	 * Process update for a scheduled updater.
	 *
	 * @param string $batch_process Fully qualified class name of the updater. Must be child class `BatchProcessor`.
	 *
	 * @throws \Exception If error occurred during batch processing.
	 */
	private function process_single_batch( string $batch_process ) {
		$batch_processor      = $this->get_processor_instance( $batch_process );
		$pending_count_before = $batch_processor->get_total_pending_count();
		$error                = $this->process_batch( $batch_processor );
		$pending_count_after  = $batch_processor->get_total_pending_count();
		if ( ( $error instanceof \Exception ) && $pending_count_before === $pending_count_after ) {
			// There is an error in processing, schedule with delay.
			$this->schedule_next_batch( $batch_process, true );
			// Throw the error, after a while, AS will stop scheduling this action.
			throw $error;
		}
		if ( $pending_count_after > 0 ) {
			$this->schedule_next_batch( $batch_process );
		} else {
			$this->mark_pending_process_complete( $batch_process );
		}
	}

	/**
	 * Process next batch for given instance of `BatchProcessor`.
	 *
	 * @param BatchProcessorInterface $batch_processor Batch processor instance.
	 *
	 * @return null|\Exception Exception if error occurred, null otherwise.
	 */
	private function process_batch( BatchProcessorInterface $batch_processor ) {
		$details    = $this->get_process_details( $batch_processor );
		$time_start = microtime( true );
		$batch      = $batch_processor->get_next_batch_to_process( $details['current_batch_size'] );
		if ( empty( $batch ) ) {
			return null;
		}
		try {
			$batch_processor->process_batch( $batch );
			$time_taken = microtime( true ) - $time_start;
			$this->update_progress_status( $batch_processor, $batch, $time_taken );
		} catch ( \Exception $exception ) {
			$this->log_error( $exception, $batch_processor, $batch );
			$this->update_progress_status( $batch_processor, $batch, $time_taken, $exception );
			return $exception;
		}
		return null;
	}

	/**
	 * Get status for this update.
	 *
	 * @param BatchProcessorInterface $batch_processor Batch processor instance.
	 *
	 * @return mixed Update status.
	 */
	private function get_process_details( BatchProcessorInterface $batch_processor ) {
		return get_option(
			$this->get_process_option_name( $batch_processor ),
			array(
				'total_time_spent'   => 0,
				'current_batch_size' => $batch_processor->get_default_batch_size(),
				'last_error'         => null,
			)
		);
	}

	/**
	 * Name of the option where we will be saving state of this process.
	 *
	 * @param BatchProcessorInterface $processor Batch processor instance.
	 *
	 * @return string
	 */
	private function get_process_option_name( BatchProcessorInterface $processor ) {
		$class_name = get_class( $processor );
		$class_md5  = md5( $class_name );
		// truncate the class name so we know that it will fit in the option name column along with md5 hash and prefix.
		$class_name = substr( $class_name, 0, 140 );
		return 'wc_batch_' . $class_name . '_' . $class_md5;
	}

	/**
	 * Update the progress status after a batch has completed processing.
	 *
	 * @param BatchProcessorInterface $batch_processor Batch processor instance.
	 * @param array                   $batch Batch that finished processing.
	 * @param float                   $time_taken Time take by the batch to complete processing.
	 * @param \Exception|null         $last_error Exception object in processing the batch, if there was one.
	 */
	private function update_progress_status( BatchProcessorInterface $batch_processor, array $batch, float $time_taken, \Exception $last_error = null ) {
		$current_status                      = $this->get_process_details( $batch_processor );
		$current_status['total_time_spent'] += $time_taken;
		$current_status['last_error']        = null !== $last_error ? $last_error->getMessage() : null;
		update_option( $this->get_process_option_name( $batch_processor ), $current_status, false );
	}

	/**
	 * Scheduler next update batch.
	 *
	 * @param string $process_name Fully qualified class name of the processor.
	 * @param bool   $with_delay   Whether to delay the next update.
	 *
	 * @return int Action ID.
	 */
	private function schedule_next_batch( string $process_name, bool $with_delay = false ) : int {
		$time = $with_delay ? time() + MINUTE_IN_SECONDS : time();
		return as_schedule_single_action( $time, self::SINGLE_BATCH_PROCESS_ACTION, array( $process_name ) );
	}

	/**
	 * Whether the given updater is already scheduled.
	 * Differs from `as_has_scheduled_action` in that this excludes actions in progress.
	 *
	 * @param string $process_name Fully qualified class name of the batch processor.
	 *
	 * @return bool Whether it's already scheduled.
	 */
	public function is_scheduled( string $process_name ) {
		return as_has_scheduled_action( self::SINGLE_BATCH_PROCESS_ACTION, array( $process_name ) );
	}

	/**
	 * Get instance of updater for given class name,
	 *
	 * @param string $processor_class_name Class name of batch processor.
	 *
	 * @return BatchProcessorInterface Instance of batch processor.
	 * @throws \Exception If processor class doesn't exist.
	 */
	private function get_processor_instance( string $processor_class_name ) : BatchProcessorInterface {
		$processor = wc_get_container()->get( $processor_class_name );

		/**
		 * Filters the instance of processor for current class name.
		 *
		 * @since 6.8.0.
		 */
		$processor = apply_filters( 'woocommerce_get_batch_processor', $processor, $processor_class_name );
		if ( ! isset( $processor ) && class_exists( $processor_class_name ) ) {
			// This is a fallback for when the batch processor is not registered in the container.
			$processor = new $processor_class_name();
		}
		if ( ! is_a( $processor, BatchProcessorInterface::class ) ) {
			throw new \Exception( "Unable to initialize batch processor instance for $processor_class_name" );
		}
		return $processor;
	}

	/**
	 * Helper method to get list of all pending processes.
	 *
	 * @return array List (of string) of pending processes classnames.
	 */
	public function get_pending() : array {
		return get_option( self::PENDING_PROCESSES_OPTION_NAME, array() );
	}

	/**
	 * Mark a pending process complete.
	 *
	 * @param string $processor Name of processor class which will be marked compete.
	 */
	private function mark_pending_process_complete( string $processor ) {
		$pending_processes = $this->get_pending();
		if ( in_array( $processor, $pending_processes, true ) ) {
			$pending_processes = array_diff( $pending_processes, array( $processor ) );
			$this->set_pending_processes( $pending_processes );
		}
	}

	/**
	 * Helper method set pending processes.
	 *
	 * @param array $processes List of classnames of all pending processes.
	 */
	private function set_pending_processes( array $processes ) {
		update_option( self::PENDING_PROCESSES_OPTION_NAME, $processes, false );
	}

	/**
	 * Check if a particular process is pending.
	 *
	 * @param string $processor_name Fully qualified class name of process.
	 *
	 * @return bool Whether the process is in progress.
	 */
	public function is_enqueued( string $processor_name ) : bool {
		return in_array( $processor_name, $this->get_pending(), true );
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
	 * @param \Exception              $error Exception object.
	 * @param BatchProcessorInterface $batch_processor Batch processor instance.
	 * @param array                   $batch Batch that finished processing.
	 */
	protected function log_error( \Exception $error, BatchProcessorInterface $batch_processor, array $batch ) : void {
		$batch_detail_string = '';
		// Log only first and last, as the entire batch may be too big.
		if ( count( $batch ) > 0 ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Logging is for debugging.
			$batch_detail_string = '\n' . print_r(
				array(
					'batch_start' => $batch[0],
					'batch_end'   => end( $batch ),
				),
				true
			);
		}
		$error_message = "Error processing batch for {$batch_processor->get_name()}: {$error->getMessage()}" . $batch_detail_string;
		/**
		 * Filters the error message for a batch processor.
		 *
		 * @since 6.8.0
		 */
		$error_message = apply_filters( 'wc_batch_processing_log_message', $error_message, $error, $batch_processor, $batch );
		$this->logger->error( $error_message, array( 'exception' => $error ) );
	}

}
