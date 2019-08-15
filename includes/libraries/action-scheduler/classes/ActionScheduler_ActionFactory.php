<?php

/**
 * Class ActionScheduler_ActionFactory
 */
class ActionScheduler_ActionFactory {

	/**
	 * @param string $status The action's status in the data store
	 * @param string $hook The hook to trigger when this action runs
	 * @param array $args Args to pass to callbacks when the hook is triggered
	 * @param ActionScheduler_Schedule $schedule The action's schedule
	 * @param string $group A group to put the action in
	 *
	 * @return ActionScheduler_Action An instance of the stored action
	 */
	public function get_stored_action( $status, $hook, array $args = array(), ActionScheduler_Schedule $schedule = null, $group = '' ) {

		switch ( $status ) {
			case ActionScheduler_Store::STATUS_PENDING :
				$action_class = 'ActionScheduler_Action';
				break;
			case ActionScheduler_Store::STATUS_CANCELED :
				$action_class = 'ActionScheduler_CanceledAction';
				break;
			default :
				$action_class = 'ActionScheduler_FinishedAction';
				break;
		}

		$action_class = apply_filters( 'action_scheduler_stored_action_class', $action_class, $status, $hook, $args, $schedule, $group );

		$action = new $action_class( $hook, $args, $schedule, $group );

		/**
		 * Allow 3rd party code to change the instantiated action for a given hook, args, schedule and group.
		 *
		 * @param ActionScheduler_Action $action The instantiated action.
		 * @param string $hook The instantiated action's hook.
		 * @param array $args The instantiated action's args.
		 * @param ActionScheduler_Schedule $schedule The instantiated action's schedule.
		 * @param string $group The instantiated action's group.
		 */
		return apply_filters( 'action_scheduler_stored_action_instance', $action, $hook, $args, $schedule, $group );
	}

	/**
	 * @param string $hook The hook to trigger when this action runs
	 * @param array $args Args to pass when the hook is triggered
	 * @param int $when Unix timestamp when the action will run
	 * @param string $group A group to put the action in
	 *
	 * @return string The ID of the stored action
	 */
	public function single( $hook, $args = array(), $when = null, $group = '' ) {
		$date = as_get_datetime_object( $when );
		$schedule = new ActionScheduler_SimpleSchedule( $date );
		$action = new ActionScheduler_Action( $hook, $args, $schedule, $group );
		return $this->store( $action );
	}

	/**
	 * @param string $hook The hook to trigger when this action runs
	 * @param array $args Args to pass when the hook is triggered
	 * @param int $first Unix timestamp for the first run
	 * @param int $interval Seconds between runs
	 * @param string $group A group to put the action in
	 *
	 * @return string The ID of the stored action
	 */
	public function recurring( $hook, $args = array(), $first = null, $interval = null, $group = '' ) {
		if ( empty($interval) ) {
			return $this->single( $hook, $args, $first, $group );
		}
		$date = as_get_datetime_object( $first );
		$schedule = new ActionScheduler_IntervalSchedule( $date, $interval );
		$action = new ActionScheduler_Action( $hook, $args, $schedule, $group );
		return $this->store( $action );
	}


	/**
	 * @param string $hook The hook to trigger when this action runs
	 * @param array $args Args to pass when the hook is triggered
	 * @param int $first Unix timestamp for the first run
	 * @param int $schedule A cron definition string
	 * @param string $group A group to put the action in
	 *
	 * @return string The ID of the stored action
	 */
	public function cron( $hook, $args = array(), $first = null, $schedule = null, $group = '' ) {
		if ( empty($schedule) ) {
			return $this->single( $hook, $args, $first, $group );
		}
		$date = as_get_datetime_object( $first );
		$cron = CronExpression::factory( $schedule );
		$schedule = new ActionScheduler_CronSchedule( $date, $cron );
		$action = new ActionScheduler_Action( $hook, $args, $schedule, $group );
		return $this->store( $action );
	}

	/**
	 * @param ActionScheduler_Action $action
	 *
	 * @return string The ID of the stored action
	 */
	protected function store( ActionScheduler_Action $action ) {
		$store = ActionScheduler_Store::instance();
		return $store->save_action( $action );
	}
}
