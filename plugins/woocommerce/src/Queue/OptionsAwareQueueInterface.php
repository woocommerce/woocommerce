<?php
/**
 * OptionsAwareQueueInterface interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Queue;

/**
 * Contract for queues that accept per-action scheduling options.
 *
 * A queue opts in by implementing this interface in addition to WC_Queue_Interface. Existing
 * WC_Queue_Interface implementations, including third-party queues attached through the
 * `woocommerce_queue_class` filter, remain valid and are unaffected.
 *
 * Callers should not use this interface directly. They go through Scheduler, which passes the
 * options to queues that implement this interface and falls back to the plain WC_Queue_Interface
 * methods otherwise.
 *
 * Recognised option keys:
 *
 * - `priority` (int): lower values run first. Action Scheduler accepts 0-255, default 10.
 * - `unique` (bool): when true, skip scheduling and return 0 if an action with the same hook,
 *   args and group is already pending or in progress. Default false.
 *
 * The `$options` parameters are deliberately untyped, like every parameter on WC_Queue_Interface.
 * Implementations must coerce a non-array to an empty array and ignore keys they do not recognise,
 * so that new options can be added without breaking existing queues.
 *
 * @since 11.3.0
 */
interface OptionsAwareQueueInterface extends \WC_Queue_Interface {

	/**
	 * Enqueue an action to run one time, as soon as possible.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options. See the interface description for the recognised keys.
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function add( $hook, $args = array(), $group = '', $options = array() );

	/**
	 * Schedule an action to run once at some time in the future.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options. See the interface description for the recognised keys.
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_single( $timestamp, $hook, $args = array(), $group = '', $options = array() );

	/**
	 * Schedule a recurring action.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp When the first instance of the job will run.
	 * @param int    $interval_in_seconds How long to wait between runs.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options. See the interface description for the recognised keys.
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', $options = array() );

	/**
	 * Schedule an action that recurs on a cron-like schedule.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp The schedule will start on or after this time.
	 * @param string $cron_schedule A cron-like schedule string.
	 * @see http://en.wikipedia.org/wiki/Cron
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options. See the interface description for the recognised keys.
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', $options = array() );

	/**
	 * Check whether a matching action is currently scheduled.
	 *
	 * Should report both pending and in-progress actions where the backend can tell them apart.
	 *
	 * @since 11.3.0
	 *
	 * @param string     $hook The hook of the action.
	 * @param array|null $args Args of the action. Null matches any args.
	 * @param string     $group The group the action is assigned to.
	 * @return bool True if a matching action is pending or in progress.
	 */
	public function has_scheduled_action( $hook, $args = null, $group = '' );

	/**
	 * Whether this queue honours a scheduling option natively.
	 *
	 * Answers per option, `unique` and `priority` today, and must return false for an option it
	 * does not know. The scheduler relies on it for `supports()`, for `strict` calls and for the
	 * system status report, so it should reflect the backend actually in use.
	 *
	 * @since 11.3.0
	 *
	 * @param string $option The option name.
	 * @return bool True if the option takes effect natively on this queue.
	 */
	public function supports( $option );
}
