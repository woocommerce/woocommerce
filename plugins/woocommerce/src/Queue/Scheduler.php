<?php
/**
 * Scheduler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Queue;

use Automattic\WooCommerce\Enums\QueueCapability;
use Automattic\WooCommerce\Enums\SchedulerQueue;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * The entry point for scheduling background work in WooCommerce.
 *
 * Every call goes through the active queue, `WC()->queue()`, so queues attached through the
 * `woocommerce_queue_class` filter keep intercepting. Scheduling options such as `priority` and
 * `unique` reach queues that implement OptionsAwareQueueInterface natively; on a plain
 * WC_Queue_Interface the priority is dropped and uniqueness is emulated with `get_next()`, which
 * cannot see in-progress actions and is not atomic.
 *
 * The `strict` option turns that best effort into a hard requirement: when true, a call whose
 * requested options cannot take effect natively throws instead of scheduling. `supports()` answers
 * the same question in advance.
 *
 * The `queue` option, one of the SchedulerQueue values, selects the stock queue instead of the
 * active one. That ignores a custom queue attached through `woocommerce_queue_class`, so it is
 * meant only for callers that depend on Action Scheduler specifically. The stock queue owns the
 * guards for the case where an older Action Scheduler copy bundled by another plugin won the
 * load race.
 *
 * Resolve it through the container: `wc_get_container()->get( Scheduler::class )`.
 *
 * The class is final: behaviour is customised through the queue, by attaching one through the
 * `woocommerce_queue_class` filter or by implementing OptionsAwareQueueInterface, not by subclassing.
 *
 * @since 11.3.0
 */
final class Scheduler {

	/**
	 * Priority used when none is requested. Matches Action Scheduler's own default.
	 *
	 * @since 11.3.0
	 */
	public const DEFAULT_PRIORITY = OptionsAwareActionQueue::DEFAULT_PRIORITY;

	/**
	 * The legacy proxy, through which the active queue is reached so tests can replace it.
	 *
	 * @var LegacyProxy
	 */
	private $legacy_proxy;

	/**
	 * The stock queue, used when a call selects it regardless of `woocommerce_queue_class`.
	 *
	 * @var OptionsAwareActionQueue
	 */
	private $default_queue;

	/**
	 * Initialize the class dependencies.
	 *
	 * @internal
	 *
	 * @param LegacyProxy             $legacy_proxy The legacy proxy.
	 * @param OptionsAwareActionQueue $default_queue The stock queue.
	 */
	final public function init( LegacyProxy $legacy_proxy, OptionsAwareActionQueue $default_queue ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules.
		$this->legacy_proxy  = $legacy_proxy;
		$this->default_queue = $default_queue;
	}

