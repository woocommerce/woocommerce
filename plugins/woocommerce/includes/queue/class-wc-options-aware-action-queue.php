<?php
/**
 * Options Aware Action Queue
 *
 * @version 11.3.0
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC Options Aware Action Queue
 *
 * The default WC()->queue() implementation. Extends WC_Action_Queue so that it accepts the
 * scheduling options described by WC_Options_Aware_Queue_Interface and passes them to
 * Action Scheduler, guarding the cases where an older Action Scheduler copy bundled by another
 * plugin won the load race.
 *
 * Third-party queues that extend WC_Action_Queue do not implement the options-aware interface,
 * so callers going through Automattic\WooCommerce\Utilities\Scheduler fall back to the plain
 * methods those queues override.
 *
 * @since 11.3.0
 */
class WC_Options_Aware_Action_Queue extends WC_Action_Queue implements WC_Options_Aware_Queue_Interface {

	/**
	 * Enqueue an action to run one time, as soon as possible.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10) and `unique` (bool, default false).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function add( $hook, $args = array(), $group = '', array $options = array() ) {
		return $this->schedule_single( time(), $hook, $args, $group, $options );
	}

	/**
	 * Schedule an action to run once at some time in the future.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp When the job will run.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10) and `unique` (bool, default false).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_single( $timestamp, $hook, $args = array(), $group = '', array $options = array() ) {
		if ( ! $this->can_schedule( 'as_schedule_single_action', $hook, $args, $group, $options ) ) {
			return 0;
		}

		return as_schedule_single_action( $timestamp, $hook, $args, $group, $this->unique( $options ), $this->priority( $options ) );
	}

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
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10) and `unique` (bool, default false).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', array $options = array() ) {
		if ( ! $this->can_schedule( 'as_schedule_recurring_action', $hook, $args, $group, $options ) ) {
			return 0;
		}

		return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, $group, $this->unique( $options ), $this->priority( $options ) );
	}

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
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10) and `unique` (bool, default false).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', array $options = array() ) {
		if ( ! $this->can_schedule( 'as_schedule_cron_action', $hook, $args, $group, $options ) ) {
			return 0;
		}

		return as_schedule_cron_action( $timestamp, $cron_schedule, $hook, $args, $group, $this->unique( $options ), $this->priority( $options ) );
	}

	/**
	 * Check whether a matching action is pending or in progress.
	 *
	 * Prefers `as_has_scheduled_action`, which only exists since Action Scheduler 3.3.0, and falls
	 * back to the much older `as_next_scheduled_action`, which may report only pending actions.
	 * Reports false with a doing-it-wrong notice when Action Scheduler is not loaded at all.
	 *
	 * @since 11.3.0
	 *
	 * @param string     $hook The hook of the action.
	 * @param array|null $args Args of the action. Null matches any args.
	 * @param string     $group The group the action is assigned to.
	 * @return bool True if a matching action is pending or in progress.
	 */
	public function has_scheduled_action( $hook, $args = null, $group = '' ) {
		foreach ( array( 'as_has_scheduled_action', 'as_next_scheduled_action' ) as $function ) {
			// PHPStan sees the Action Scheduler copy bundled with WooCommerce and concludes both functions
			// always exist. The runtime case this guard exists for is precisely the one it cannot see.
			// @phpstan-ignore-next-line function.alreadyNarrowedType -- see comment above.
			if ( function_exists( $function ) ) {
				// `as_next_scheduled_action` returns the timestamp of the next pending action, hence the cast.
				return (bool) $function( $hook, $args, $group );
			}
		}

		wc_doing_it_wrong(
			__METHOD__,
			'Action Scheduler is not loaded, so scheduled actions cannot be checked. Call this after Action Scheduler has initialized.',
			'11.3.0'
		);

		return false;
	}

	/**
	 * The version of the Action Scheduler copy that won the load race, or null when none is loaded.
	 *
	 * Protected so tests can force the paths that older copies take.
	 *
	 * @since 11.3.0
	 *
	 * @return string|null
	 */
	protected function get_action_scheduler_version(): ?string {
		if ( ! class_exists( 'ActionScheduler_Versions' ) ) {
			return null;
		}

		$version = ActionScheduler_Versions::instance()->latest_version();

		return is_string( $version ) && '' !== $version ? $version : null;
	}

	/**
	 * Whether the loaded Action Scheduler accepts the `$unique` argument (3.5.0 and newer).
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public function supports_unique_actions(): bool {
		$version = $this->get_action_scheduler_version();

		return null !== $version && version_compare( $version, '3.5.0', '>=' );
	}

	/**
	 * Whether the loaded Action Scheduler accepts the `$priority` argument (3.6.0 and newer).
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public function supports_priority(): bool {
		$version = $this->get_action_scheduler_version();

		return null !== $version && version_compare( $version, '3.6.0', '>=' );
	}

	/**
	 * Decide whether a scheduling call can go ahead.
	 *
	 * Returns false, with a doing-it-wrong notice, when the Action Scheduler function is missing.
	 * Also returns false when a unique action is requested on an Action Scheduler copy that predates
	 * the `$unique` argument and a matching action is already pending, emulating uniqueness.
	 *
	 * @param string $function_name The Action Scheduler function the caller is about to use.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options.
	 * @return bool
	 */
	private function can_schedule( string $function_name, $hook, $args, $group, array $options ): bool {
		if ( ! function_exists( $function_name ) ) {
			wc_doing_it_wrong( __METHOD__, 'Action Scheduler is not loaded, so actions cannot be scheduled. Call this after Action Scheduler has initialized.', '11.3.0' );
			return false;
		}

		if ( $this->unique( $options ) && ! $this->supports_unique_actions() && as_next_scheduled_action( $hook, $args, $group ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Read the `unique` option.
	 *
	 * @param array $options Scheduling options.
	 * @return bool
	 */
	private function unique( array $options ): bool {
		return ! empty( $options['unique'] );
	}

	/**
	 * Read the `priority` option, defaulting to 10.
	 *
	 * @param array $options Scheduling options.
	 * @return int
	 */
	private function priority( array $options ): int {
		return isset( $options['priority'] ) ? (int) $options['priority'] : 10;
	}
}
