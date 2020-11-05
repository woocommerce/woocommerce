<?php

/**
 * General API functions for scheduling actions
 */

/**
 * Enqueue an action to run one time, as soon as possible
 *
 * @param string $hook The hook to trigger.
 * @param array  $args Arguments to pass when the hook triggers.
 * @param string $group The group to assign this job to.
 * @return int The action ID.
 */
function as_enqueue_async_action( $hook, $args = array(), $group = '' ) {
	if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
		return 0;
	}
	return ActionScheduler::factory()->async( $hook, $args, $group );
}

/**
 * Schedule an action to run one time
 *
 * @param int $timestamp When the job will run.
 * @param string $hook The hook to trigger.
 * @param array $args Arguments to pass when the hook triggers.
 * @param string $group The group to assign this job to.
 *
 * @return int The action ID.
 */
function as_schedule_single_action( $timestamp, $hook, $args = array(), $group = '' ) {
	if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
		return 0;
	}
	return ActionScheduler::factory()->single( $hook, $args, $timestamp, $group );
}

/**
 * Schedule a recurring action
 *
 * @param int $timestamp When the first instance of the job will run.
 * @param int $interval_in_seconds How long to wait between runs.
 * @param string $hook The hook to trigger.
 * @param array $args Arguments to pass when the hook triggers.
 * @param string $group The group to assign this job to.
 *
 * @return int The action ID.
 */
function as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '' ) {
	if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
		return 0;
	}
	return ActionScheduler::factory()->recurring( $hook, $args, $timestamp, $interval_in_seconds, $group );
}

/**
 * Schedule an action that recurs on a cron-like schedule.
 *
 * @param int $base_timestamp The first instance of the action will be scheduled
 *        to run at a time calculated after this timestamp matching the cron
 *        expression. This can be used to delay the first instance of the action.
 * @param string $schedule A cron-link schedule string
 * @see http://en.wikipedia.org/wiki/Cron
 *   *    *    *    *    *    *
 *   ┬    ┬    ┬    ┬    ┬    ┬
 *   |    |    |    |    |    |
 *   |    |    |    |    |    + year [optional]
 *   |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
 *   |    |    |    +---------- month (1 - 12)
 *   |    |    +--------------- day of month (1 - 31)
 *   |    +-------------------- hour (0 - 23)
 *   +------------------------- min (0 - 59)
 * @param string $hook The hook to trigger.
 * @param array $args Arguments to pass when the hook triggers.
 * @param string $group The group to assign this job to.
 *
 * @return int The action ID.
 */
function as_schedule_cron_action( $timestamp, $schedule, $hook, $args = array(), $group = '' ) {
	if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
		return 0;
	}
	return ActionScheduler::factory()->cron( $hook, $args, $timestamp, $schedule, $group );
}

/**
 * Cancel the next occurrence of a scheduled action.
 *
 * While only the next instance of a recurring or cron action is unscheduled by this method, that will also prevent
 * all future instances of that recurring or cron action from being run. Recurring and cron actions are scheduled in
 * a sequence instead of all being scheduled at once. Each successive occurrence of a recurring action is scheduled
 * only after the former action is run. If the next instance is never run, because it's unscheduled by this function,
 * then the following instance will never be scheduled (or exist), which is effectively the same as being unscheduled
 * by this method also.
 *
 * @param string $hook The hook that the job will trigger.
 * @param array $args Args that would have been passed to the job.
 * @param string $group The group the job is assigned to.
 *
 * @return string|null The scheduled action ID if a scheduled action was found, or null if no matching action found.
 */
function as_unschedule_action( $hook, $args = array(), $group = '' ) {
	if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
		return 0;
	}
	$params = array();
	if ( is_array($args) ) {
		$params['args'] = $args;
	}
	if ( !empty($group) ) {
		$params['group'] = $group;
	}
	$job_id = ActionScheduler::store()->find_action( $hook, $params );

	if ( ! empty( $job_id ) ) {
		ActionScheduler::store()->cancel_action( $job_id );
	}

	return $job_id;
}

/**
 * Cancel all occurrences of a scheduled action.
 *
 * @param string $hook The hook that the job will trigger.
 * @param array $args Args that would have been passed to the job.
 * @param string $group The group the job is assigned to.
 */
function as_unschedule_all_actions( $hook, $args = array(), $group = '' ) {
	if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
		return;
	}
	if ( empty( $args ) ) {
		if ( ! empty( $hook ) && empty( $group ) ) {
			ActionScheduler_Store::instance()->cancel_actions_by_hook( $hook );
			return;
		}
		if ( ! empty( $group ) && empty( $hook ) ) {
			ActionScheduler_Store::instance()->cancel_actions_by_group( $group );
			return;
		}
	}
	do {
		$unscheduled_action = as_unschedule_action( $hook, $args, $group );
	} while ( ! empty( $unscheduled_action ) );
}

