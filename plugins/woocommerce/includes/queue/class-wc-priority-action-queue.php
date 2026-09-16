<?php
/**
 * Priority Action Queue
 *
 * @version 11.3.0
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC Priority Action Queue
 *
 * The default WC()->queue() implementation. Extends WC_Action_Queue with methods that
 * schedule Action Scheduler actions at a given priority.
 *
 * Queues that extend WC_Action_Queue directly do not implement WC_Priority_Queue_Interface,
 * so callers that check for it fall back to the plain methods and any overrides keep working.
 * A queue that extends this class and overrides the plain scheduling methods should override
 * the *_with_priority() ones as well: they call Action Scheduler directly rather than going
 * through the plain methods.
 *
 * @since 11.3.0
 */
class WC_Priority_Action_Queue extends WC_Action_Queue implements WC_Priority_Queue_Interface {

	/**
	 * Enqueue an action to run one time, as soon as possible, at a given priority.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 * @param bool   $unique Whether the action should be unique: skipped when an action with the same hook, args and group is already pending or in progress.
	 * @return int The action ID, or 0 when $unique is true and a matching action already exists.
	 */
	public function add_with_priority( $hook, $args = array(), $group = '', $priority = 10, $unique = false ) {
		return $this->schedule_single_with_priority( time(), $hook, $args, $group, $priority, $unique );
	}

	/**
	 * Schedule an action to run once at some time in the future, at a given priority.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 * @param bool   $unique Whether the action should be unique: skipped when an action with the same hook, args and group is already pending or in progress.
	 * @return int The action ID, or 0 when $unique is true and a matching action already exists.
	 */
	public function schedule_single_with_priority( $timestamp, $hook, $args = array(), $group = '', $priority = 10, $unique = false ) {
		return as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
	}

	/**
	 * Schedule a recurring action at a given priority.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp When the first instance of the job will run.
	 * @param int    $interval_in_seconds How long to wait between runs.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 * @param bool   $unique Whether the action should be unique: skipped when an action with the same hook, args and group is already pending or in progress.
	 * @return int The action ID, or 0 when $unique is true and a matching action already exists.
	 */
	public function schedule_recurring_with_priority( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', $priority = 10, $unique = false ) {
		return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, $group, $unique, $priority );
	}

	/**
	 * Schedule an action that recurs on a cron-like schedule, at a given priority.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp The schedule will start on or after this time.
	 * @param string $cron_schedule A cron-like schedule string.
	 * @see http://en.wikipedia.org/wiki/Cron
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 * @param bool   $unique Whether the action should be unique: skipped when an action with the same hook, args and group is already pending or in progress.
	 * @return int The action ID, or 0 when $unique is true and a matching action already exists.
	 */
	public function schedule_cron_with_priority( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', $priority = 10, $unique = false ) {
		return as_schedule_cron_action( $timestamp, $cron_schedule, $hook, $args, $group, $unique, $priority );
	}
}
