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
 * - `priority`: lower values run first. The queue applies its own range and default; the stock
 *   queue clamps to the 0-255 Action Scheduler accepts and defaults to 10.
 * - `unique` (bool): when true, skip scheduling and return 0 if an action with the same hook,
 *   args and group is already pending or in progress. Default false.
 *
 * The `$options` parameters are deliberately untyped, like every parameter on WC_Queue_Interface.
 * Implementations own the values they receive: coerce a non-array to an empty array, validate and
 * default each recognised key, and ignore keys they do not recognise, so that new options can be
 * added without breaking existing queues. The scheduler forwards option values as the caller gave
 * them.
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
	 * Enqueue an action to run as soon as possible, without a scheduled time.
	 *
	 * Where the backend distinguishes async actions from timestamped ones, as Action Scheduler does,
	 * an async action is claimed ahead of overdue timestamped work of the same priority. A backend
	 * without that distinction may treat this exactly like `add()`.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options. See the interface description for the recognised keys.
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function enqueue_async( $hook, $args = array(), $group = '', $options = array() );

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
	 * Whether this queue has a capability natively.
	 *
	 * The capabilities are the Automattic\WooCommerce\Enums\QueueCapability values, each of which is
	 * also the scheduling option that requests it. Answer per capability, reflecting the backend
	 * actually in use, and return false for one you do not know. The scheduler relies on this for
	 * `supports()`, for `strict` calls and for the system status report.
	 *
	 * @since 11.3.0
	 *
	 * @param string $capability A QueueCapability value.
	 * @return bool True if the capability takes effect natively on this queue.
	 */
	public function supports( $capability );

	/**
	 * Whether this queue can accept calls yet.
	 *
	 * Return false until the backend is usable, for example while its data store is not yet
	 * initialised. The scheduler checks this before every operation and, when false, raises a
	 * notice and returns a neutral value instead of calling the queue, or throws in strict mode.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public function is_ready();

	/**
	 * Count the actions matching the given criteria.
	 *
	 * Takes the same criteria as `search()`. Count without hydrating actions where the backend
	 * can, since callers use this on large stores precisely to avoid loading every match.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args Search criteria, as accepted by WC_Queue_Interface::search().
	 * @return int The number of matching actions.
	 */
	public function count( $args = array() );
}
