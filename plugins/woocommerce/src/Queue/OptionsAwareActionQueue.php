<?php
/**
 * OptionsAwareActionQueue class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Queue;

use Automattic\WooCommerce\Enums\QueueCapability;

/**
 * The default WC()->queue() implementation.
 *
 * Extends WC_Action_Queue so that it accepts the scheduling options described by
 * OptionsAwareQueueInterface and passes them to Action Scheduler, guarding the cases where an
 * older Action Scheduler copy bundled by another plugin won the load race.
 *
 * Third-party queues that extend WC_Action_Queue do not implement the options-aware interface,
 * so callers going through Scheduler fall back to the plain methods those queues override.
 *
 * @since 11.3.0
 */
class OptionsAwareActionQueue extends \WC_Action_Queue implements OptionsAwareQueueInterface {

	/**
	 * Priority used when none is requested. Matches Action Scheduler's own default.
	 *
	 * @since 11.3.0
	 * @var int
	 */
	public const DEFAULT_PRIORITY = 10;

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
	public function add( $hook, $args = array(), $group = '', $options = array() ) {
		return $this->schedule_single( time(), $hook, $args, $group, $this->coerce_options( $options ) );
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
	public function schedule_single( $timestamp, $hook, $args = array(), $group = '', $options = array() ) {
		$options = $this->coerce_options( $options );
		if ( ! $this->can_schedule( __FUNCTION__, $hook, $args, $group, $options ) ) {
			return 0;
		}

		// Action Scheduler older than 3.6.0 discards the surplus priority argument, so it is passed unconditionally.
		return as_schedule_single_action( $timestamp, $hook, $args, $group, $this->is_unique_requested( $options ), $this->get_priority( $options ) );
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
	public function schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', $options = array() ) {
		$options = $this->coerce_options( $options );
		if ( ! $this->can_schedule( __FUNCTION__, $hook, $args, $group, $options ) ) {
			return 0;
		}

		// Action Scheduler older than 3.6.0 discards the surplus priority argument, so it is passed unconditionally.
		return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, $group, $this->is_unique_requested( $options ), $this->get_priority( $options ) );
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
	public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '', $options = array() ) {
		$options = $this->coerce_options( $options );
		if ( ! $this->can_schedule( __FUNCTION__, $hook, $args, $group, $options ) ) {
			return 0;
		}

		// Action Scheduler older than 3.6.0 discards the surplus priority argument, so it is passed unconditionally.
		return as_schedule_cron_action( $timestamp, $cron_schedule, $hook, $args, $group, $this->is_unique_requested( $options ), $this->get_priority( $options ) );
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
		if ( ! $this->is_ready() ) {
			wc_doing_it_wrong(
				__METHOD__,
				__( 'Action Scheduler is not ready, so scheduled actions cannot be checked. Call this after Action Scheduler has initialized.', 'woocommerce' ),
				'11.3.0'
			);

			return false;
		}

		// `as_has_scheduled_action` only exists since Action Scheduler 3.3.0; an older copy may have won the load race.
		if ( function_exists( 'as_has_scheduled_action' ) ) {
			return (bool) as_has_scheduled_action( $hook, $args, $group );
		}

		// `as_next_scheduled_action` returns the timestamp of the next pending action, hence the cast.
		return (bool) as_next_scheduled_action( $hook, $args, $group );
	}

	/**
	 * Count matching actions with a store-level count query, without hydrating them.
	 *
	 * Date criteria are converted the way `as_get_scheduled_actions()` converts them. A copy of
	 * Action Scheduler too old to count in the store ignores the query type and returns IDs, which
	 * are then counted. Reports 0 with a doing-it-wrong notice when Action Scheduler is not ready.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args Search criteria, as accepted by WC_Queue_Interface::search().
	 * @return int
	 */
	public function count( $args = array() ): int {
		if ( ! $this->is_ready() ) {
			wc_doing_it_wrong(
				__METHOD__,
				__( 'Action Scheduler is not ready, so scheduled actions cannot be counted. Call this after Action Scheduler has initialized.', 'woocommerce' ),
				'11.3.0'
			);

			return 0;
		}

		$args = is_array( $args ) ? $args : array();
		foreach ( array( 'date', 'modified' ) as $key ) {
			if ( isset( $args[ $key ] ) && function_exists( 'as_get_datetime_object' ) ) {
				$args[ $key ] = as_get_datetime_object( $args[ $key ] );
			}
		}

		$result = \ActionScheduler::store()->query_actions( $args, 'count' );

		return is_array( $result ) ? count( $result ) : (int) $result;
	}

