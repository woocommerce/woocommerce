<?php
/**
 * Controller to manage updates of `BatchProcessor` type. Trigger the `enqueue_processor` method to start a update, and it will manage rest of the things.
 */

namespace Automattic\WooCommerce\Internal\Updates;

use Automattic\WooCommerce\DataBase\BatchProcessor;

/**
 * Class BatchProcessingController
 *
 * @package Automattic\WooCommerce\Internal\Updates.
 */
class BatchProcessingController {

	const PENDING_PROCESS_OPTION_NAME = 'wc_pending_action_updates';
	const CONTROLLER_CRON_NAME = 'wc_schedule_pending_updates';
	const SINGLE_PROCESS_MIGRATION_ACTION = 'wc_run_action_update';
	const ACTION_GROUP = 'wc_updates';

	/**
	 * BatchProcessingController constructor.
	 *
	 * Schedules necessary actions to process batches.
	 */
	public function __construct() {
		add_action( self::CONTROLLER_CRON_NAME, array( $this, 'schedule_processes' ) );
		add_action( self::SINGLE_PROCESS_MIGRATION_ACTION, array( $this, 'process_single_update' ), 10, 2 );
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
	 * @param string $update_name Fully qualified class name of the updater. Must be child class `BatchProcessor`.
	 */
	public function process_single_update( string $update_name ) {
		$updater = $this->get_processor_instance( $update_name );
		$still_pending = $updater->process_batch();
		if ( $still_pending ) {
			$this->schedule_next_batch( $update_name );
		} else {
			$this->mark_pending_process_complete( $update_name );
			$updater->mark_process_complete();
		}
	}

	/**
	 * Scheduler next update batch.
	 *
	 * @param string $update_name Fully qualified class name of the updator.
	 *
	 * @return int Action ID.
	 */
	private function schedule_next_batch( string $update_name ) : int {
		return as_schedule_single_action( time(), self::SINGLE_PROCESS_MIGRATION_ACTION, array( $update_name ) );
	}

	/**
	 * Whether the given updater is already scheduled.
	 * Differs from `as_has_scheduled_action` in that this excludes actions in progress.
	 *
	 * @param string $update_name Fully qualified class name of the updator.
	 *
	 * @return bool Whether it's already scheduled.
	 */
	public function already_scheduled( string $update_name ) {
		return as_has_scheduled_action( self::SINGLE_PROCESS_MIGRATION_ACTION, array( $update_name ) );
	}

	/**
	 * Get instance of updater for given class name,
	 *
	 * @param string $updater_class_name Class name of updator.
	 *
	 * @return BatchProcessor Updator instance.
	 */
	private function get_processor_instance( string $updater_class_name ) : BatchProcessor {
		$processor = wc_get_container()->get( $updater_class_name );

		/**
		 * Filters the instance of processor for current class name.
		 *
		 * @since 6.7.0.
		 */
		return apply_filters( 'woocommerce_get_batch_processor', $processor, $updater_class_name );
	}

	/**
	 * Helper method to get list of all pending updates.
	 *
	 * @return array List of pending updates.
	 */
	public function get_pending() : array {
		return get_option( self::PENDING_PROCESS_OPTION_NAME, array() );
	}

	/**
	 * Mark a pending update complete.
	 *
	 * @param string $updater Name of updater class which will be marked compete.
	 */
	protected function mark_pending_process_complete( string $updater ) {
		$pending_processes = $this->get_pending();
		if ( in_array( $updater, $pending_processes ) ) {
			$pending_processes = array_diff( $pending_processes, array( $updater ) );
			$this->set_pending_processes( $pending_processes );
		}
	}

	/**
	 * Helper method set pending processes.
	 *
	 * @param array $processes List of pending processes.
	 */
	private function set_pending_processes( array $processes ) {
		update_option( self::PENDING_PROCESS_OPTION_NAME, $processes, false );
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
	 * @return bool Whether the update is in progress.
	 */
	public function is_batch_process_pending( string $process_id ) : bool {
		return in_array( $process_id, $this->get_pending() );
	}

	/**
	 * Stops all pending updates, and clears current memory state.
	 */
	public function force_clear_all_processes() {
		as_unschedule_all_actions( self::SINGLE_PROCESS_MIGRATION_ACTION );
		as_unschedule_action( self::CONTROLLER_CRON_NAME );
		update_option( self::PENDING_PROCESS_OPTION_NAME, array(), false );
	}

}
