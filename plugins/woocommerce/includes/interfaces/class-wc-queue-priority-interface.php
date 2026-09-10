<?php
/**
 * Queue Priority Interface
 *
 * @version 11.2.0
 * @package WooCommerce\Interface
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC Queue Priority Interface
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
 *     if ( $queue instanceof WC_Queue_Priority_Interface ) {
 *         $queue->schedule_single_with_priority( $timestamp, $hook, $args, $group, 20 );
 *     } else {
 *         $queue->schedule_single( $timestamp, $hook, $args, $group );
 *     }
 *
 * @since 11.2.0
 */
interface WC_Queue_Priority_Interface extends WC_Queue_Interface {

	/**
	 * Enqueue an action to run one time, as soon as possible, at a given priority.
	 *
	 * @since 11.2.0
	 *
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 * @return int The action ID
	 */
	public function add_with_priority( $hook, $args = array(), $group = '', $priority = 10 );

	/**
	 * Schedule an action to run once at some time in the future, at a given priority.
	 *
	 * @since 11.2.0
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 * @return int The action ID
	 */
	public function schedule_single_with_priority( $timestamp, $hook, $args = array(), $group = '', $priority = 10 );

	/**
	 * Schedule a recurring action at a given priority.
	 *
	 * @since 11.2.0
	 *
	 * @param int    $timestamp When the first instance of the job will run.
	 * @param int    $interval_in_seconds How long to wait between runs.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 * @return int The action ID
	 */
	public function schedule_recurring_with_priority( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', $priority = 10 );

	/**
	 * Schedule an action that recurs on a cron-like schedule, at a given priority.
	 *
	 * @since 11.2.0
	 *
	 * @param int    $timestamp The schedule will start on or after this time.
	 * @param string $cron_schedule A cron-like schedule string.
	 * @see http://en.wikipedia.org/wiki/Cron
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
	 * @return int The action ID
	 */
	public function schedule_cron_with_priority( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', $priority = 10 );
}
