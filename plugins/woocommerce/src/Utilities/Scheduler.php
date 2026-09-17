<?php
/**
 * Scheduler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Utilities;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * The entry point for scheduling background work in WooCommerce.
 *
 * Every call goes through the active queue, `WC()->queue()`, so queues attached through the
 * `woocommerce_queue_class` filter keep intercepting. Scheduling options such as `priority` and
 * `unique` reach queues that implement WC_Options_Aware_Queue_Interface natively; on a plain
 * WC_Queue_Interface the priority is dropped and uniqueness is emulated with `get_next()`, which
 * cannot see in-progress actions.
 *
 * The `queue` option selects the stock queue instead of the active one, for callers that depend
 * on Action Scheduler specifically. The stock queue owns the guards for the case where an older
 * Action Scheduler copy bundled by another plugin won the load race.
 *
 * Resolve it through the container: `wc_get_container()->get( Scheduler::class )`.
 *
 * @since 11.3.0
 */
class Scheduler {

	/**
	 * Value of the `queue` option that routes through the active queue. The default.
	 *
	 * @since 11.3.0
	 */
	public const QUEUE_ACTIVE = 'active';

	/**
	 * Value of the `queue` option that routes through the stock queue, ignoring `woocommerce_queue_class`.
	 *
	 * @since 11.3.0
	 */
	public const QUEUE_DEFAULT = 'default';

	/**
	 * Priority used when none is requested. Matches Action Scheduler's own default.
	 *
	 * @since 11.3.0
	 */
	public const DEFAULT_PRIORITY = 10;

	/**
	 * The legacy proxy, resolved on first use.
	 *
	 * @var LegacyProxy|null
	 */
	private $proxy = null;

	/**
	 * Enqueue an action to run one time, as soon as possible.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10), `unique` (bool, default false), `queue` (one of the QUEUE_* constants).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function add( string $hook, array $args = array(), string $group = '', array $options = array() ): int {
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
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10), `unique` (bool, default false), `queue` (one of the QUEUE_* constants).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_single( int $timestamp, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options );
		$queue   = $this->queue_for( $options );

		if ( $queue instanceof \WC_Options_Aware_Queue_Interface ) {
			return (int) $queue->schedule_single( $timestamp, $hook, $args, $group, $this->queue_options( $options ) );
		}

		if ( $this->emulated_unique_match( $queue, $options, $hook, $args, $group ) ) {
			return 0;
		}

		return (int) $queue->schedule_single( $timestamp, $hook, $args, $group );
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
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10), `unique` (bool, default false), `queue` (one of the QUEUE_* constants).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_recurring( int $timestamp, int $interval_in_seconds, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options );
		$queue   = $this->queue_for( $options );

		if ( $queue instanceof \WC_Options_Aware_Queue_Interface ) {
			return (int) $queue->schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args, $group, $this->queue_options( $options ) );
		}

		if ( $this->emulated_unique_match( $queue, $options, $hook, $args, $group ) ) {
			return 0;
		}

		return (int) $queue->schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args, $group );
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
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10), `unique` (bool, default false), `queue` (one of the QUEUE_* constants).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 */
	public function schedule_cron( int $timestamp, string $cron_schedule, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options );
		$queue   = $this->queue_for( $options );

		if ( $queue instanceof \WC_Options_Aware_Queue_Interface ) {
			return (int) $queue->schedule_cron( $timestamp, $cron_schedule, $hook, $args, $group, $this->queue_options( $options ) );
		}

		if ( $this->emulated_unique_match( $queue, $options, $hook, $args, $group ) ) {
			return 0;
		}