/**
 * Check if there is an existing action in the queue with a given hook, args and group combination.
 *
 * An action in the queue could be pending, in-progress or async. If the is pending for a time in
 * future, its scheduled date will be returned as a timestamp. If it is currently being run, or an
 * async action sitting in the queue waiting to be processed, in which case boolean true will be
 * returned. Or there may be no async, in-progress or pending action for this hook, in which case,
 * boolean false will be the return value.
 *
 * @param string $hook
 * @param array $args
 * @param string $group
 *
 * @return int|bool The timestamp for the next occurrence of a pending scheduled action, true for an async or in-progress action or false if there is no matching action.
 */
function as_next_scheduled_action( $hook, $args = NULL, $group = '' ) {
	if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
		return false;
	}
	$params = array();
	if ( is_array($args) ) {
		$params['args'] = $args;
	}
	if ( !empty($group) ) {
		$params['group'] = $group;
	}

	$params['status'] = ActionScheduler_Store::STATUS_RUNNING;
	$job_id = ActionScheduler::store()->find_action( $hook, $params );
	if ( ! empty( $job_id ) ) {
		return true;
	}

	$params['status'] = ActionScheduler_Store::STATUS_PENDING;
	$job_id = ActionScheduler::store()->find_action( $hook, $params );
	if ( empty($job_id) ) {
		return false;
	}
	$job = ActionScheduler::store()->fetch_action( $job_id );
	$scheduled_date = $job->get_schedule()->get_date();
	if ( $scheduled_date ) {
		return (int) $scheduled_date->format( 'U' );
	} elseif ( NULL === $scheduled_date ) { // pending async action with NullSchedule
		return true;
	}
	return false;
}

/**
 * Find scheduled actions
 *
 * @param array $args Possible arguments, with their default values:
 *        'hook' => '' - the name of the action that will be triggered
 *        'args' => NULL - the args array that will be passed with the action
 *        'date' => NULL - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
 *        'date_compare' => '<=' - operator for testing "date". accepted values are '!=', '>', '>=', '<', '<=', '='
 *        'modified' => NULL - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime(). Used in UTC timezone.
 *        'modified_compare' => '<=' - operator for testing "modified". accepted values are '!=', '>', '>=', '<', '<=', '='
 *        'group' => '' - the group the action belongs to
 *        'status' => '' - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING
 *        'claimed' => NULL - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID
 *        'per_page' => 5 - Number of results to return
 *        'offset' => 0
 *        'orderby' => 'date' - accepted values are 'hook', 'group', 'modified', or 'date'
 *        'order' => 'ASC'
 *
 * @param string $return_format OBJECT, ARRAY_A, or ids.
 *
 * @return array
 */
function as_get_scheduled_actions( $args = array(), $return_format = OBJECT ) {
	if ( ! ActionScheduler::is_initialized( __FUNCTION__ ) ) {
		return array();
	}
	$store = ActionScheduler::store();
	foreach ( array('date', 'modified') as $key ) {
		if ( isset($args[$key]) ) {
			$args[$key] = as_get_datetime_object($args[$key]);
		}
	}
	$ids = $store->query_actions( $args );

	if ( $return_format == 'ids' || $return_format == 'int' ) {
		return $ids;
	}

	$actions = array();
	foreach ( $ids as $action_id ) {
		$actions[$action_id] = $store->fetch_action( $action_id );
	}

	if ( $return_format == ARRAY_A ) {
		foreach ( $actions as $action_id => $action_object ) {
			$actions[$action_id] = get_object_vars($action_object);
		}
	}

	return $actions;
}

/**
 * Helper function to create an instance of DateTime based on a given
 * string and timezone. By default, will return the current date/time
 * in the UTC timezone.
 *
 * Needed because new DateTime() called without an explicit timezone
 * will create a date/time in PHP's timezone, but we need to have
 * assurance that a date/time uses the right timezone (which we almost
 * always want to be UTC), which means we need to always include the
 * timezone when instantiating datetimes rather than leaving it up to
 * the PHP default.
 *
 * @param mixed $date_string A date/time string. Valid formats are explained in http://php.net/manual/en/datetime.formats.php.
 * @param string $timezone A timezone identifier, like UTC or Europe/Lisbon. The list of valid identifiers is available http://php.net/manual/en/timezones.php.
 *
 * @return ActionScheduler_DateTime
 */
function as_get_datetime_object( $date_string = null, $timezone = 'UTC' ) {
	if ( is_object( $date_string ) && $date_string instanceof DateTime ) {
		$date = new ActionScheduler_DateTime( $date_string->format( 'Y-m-d H:i:s' ), new DateTimeZone( $timezone ) );
	} elseif ( is_numeric( $date_string ) ) {
		$date = new ActionScheduler_DateTime( '@' . $date_string, new DateTimeZone( $timezone ) );
	} else {
		$date = new ActionScheduler_DateTime( $date_string, new DateTimeZone( $timezone ) );
	}
	return $date;
}
