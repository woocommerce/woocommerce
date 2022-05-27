<?php
/**
 * Controller to manage updates of `WCActionUpdater` type. Trigger the `start_update` method to start a update, and it will manage rest of the things.
 */

namespace Automattic\WooCommerce\Internal\Updates;

use Automattic\WooCommerce\DataBase\WCActionUpdater;

/**
 * Class WCActionUpdateController
 *
 * @package Automattic\WooCommerce\Internal\Updates.
 */
class WCActionUpdateController {

	const PENDING_UPDATES_OPTION = 'wc_pending_action_updates';
	const UPDATE_CRON_NAME = 'wc_schedule_pending_updates';
	const UPDATE_SINGLE_MIGRATION_ACTION = 'wc_run_action_update';
	const ACTION_GROUP = 'wc_updates';

	/**
	 * WCActionUpdateController constructor.
	 * Schedules necessary actions.
	 */
	public function __construct() {
		add_action( self::UPDATE_CRON_NAME, array( $this, 'schedule_updates' ) );
		add_action( self::UPDATE_SINGLE_MIGRATION_ACTION, array( $this, 'process_single_update' ), 10, 2 );
	}

	/**
	 * Starts an update.
	 *
	 * @param string $updater_class_name Fully qualified class name of an updater, must be child class of `WCActionUpdater`.
	 */
	public function start_update( string $updater_class_name ) {
		$pending_updates = $this->get_pending_updates();
		if ( ! in_array( $updater_class_name, array_keys( $pending_updates ) ) ) {
			$pending_updates[] = $updater_class_name;
			$this->set_pending_updates( $pending_updates );
		}
		$this->schedule_init_cron();
	}

	/**
	 * Helper method to start update cron.
	 */
	private function schedule_init_cron() {
		as_schedule_single_action(
			$this->get_start_timestamp_for_init_action(),
			self::UPDATE_CRON_NAME,
			$this->get_args_for_init_cron(),
			self::ACTION_GROUP
		);
	}

	/**
	 * Schedules update for all updaters that may be stuck. This method will be called in a action.
	 */
	public function schedule_updates() {
		$pending_updates = $this->get_pending_updates();
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
	 * @param string $update_name Fully qualified class name of the updater. Must be child class `WCActionUpdater`.
	 */
	public function process_single_update( string $update_name ) {
		$updater = $this->get_updater_instance( $update_name );
		$still_pending = $updater->process_batch();
		if ( $still_pending ) {
			$this->schedule_next_batch( $update_name );
		} else {
			$this->mark_pending_update_complete( $update_name );
			$updater->mark_update_complete();
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
		return as_schedule_single_action( time(), self::UPDATE_SINGLE_MIGRATION_ACTION, array( $update_name ) );
	}

	/**
	 * Whether the given updater is already scheduled.
	 * Differs from `as_has_scheduled_action` in that this excludes actions in progress.
	 *
	 * @param string $update_name Fully qualified class name of the updator.
	 *
	 * @return bool Whether it's already scheduled.
	 */
	private function already_scheduled( string $update_name ) {
		return as_has_scheduled_action( self::UPDATE_SINGLE_MIGRATION_ACTION, array( $update_name ) );
	}

	/**
	 * Get instance of updater for given class name,
	 *
	 * @param string $updater_class_name Class name of updator.
	 *
	 * @return WCActionUpdater Updator instance.
	 */
	private function get_updater_instance( string $updater_class_name ) : WCActionUpdater {
		return wc_get_container()->get( $updater_class_name );
	}

	/**
	 * Helper method to get list of all pending updates.
	 *
	 * @return array List of pending updates.
	 */
	public function get_pending_updates() : array {
		return get_option( self::PENDING_UPDATES_OPTION, array() );
	}

	/**
	 * Mark a pending update complete.
	 *
	 * @param string $updater Name of updater class which will be marked compete.
	 */
	protected function mark_pending_update_complete( string $updater ) {
		$pending_migrations = $this->get_pending_updates();
		if ( in_array( $updater, $pending_migrations ) ) {
			$pending_migrations = array_diff( $pending_migrations, array( $updater ) );
			$this->set_pending_updates( $pending_migrations );
		}
	}

	/**
	 * Helper method set pending updates.
	 *
	 * @param array $updates List of pending updates.
	 */
	private function set_pending_updates( array $updates ) {
		update_option( self::PENDING_UPDATES_OPTION, $updates, false );
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
	 * Check if a particular update is in progress.
	 *
	 * @param string $update_name Fully qualified class name of updator.
	 *
	 * @return bool Whether the update is in progress.
	 */
	public function is_update_in_progress( string $update_name ) : bool {
		return in_array( $update_name, $this->get_pending_updates() );
	}

}
