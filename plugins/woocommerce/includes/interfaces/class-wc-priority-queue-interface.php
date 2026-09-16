<?php
/**
 * Priority Queue Interface
 *
 * @version 11.3.0
 * @package WooCommerce\Interface
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC Priority Queue Interface
 *
 * Implemented by queues that can pass an Action Scheduler priority through to the actions
 * they schedule. Lower priorities run first, the accepted range is 0-255, and the default
 * is 10 - the same convention WordPress uses for hook priorities.
 *
 * Implementing this interface is optional: a queue attached through the
 * `woocommerce_queue_class` filter only has to implement WC_Queue_Interface. Check for
 * support before asking for a priority, and fall back to the plain method otherwise:
 *
 *     $queue = WC()->queue();
 *     if ( $queue instanceof WC_Priority_Queue_Interface ) {
 *         $queue->schedule_single_with_priority( $timestamp, $hook, $args, $group, 20 );
 *     } else {
 *         $queue->schedule_single( $timestamp, $hook, $args, $group );
 *     }
 *
 * Action Scheduler has accepted a priority since 3.6.0. If an older copy bundled by another
 * plugin wins the load race, the priority is ignored and the action is scheduled at the
 * default.
 *
 * These methods also expose Action Scheduler's $unique flag. A unique action is skipped, and
 * 0 returned instead of an action ID, when an action with the same hook, args and group is
 * already pending or in progress. The WC_Queue_Interface methods always schedule non-unique
 * actions.
 *
 * @since 11.3.0
 */
interface WC_Priority_Queue_Interface extends WC_Queue_Interface {

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
	public function add_with_priority( $hook, $args = array(), $group = '', $priority = 10, $unique = false );

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
	public function schedule_single_with_priority( $timestamp, $hook, $args = array(), $group = '', $priority = 10, $unique = false );

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
	public function schedule_recurring_with_priority( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', $priority = 10, $unique = false );

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
	public function schedule_cron_with_priority( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', $priority = 10, $unique = false );
}