		return (int) $queue->schedule_cron( $timestamp, $cron_schedule, $hook, $args, $group );
	}

	/**
	 * Cancel the next occurrence of a scheduled action.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook that the job will trigger.
	 * @param array  $args Args that would have been passed to the job.
	 * @param string $group The group the job is assigned to.
	 * @param array  $options Only `queue` (one of the QUEUE_* constants) applies here.
	 * @return void
	 */
	public function cancel( string $hook, array $args = array(), string $group = '', array $options = array() ): void {
		$this->queue_for( $this->normalize_options( $options ) )->cancel( $hook, $args, $group );
	}

	/**
	 * Cancel all occurrences of a scheduled action.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook that the job will trigger.
	 * @param array  $args Args that would have been passed to the job.
	 * @param string $group The group the job is assigned to.
	 * @param array  $options Only `queue` (one of the QUEUE_* constants) applies here.
	 * @return void
	 */
	public function cancel_all( string $hook, array $args = array(), string $group = '', array $options = array() ): void {
		$this->queue_for( $this->normalize_options( $options ) )->cancel_all( $hook, $args, $group );
	}

	/**
	 * Get the date and time of the next pending occurrence of a scheduled action.
	 *
	 * @since 11.3.0
	 *
	 * @param string     $hook The hook that the job will trigger.
	 * @param array|null $args Args that would have been passed to the job. Null matches any args.
	 * @param string     $group The group the job is assigned to.
	 * @param array      $options Only `queue` (one of the QUEUE_* constants) applies here.
	 * @return \WC_DateTime|null The next occurrence, or null when nothing is pending.
	 */
	public function get_next( string $hook, ?array $args = null, string $group = '', array $options = array() ): ?\WC_DateTime {
		$next = $this->queue_for( $this->normalize_options( $options ) )->get_next( $hook, $args, $group );

		return $next instanceof \WC_DateTime ? $next : null;
	}

	/**
	 * Find scheduled actions.
	 *
	 * @since 11.3.0
	 *
	 * @param array  $args Search criteria, as accepted by WC_Queue_Interface::search().
	 * @param string $return_format OBJECT, ARRAY_A, or 'ids'.
	 * @param array  $options Only `queue` (one of the QUEUE_* constants) applies here.
	 * @return array
	 */
	public function search( array $args = array(), $return_format = OBJECT, array $options = array() ): array {
		$results = $this->queue_for( $this->normalize_options( $options ) )->search( $args, $return_format );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Check whether a matching action is currently scheduled.
	 *
	 * On an options-aware queue this covers pending and in-progress actions. On a plain queue it
	 * relies on `get_next()`, which only sees pending ones.
	 *
	 * @since 11.3.0
	 *
	 * @param string     $hook The hook of the action.
	 * @param array|null $args Args of the action. Null matches any args.
	 * @param string     $group The group the action is assigned to.
	 * @param array      $options Only `queue` (one of the QUEUE_* constants) applies here.
	 * @return bool
	 */
	public function has_scheduled_action( string $hook, ?array $args = null, string $group = '', array $options = array() ): bool {
		$queue = $this->queue_for( $this->normalize_options( $options ) );

		if ( $queue instanceof \WC_Options_Aware_Queue_Interface ) {
			return (bool) $queue->has_scheduled_action( $hook, $args, $group );
		}

		return null !== $queue->get_next( $hook, $args, $group );
	}

	/**
	 * Whether a scheduling option will take effect on the path a call would take.
	 *
	 * `priority` needs an options-aware queue, and on the stock queue an Action Scheduler of 3.6.0 or
	 * newer. `unique` always takes effect, natively or emulated. Unknown options never do.
	 *
	 * @since 11.3.0
	 *
	 * @param string $option The option name: `priority` or `unique`.
	 * @param array  $options Only `queue` (one of the QUEUE_* constants) applies here, to pick the path.
	 * @return bool
	 */
	public function supports( string $option, array $options = array() ): bool {
		$queue = $this->queue_for( $this->normalize_options( $options ) );

		switch ( $option ) {
			case 'unique':
				return true;
			case 'priority':
				if ( $queue instanceof \WC_Options_Aware_Action_Queue ) {
					return $queue->supports_priority();
				}
				return $queue instanceof \WC_Options_Aware_Queue_Interface;
			default:
				return false;
		}
	}

	/**
	 * The version of the Action Scheduler copy that won the load race, or null when none is loaded.
	 *
	 * @since 11.3.0
	 *
	 * @return string|null
	 */
	public function get_version(): ?string {
		if ( ! class_exists( \ActionScheduler_Versions::class ) ) {
			return null;
		}

		$version = \ActionScheduler_Versions::instance()->latest_version();

		return is_string( $version ) && '' !== $version ? $version : null;
	}

	/**
	 * Whether scheduling calls can be made yet.
	 *
	 * The queue class is chosen at `plugins_loaded`. When the stock queue is active, Action Scheduler
	 * must also have loaded its functions and, on copies that report it, initialised its data store,
	 * which happens at `init`.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		if ( ! did_action( 'plugins_loaded' ) ) {
			return false;
		}

		if ( ! $this->active_queue() instanceof \WC_Options_Aware_Action_Queue ) {
			return true;
		}

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
	 * Bring the options into a known shape: `priority`, `unique` and `queue`, nothing else.
	 *
	 * The priority is clamped to 0-255 before it is narrowed to an integer, since a float above the
	 * integer range would otherwise wrap to a negative number. Non-numeric and non-finite values fall
	 * back to the default.
	 *
	 * @param array $options Options as given by the caller.
	 * @return array
	 */
	private function normalize_options( array $options ): array {
		$priority = $options['priority'] ?? self::DEFAULT_PRIORITY;
		$priority = is_numeric( $priority ) && is_finite( (float) $priority )
			? (int) max( 0, min( 255, (float) $priority ) )
			: self::DEFAULT_PRIORITY;

		$queue = $options['queue'] ?? self::QUEUE_ACTIVE;
		if ( ! in_array( $queue, array( self::QUEUE_ACTIVE, self::QUEUE_DEFAULT ), true ) ) {
			wc_doing_it_wrong( __METHOD__, 'The queue option must be one of the Scheduler::QUEUE_* constants. Falling back to the active queue.', '11.3.0' );
			$queue = self::QUEUE_ACTIVE;
		}

		return array(
			'priority' => $priority,
			'unique'   => ! empty( $options['unique'] ),
			'queue'    => $queue,
		);
	}

	/**
	 * The options a queue receives. The `queue` key is the scheduler's own and never leaves it.
	 *
	 * @param array $options Normalised options.
	 * @return array
	 */
	private function queue_options( array $options ): array {
		return array(
			'priority' => $options['priority'],
			'unique'   => $options['unique'],
		);
	}

	/**
	 * Whether a unique action requested on a plain queue already has a pending match.
	 *
	 * @param \WC_Queue_Interface $queue The plain queue.
	 * @param array               $options Normalised options.
	 * @param string              $hook The hook to trigger.
	 * @param array               $args Arguments to pass when the hook triggers.
	 * @param string              $group The group to assign this job to.
	 * @return bool
	 */
	private function emulated_unique_match( \WC_Queue_Interface $queue, array $options, string $hook, array $args, string $group ): bool {
		return $options['unique'] && null !== $queue->get_next( $hook, $args, $group );
	}

	/**
	 * The queue a call should go through.
	 *
	 * @param array $options Normalised options.
	 * @return \WC_Queue_Interface
	 */
	private function queue_for( array $options ): \WC_Queue_Interface {
		return self::QUEUE_DEFAULT === $options['queue'] ? $this->default_queue() : $this->active_queue();
	}

	/**
	 * The queue WooCommerce is configured with, honouring `woocommerce_queue_class`.
	 *
	 * @return \WC_Queue_Interface
	 */
	private function active_queue(): \WC_Queue_Interface {
		return $this->proxy()->get_instance_of( \WC_Queue_Interface::class );
	}

	/**
	 * The stock queue, regardless of `woocommerce_queue_class`.
	 *
	 * @return \WC_Options_Aware_Action_Queue
	 */
	private function default_queue(): \WC_Options_Aware_Action_Queue {
		return $this->proxy()->get_instance_of( \WC_Options_Aware_Action_Queue::class );
	}

	/**
	 * The legacy proxy, through which legacy queue classes are reached so tests can replace them.
	 *
	 * @return LegacyProxy
	 */
	private function proxy(): LegacyProxy {
		if ( null === $this->proxy ) {
			$this->proxy = wc_get_container()->get( LegacyProxy::class );
		}

		return $this->proxy;
	}
}