	/**
	 * Whether Action Scheduler can accept calls yet.
	 *
	 * The copy that won the load race registers its functions at `plugins_loaded` priority 0 and
	 * initialises its data store at `init`. Calls in between fail quietly inside Action Scheduler.
	 * Copies older than 3.1.6 cannot report initialisation and are assumed ready, which is the
	 * assumption every direct Action Scheduler call in core makes on such a copy.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return false;
		}

		// PHPStan sees the bundled Action Scheduler, where the method exists. An older copy that won
		// the load race may predate it (added in 3.1.6), which is the case this guard is for.
		// @phpstan-ignore-next-line function.alreadyNarrowedType -- see comment above.
		if ( method_exists( \ActionScheduler::class, 'is_initialized' ) ) {
			return (bool) \ActionScheduler::is_initialized();
		}

		return true;
	}

	/**
	 * The version of the Action Scheduler copy that won the load race, or null when none is loaded.
	 *
	 * Copies register at `plugins_loaded` priority 0, so the answer is null before that hook has
	 * run. Protected so tests can force the paths that older copies take.
	 *
	 * @since 11.3.0
	 *
	 * @return string|null
	 */
	protected function get_action_scheduler_version(): ?string {
		if ( ! did_action( 'plugins_loaded' ) || ! class_exists( \ActionScheduler_Versions::class ) ) {
			return null;
		}

		$version = \ActionScheduler_Versions::instance()->latest_version();

		return is_string( $version ) && '' !== $version ? $version : null;
	}

	/**
	 * Whether the loaded Action Scheduler has a capability.
	 *
	 * `unique` needs Action Scheduler 3.5.0 and `priority` needs 3.6.0. The scheduling methods pass
	 * both arguments regardless, since older copies ignore what they do not declare, so this exists
	 * for callers deciding whether a requested option will take effect.
	 *
	 * @since 11.3.0
	 *
	 * @param string $capability A QueueCapability value.
	 * @return bool
	 */
	public function supports( $capability ): bool {
		switch ( $capability ) {
			case QueueCapability::UNIQUE:
				return $this->action_scheduler_is_at_least( '3.5.0' );
			case QueueCapability::PRIORITY:
				return $this->action_scheduler_is_at_least( '3.6.0' );
			default:
				return false;
		}
	}

	/**
	 * Whether the loaded Action Scheduler is at least the given version.
	 *
	 * @param string $version The minimum version.
	 * @return bool False when no version can be determined.
	 */
	private function action_scheduler_is_at_least( string $version ): bool {
		$loaded = $this->get_action_scheduler_version();

		return null !== $loaded && version_compare( $loaded, $version, '>=' );
	}

	/**
	 * Bring a priority value into the 0-255 range Action Scheduler accepts.
	 *
	 * Clamps before narrowing to an integer, since a float above the integer range would otherwise
	 * wrap to a negative number and land at the highest priority. Non-numeric and non-finite values
	 * fall back to the default.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $value The requested priority.
	 * @return int
	 */
	public static function normalize_priority( $value ): int {
		if ( ! is_numeric( $value ) || ! is_finite( (float) $value ) ) {
			return self::DEFAULT_PRIORITY;
		}

		return (int) max( 0, min( 255, (float) $value ) );
	}

	/**
	 * Decide whether a scheduling call can go ahead.
	 *
	 * Returns false, with a doing-it-wrong notice, when Action Scheduler is not ready. Also returns
	 * false when a unique action is requested on an Action Scheduler copy that predates the
	 * `$unique` argument and a matching action is already pending, emulating uniqueness.
	 *
	 * @param string $method The public method being guarded, named in the notice.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options.
	 * @return bool
	 */
	private function can_schedule( string $method, $hook, $args, $group, array $options ): bool {
		if ( ! $this->is_ready() ) {
			wc_doing_it_wrong( __CLASS__ . '::' . $method, __( 'Action Scheduler is not ready, so actions cannot be scheduled. Call this after Action Scheduler has initialized.', 'woocommerce' ), '11.3.0' );
			return false;
		}

		if ( $this->is_unique_requested( $options ) && ! $this->supports( QueueCapability::UNIQUE ) && as_next_scheduled_action( $hook, $args, $group ) ) {
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
	private function is_unique_requested( array $options ): bool {
		return ! empty( $options[ QueueCapability::UNIQUE ] );
	}

	/**
	 * Read the `priority` option, normalised, defaulting to 10.
	 *
	 * @param array $options Scheduling options.
	 * @return int
	 */
	private function get_priority( array $options ): int {
		return self::normalize_priority( $options[ QueueCapability::PRIORITY ] ?? self::DEFAULT_PRIORITY );
	}

	/**
	 * Coerce anything a caller might pass as `$options` into an array.
	 *
	 * The base interface has no options parameter, so a stray extra argument used to be discarded
	 * by PHP. Discarding it here keeps that behaviour instead of turning it into a type error.
	 *
	 * @param mixed $options Whatever the caller passed.
	 * @return array
	 */
	private function coerce_options( $options ): array {
		return is_array( $options ) ? $options : array();
	}
}