	/**
	 * Enqueue an action to run one time, as soon as possible.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10), `unique` (bool, default false), `strict` (bool, default false), `queue` (a SchedulerQueue value).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 * @throws \RuntimeException When `strict` is set and a requested option cannot take effect natively.
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
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10), `unique` (bool, default false), `strict` (bool, default false), `queue` (a SchedulerQueue value).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 * @throws \RuntimeException When `strict` is set and a requested option cannot take effect natively.
	 */
	public function schedule_single( int $timestamp, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->select_queue( $options );
		$this->assert_options_supported( $queue, $options );

		if ( $queue instanceof OptionsAwareQueueInterface ) {
			return (int) $queue->schedule_single( $timestamp, $hook, $args, $group, $this->extract_queue_options( $options ) );
		}

		if ( $this->has_pending_match( $queue, $options, $hook, $args, $group ) ) {
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
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10), `unique` (bool, default false), `strict` (bool, default false), `queue` (a SchedulerQueue value).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 * @throws \RuntimeException When `strict` is set and a requested option cannot take effect natively.
	 */
	public function schedule_recurring( int $timestamp, int $interval_in_seconds, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->select_queue( $options );
		$this->assert_options_supported( $queue, $options );

		if ( $queue instanceof OptionsAwareQueueInterface ) {
			return (int) $queue->schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args, $group, $this->extract_queue_options( $options ) );
		}

		if ( $this->has_pending_match( $queue, $options, $hook, $args, $group ) ) {
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
	 * @param array  $options Scheduling options: `priority` (int, 0-255, default 10), `unique` (bool, default false), `strict` (bool, default false), `queue` (a SchedulerQueue value).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 * @throws \RuntimeException When `strict` is set and a requested option cannot take effect natively.
	 */
	public function schedule_cron( int $timestamp, string $cron_schedule, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->select_queue( $options );
		$this->assert_options_supported( $queue, $options );

		if ( $queue instanceof OptionsAwareQueueInterface ) {
			return (int) $queue->schedule_cron( $timestamp, $cron_schedule, $hook, $args, $group, $this->extract_queue_options( $options ) );
		}

		if ( $this->has_pending_match( $queue, $options, $hook, $args, $group ) ) {
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
	 * @param array  $options Only `queue` (a SchedulerQueue value) applies here.
	 * @return void
	 */
	public function cancel( string $hook, array $args = array(), string $group = '', array $options = array() ): void {
		$this->select_queue( $this->normalize_options( $options, __FUNCTION__ ) )->cancel( $hook, $args, $group );
	}

	/**
	 * Cancel all occurrences of a scheduled action.
	 *
	 * @since 11.3.0
	 *
	 * @param string $hook The hook that the job will trigger.
	 * @param array  $args Args that would have been passed to the job.
	 * @param string $group The group the job is assigned to.
	 * @param array  $options Only `queue` (a SchedulerQueue value) applies here.
	 * @return void
	 */
	public function cancel_all( string $hook, array $args = array(), string $group = '', array $options = array() ): void {
		$this->select_queue( $this->normalize_options( $options, __FUNCTION__ ) )->cancel_all( $hook, $args, $group );
	}

	/**
	 * Get the date and time of the next pending occurrence of a scheduled action.
	 *
	 * @since 11.3.0
	 *
	 * @param string     $hook The hook that the job will trigger.
	 * @param array|null $args Args that would have been passed to the job. Null matches any args.
	 * @param string     $group The group the job is assigned to.
	 * @param array      $options Only `queue` (a SchedulerQueue value) applies here.
	 * @return \WC_DateTime|null The next occurrence, or null when nothing is pending.
	 */
	public function get_next( string $hook, ?array $args = null, string $group = '', array $options = array() ): ?\WC_DateTime {
		$next = $this->select_queue( $this->normalize_options( $options, __FUNCTION__ ) )->get_next( $hook, $args, $group );

		return $next instanceof \WC_DateTime ? $next : null;
	}

	/**
	 * Find scheduled actions.
	 *
	 * @since 11.3.0
	 *
	 * @param array  $args Search criteria, as accepted by WC_Queue_Interface::search().
	 * @param string $return_format OBJECT, ARRAY_A, or 'ids'.
	 * @param array  $options Only `queue` (a SchedulerQueue value) applies here.
	 * @return array
	 */
	public function search( array $args = array(), string $return_format = OBJECT, array $options = array() ): array {
		$results = $this->select_queue( $this->normalize_options( $options, __FUNCTION__ ) )->search( $args, $return_format );

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
	 * @param array      $options Only `queue` (a SchedulerQueue value) applies here.
	 * @return bool
	 */
	public function has_scheduled_action( string $hook, ?array $args = null, string $group = '', array $options = array() ): bool {
		$queue = $this->select_queue( $this->normalize_options( $options, __FUNCTION__ ) );

		if ( $queue instanceof OptionsAwareQueueInterface ) {
			return (bool) $queue->has_scheduled_action( $hook, $args, $group );
		}

		return $queue->get_next( $hook, $args, $group ) instanceof \WC_DateTime;
	}

	/**
	 * Whether a capability takes effect natively on the path a call would take.
	 *
	 * Capabilities are the QueueCapability values, and each is also the scheduling option that
	 * requests it. Every capability needs an options-aware queue; the stock queue additionally
	 * needs Action Scheduler 3.5.0 for `unique` and 3.6.0 for `priority`. Where this returns false
	 * a non-strict call still does its best: uniqueness is emulated with a pending-action check
	 * that cannot see in-progress actions and is not atomic, and the priority is dropped. A strict
	 * call throws instead. Unknown capabilities are never supported.
	 *
	 * @since 11.3.0
	 *
	 * @param string $capability A QueueCapability value.
	 * @param array  $options Only `queue` (a SchedulerQueue value) applies here, to pick the path.
	 * @return bool
	 */
	public function supports( string $capability, array $options = array() ): bool {
		return $this->is_supported_by( $this->select_queue( $this->normalize_options( $options, __FUNCTION__ ) ), $capability );
	}

	/**
	 * The version of the Action Scheduler copy that won the load race, or null when none is loaded.
	 *
	 * Copies register at `plugins_loaded` priority 0, so the answer is null before that hook has run.
	 *
	 * @since 11.3.0
	 *
	 * @return string|null
	 */
	public function get_action_scheduler_version(): ?string {
		if ( ! did_action( 'plugins_loaded' ) || ! class_exists( \ActionScheduler_Versions::class ) ) {
			return null;
		}

		$version = \ActionScheduler_Versions::instance()->latest_version();

		return is_string( $version ) && '' !== $version ? $version : null;
	}

	/**
	 * Whether scheduling calls can be made yet.
	 *
	 * The queue class is chosen at `plugins_loaded`. When the active queue is the stock one or extends
	 * WC_Action_Queue, and so delegates to Action Scheduler, Action Scheduler must also have loaded its
	 * functions and, on copies that report it, initialised its data store, which happens at `init`.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		if ( ! did_action( 'plugins_loaded' ) ) {
			return false;
		}

		if ( ! $this->get_active_queue() instanceof \WC_Action_Queue ) {
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
	 * Bring the options into a known shape: `priority`, `unique`, `strict` and `queue`, nothing else.
	 *
	 * The priority goes through the same normaliser the stock queue uses, so both public entry points
	 * coerce it identically.
	 *
	 * @param array  $options Options as given by the caller.
	 * @param string $method  Public method the options were passed to, named in the notice for an invalid queue.
	 * @return array
	 */
	private function normalize_options( array $options, string $method ): array {
		$priority = OptionsAwareActionQueue::normalize_priority( $options[ QueueCapability::PRIORITY ] ?? self::DEFAULT_PRIORITY );

		$queue = $options['queue'] ?? SchedulerQueue::ACTIVE;
		if ( ! in_array( $queue, SchedulerQueue::get_all(), true ) ) {
			wc_doing_it_wrong( __CLASS__ . '::' . $method, __( 'The queue option must be one of the SchedulerQueue values. Falling back to the active queue.', 'woocommerce' ), '11.3.0' );
			$queue = SchedulerQueue::ACTIVE;
		}

		return array(
			QueueCapability::PRIORITY => $priority,
			QueueCapability::UNIQUE   => ! empty( $options[ QueueCapability::UNIQUE ] ),
			'strict'                  => ! empty( $options['strict'] ),
			'queue'                   => $queue,
		);
	}

	/**
	 * Throw when a strict call asks for an option the selected queue cannot honour natively.
	 *
	 * Only options the caller actually asked for count: `unique` when true, `priority` when it
	 * differs from the default.
	 *
	 * @param \WC_Queue_Interface $queue The selected queue.
	 * @param array               $options Normalised options.
	 * @return void
	 * @throws \RuntimeException When `strict` is set and a requested option is unsupported.
	 */
	private function assert_options_supported( \WC_Queue_Interface $queue, array $options ): void {
		if ( ! $options['strict'] ) {
			return;
		}

		$requested = array();
		if ( $options[ QueueCapability::UNIQUE ] ) {
			$requested[] = QueueCapability::UNIQUE;
		}
		if ( self::DEFAULT_PRIORITY !== $options[ QueueCapability::PRIORITY ] ) {
			$requested[] = QueueCapability::PRIORITY;
		}

		foreach ( $requested as $capability ) {
			if ( ! $this->is_supported_by( $queue, $capability ) ) {
				throw new \RuntimeException(
					esc_html(
						sprintf(
							/* translators: 1: scheduling option name, for example "unique", 2: the reason it cannot take effect */
							__( 'The %1$s scheduling option cannot take effect: %2$s.', 'woocommerce' ),
							$capability,
							$this->describe_unsupported( $queue, $capability )
						)
					)
				);
			}
		}
	}

	/**
	 * Whether a queue has a capability natively.
	 *
	 * Unknown capabilities are never supported, whatever the queue says.
	 *
	 * @param \WC_Queue_Interface $queue The queue a call would go through.
	 * @param string              $capability A QueueCapability value.
	 * @return bool
	 */
	private function is_supported_by( \WC_Queue_Interface $queue, string $capability ): bool {
		if ( ! in_array( $capability, QueueCapability::get_all(), true ) || ! $queue instanceof OptionsAwareQueueInterface ) {
			return false;
		}

		return (bool) $queue->supports( $capability );
	}

	/**
	 * Explain why a queue does not honour an option, for the strict-mode exception message.
	 *
	 * @param \WC_Queue_Interface $queue The queue a call would go through.
	 * @param string              $capability A QueueCapability value.
	 * @return string
	 */
	private function describe_unsupported( \WC_Queue_Interface $queue, string $capability ): string {
		if ( $queue instanceof OptionsAwareActionQueue ) {
			return sprintf(
				/* translators: 1: loaded Action Scheduler version, 2: scheduling option name, 3: the Action Scheduler version that added it */
				__( 'the loaded Action Scheduler (%1$s) predates %2$s support, which needs %3$s', 'woocommerce' ),
				$this->get_action_scheduler_version() ?? __( 'unknown version', 'woocommerce' ),
				$capability,
				QueueCapability::UNIQUE === $capability ? '3.5.0' : '3.6.0'
			);
		}

		/* translators: %s: class name of the active queue */
		return sprintf( __( 'the active queue (%s) does not implement OptionsAwareQueueInterface', 'woocommerce' ), get_class( $queue ) );
	}

	/**
	 * The options a queue receives. The `strict` and `queue` keys are the scheduler's own and never leave it.
	 *
	 * @param array $options Normalised options.
	 * @return array
	 */
	private function extract_queue_options( array $options ): array {
		return array(
			QueueCapability::PRIORITY => $options[ QueueCapability::PRIORITY ],
			QueueCapability::UNIQUE   => $options[ QueueCapability::UNIQUE ],
		);
	}

	/**
	 * Whether a unique action requested on a plain queue already has a pending match.
	 *
	 * Only a WC_DateTime counts as a match. The base interface has no return type, and a custom queue
	 * may answer false rather than null when nothing is scheduled.
	 *
	 * @param \WC_Queue_Interface $queue The plain queue.
	 * @param array               $options Normalised options.
	 * @param string              $hook The hook to trigger.
	 * @param array               $args Arguments to pass when the hook triggers.
	 * @param string              $group The group to assign this job to.
	 * @return bool
	 */
	private function has_pending_match( \WC_Queue_Interface $queue, array $options, string $hook, array $args, string $group ): bool {
		return $options[ QueueCapability::UNIQUE ] && $queue->get_next( $hook, $args, $group ) instanceof \WC_DateTime;
	}

	/**
	 * Pick the queue a call should go through.
	 *
	 * @param array $options Normalised options.
	 * @return \WC_Queue_Interface
	 */
	private function select_queue( array $options ): \WC_Queue_Interface {
		return SchedulerQueue::DEFAULT === $options['queue'] ? $this->get_default_queue() : $this->get_active_queue();
	}

	/**
	 * Get the queue WooCommerce is configured with, honouring `woocommerce_queue_class`.
	 *
	 * @return \WC_Queue_Interface
	 */
	private function get_active_queue(): \WC_Queue_Interface {
		return $this->legacy_proxy->get_instance_of( \WC_Queue_Interface::class );
	}

	/**
	 * Get the stock queue, regardless of `woocommerce_queue_class`.
	 *
	 * @return OptionsAwareActionQueue
	 */
	private function get_default_queue(): OptionsAwareActionQueue {
		return $this->default_queue;
	}
}
