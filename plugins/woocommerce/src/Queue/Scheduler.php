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
 * `unique` reach queues that implement OptionsAwareQueueInterface natively, as the caller gave
 * them: each queue validates and defaults them itself, the stock queue clamping the priority to
 * 0-255 with a default of 10. On a plain WC_Queue_Interface the priority is dropped and
 * uniqueness is emulated with `get_next()`, which cannot see in-progress actions and is not atomic.
 *
 * Every operation first checks `is_ready()`. When the queue is not ready, a non-strict call raises
 * a doing-it-wrong notice and returns a neutral value without touching the queue; a strict call
 * throws.
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
 * `when_ready()` runs a callback once the queue can accept calls, and `ensure_recurring()` and
 * `ensure_cron()` keep a recurring action scheduled by re-asserting it, as a unique action,
 * whenever Action Scheduler asks for recurring actions to be ensured. Like `add_action()`, those
 * registrations are per request and have to be made on every request.
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
	 * Recurring actions registered through ensure_recurring() and ensure_cron() in this request.
	 *
	 * @var array[]
	 */
	private $recurring_registrations = array();

	/**
	 * The callback attached to Action Scheduler's ensure hook, once a registration exists.
	 *
	 * @var \Closure|null
	 */
	private $recurring_registry_handler;

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
	 * @param array  $options Scheduling options: `priority` (lower runs first; the queue applies its own range and default, 10 on the stock queue), `unique` (bool, default false), `strict` (bool, default false), `queue` (a SchedulerQueue value).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 * @throws \RuntimeException When `strict` is set and the queue is not ready or a requested option cannot take effect natively.
	 */
	public function add( string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return 0;
		}
		$this->assert_options_supported( $queue, $options );

		if ( $queue instanceof OptionsAwareQueueInterface ) {
			return (int) $queue->add( $hook, $args, $group, $this->extract_queue_options( $options ) );
		}

		if ( $this->has_pending_match( $queue, $options, $hook, $args, $group ) ) {
			return 0;
		}

		return (int) $queue->add( $hook, $args, $group );
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
	 * @param array  $options Scheduling options: `priority` (lower runs first; the queue applies its own range and default, 10 on the stock queue), `unique` (bool, default false), `strict` (bool, default false), `queue` (a SchedulerQueue value).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 * @throws \RuntimeException When `strict` is set and the queue is not ready or a requested option cannot take effect natively.
	 */
	public function schedule_single( int $timestamp, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return 0;
		}
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
	 * @param array  $options Scheduling options: `priority` (lower runs first; the queue applies its own range and default, 10 on the stock queue), `unique` (bool, default false), `strict` (bool, default false), `queue` (a SchedulerQueue value).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 * @throws \RuntimeException When `strict` is set and the queue is not ready or a requested option cannot take effect natively.
	 */
	public function schedule_recurring( int $timestamp, int $interval_in_seconds, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return 0;
		}
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
	 * @param array  $options Scheduling options: `priority` (lower runs first; the queue applies its own range and default, 10 on the stock queue), `unique` (bool, default false), `strict` (bool, default false), `queue` (a SchedulerQueue value).
	 * @return int The action ID, or 0 when the action was not scheduled.
	 * @throws \RuntimeException When `strict` is set and the queue is not ready or a requested option cannot take effect natively.
	 */
	public function schedule_cron( int $timestamp, string $cron_schedule, string $hook, array $args = array(), string $group = '', array $options = array() ): int {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return 0;
		}
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
	 * Keep a recurring action scheduled.
	 *
	 * The registration is re-asserted, as a unique action so a pending or in-progress instance
	 * blocks a duplicate, whenever Action Scheduler fires `action_scheduler_ensure_recurring_actions`.
	 * Action Scheduler fires it from a daily recurring action of its own on every store, whatever
	 * queue WooCommerce is configured with. If it has already fired in this request the action is
	 * scheduled at once. Registrations are per request, like `add_action()`, and have to be made
	 * on every request early enough to be in place when the queue runs.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp When the first instance of the job will run.
	 * @param int    $interval_in_seconds How long to wait between runs.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options as for schedule_recurring(); `unique` is always true.
	 * @throws \RuntimeException When `strict` is set and the action cannot be scheduled as requested.
	 */
	public function ensure_recurring( int $timestamp, int $interval_in_seconds, string $hook, array $args = array(), string $group = '', array $options = array() ): void {
		$this->register_recurring(
			array(
				'type'      => 'recurring',
				'timestamp' => $timestamp,
				'schedule'  => $interval_in_seconds,
				'hook'      => $hook,
				'args'      => $args,
				'group'     => $group,
				'options'   => $options,
			)
		);
	}

	/**
	 * Keep an action that recurs on a cron-like schedule scheduled.
	 *
	 * Works like ensure_recurring(), see there for when the registration takes effect.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $timestamp The schedule will start on or after this time.
	 * @param string $cron_schedule A cron-like schedule string.
	 * @param string $hook The hook to trigger.
	 * @param array  $args Arguments to pass when the hook triggers.
	 * @param string $group The group to assign this job to.
	 * @param array  $options Scheduling options as for schedule_cron(); `unique` is always true.
	 * @throws \RuntimeException When `strict` is set and the action cannot be scheduled as requested.
	 */
	public function ensure_cron( int $timestamp, string $cron_schedule, string $hook, array $args = array(), string $group = '', array $options = array() ): void {
		$this->register_recurring(
			array(
				'type'      => 'cron',
				'timestamp' => $timestamp,
				'schedule'  => $cron_schedule,
				'hook'      => $hook,
				'args'      => $args,
				'group'     => $group,
				'options'   => $options,
			)
		);
	}

	/**
	 * Cancel the next occurrence of a scheduled action.
	 *
	 * @since 11.3.0
	 *
	 * @param string     $hook    The hook that the job will trigger.
	 * @param array|null $args    Args that would have been passed to the job. Null matches any args.
	 * @param string     $group   The group the job is assigned to.
	 * @param array      $options `queue` (a SchedulerQueue value) and `strict` apply here.
	 * @return void
	 * @throws \RuntimeException When `strict` is set and the queue is not ready.
	 */
	public function cancel( string $hook, ?array $args = array(), string $group = '', array $options = array() ): void {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return;
		}

		$queue->cancel( $hook, $args, $group );
	}

	/**
	 * Cancel all occurrences of a scheduled action.
	 *
	 * @since 11.3.0
	 *
	 * @param string     $hook    The hook that the job will trigger.
	 * @param array|null $args    Args that would have been passed to the job. Null matches any args.
	 * @param string     $group   The group the job is assigned to.
	 * @param array      $options `queue` (a SchedulerQueue value) and `strict` apply here.
	 * @return void
	 * @throws \RuntimeException When `strict` is set and the queue is not ready.
	 */
	public function cancel_all( string $hook, ?array $args = array(), string $group = '', array $options = array() ): void {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return;
		}

		$queue->cancel_all( $hook, $args, $group );
	}

	/**
	 * Get the date and time of the next pending occurrence of a scheduled action.
	 *
	 * @since 11.3.0
	 *
	 * @param string     $hook    The hook that the job will trigger.
	 * @param array|null $args    Args that would have been passed to the job. Null matches any args.
	 * @param string     $group   The group the job is assigned to.
	 * @param array      $options `queue` (a SchedulerQueue value) and `strict` apply here.
	 * @return \WC_DateTime|null The next occurrence, or null when nothing is pending.
	 * @throws \RuntimeException When `strict` is set and the queue is not ready.
	 */
	public function get_next( string $hook, ?array $args = null, string $group = '', array $options = array() ): ?\WC_DateTime {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return null;
		}

		$next = $queue->get_next( $hook, $args, $group );

		return $next instanceof \WC_DateTime ? $next : null;
	}

	/**
	 * Find scheduled actions.
	 *
	 * @since 11.3.0
	 *
	 * @param array  $args Search criteria, as accepted by WC_Queue_Interface::search().
	 * @param string $return_format OBJECT, ARRAY_A, or 'ids'.
	 * @param array  $options `queue` (a SchedulerQueue value) and `strict` apply here.
	 * @return array
	 * @throws \RuntimeException When `strict` is set and the queue is not ready.
	 */
	public function search( array $args = array(), string $return_format = OBJECT, array $options = array() ): array {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return array();
		}

		$results = $queue->search( $args, $return_format );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Count the actions matching the given criteria.
	 *
	 * An options-aware queue counts in its backend without hydrating actions. A plain queue has no
	 * count operation, so the count falls back to the size of a `search()` for IDs, which still
	 * avoids loading each action.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args Search criteria, as accepted by WC_Queue_Interface::search().
	 * @param array $options `queue` (a SchedulerQueue value) and `strict` apply here.
	 * @return int The number of matching actions, or 0 when the queue is not ready.
	 * @throws \RuntimeException When `strict` is set and the queue is not ready.
	 */
	public function count( array $args = array(), array $options = array() ): int {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return 0;
		}

		if ( $queue instanceof OptionsAwareQueueInterface ) {
			return (int) $queue->count( $args );
		}

		$ids = $queue->search( $args, 'ids' );

		return is_array( $ids ) ? count( $ids ) : 0;
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
	 * @param array      $options `queue` (a SchedulerQueue value) and `strict` apply here.
	 * @return bool
	 * @throws \RuntimeException When `strict` is set and the queue is not ready.
	 */
	public function has_scheduled_action( string $hook, ?array $args = null, string $group = '', array $options = array() ): bool {
		$options = $this->normalize_options( $options, __FUNCTION__ );
		$queue   = $this->ready_queue( $options, __FUNCTION__ );
		if ( null === $queue ) {
			return false;
		}

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
	 * Whether calls can be made yet on the path a call would take.
	 *
	 * False before `plugins_loaded`, when the queue class has not been chosen. After that an
	 * options-aware queue answers for itself. A plain queue that extends WC_Action_Queue delegates
	 * to Action Scheduler, so it is judged by the stock queue's answer, which covers Action
	 * Scheduler having loaded its functions and initialised its data store at `init`. Any other
	 * plain queue is ready.
	 *
	 * @since 11.3.0
	 *
	 * @param array $options Only `queue` (a SchedulerQueue value) applies here, to pick the path.
	 * @return bool
	 */
	public function is_ready( array $options = array() ): bool {
		if ( ! did_action( 'plugins_loaded' ) ) {
			return false;
		}

		return $this->is_queue_ready( $this->select_queue( $this->normalize_options( $options, __FUNCTION__ ) ) );
	}

	/**
	 * Run a callback as soon as the queue is ready, now if it already is.
	 *
	 * Otherwise the callback waits for the next event after which the queue may be ready:
	 * `plugins_loaded` before it has fired; on an Action Scheduler-backed path `action_scheduler_init`,
	 * which fires right after the data store initialises (copies older than 3.6.0 lack it and get
	 * `init`); then `init` and `wp_loaded`. Readiness is re-checked when the event fires, moving on
	 * to the next event if needed. Once no event is left to wait for, a notice is raised and the
	 * callback is dropped rather than attached to an event that will never fire.
	 *
	 * @since 11.3.0
	 *
	 * @param callable $callback Called with no arguments once the queue is ready.
	 * @param array    $options Only `queue` (a SchedulerQueue value) applies here, to pick the path.
	 */
	public function when_ready( callable $callback, array $options = array() ): void {
		if ( $this->is_ready( $options ) ) {
			$callback();
			return;
		}

		$hook = $this->next_readiness_hook( $options );
		if ( null === $hook ) {
			wc_doing_it_wrong(
				__METHOD__,
				__( 'The queue is not ready and no event is left in this request to wait for, so the callback was dropped. Check Scheduler::is_ready() first.', 'woocommerce' ),
				'11.3.0'
			);
			return;
		}

		add_action(
			$hook,
			function () use ( $callback, $options ) {
				$this->when_ready( $callback, $options );
			}
		);
	}

	/**
	 * Whether a queue can accept calls, see is_ready() for the rules.
	 *
	 * @param \WC_Queue_Interface $queue The queue a call would go through.
	 * @return bool
	 */
	private function is_queue_ready( \WC_Queue_Interface $queue ): bool {
		if ( $queue instanceof OptionsAwareQueueInterface ) {
			return (bool) $queue->is_ready();
		}

		if ( $queue instanceof \WC_Action_Queue ) {
			return $this->default_queue->is_ready();
		}

		return true;
	}

	/**
	 * Pick the queue for a call once it can accept one.
	 *
	 * Checks `plugins_loaded` before touching the queue at all, since choosing it earlier would pin
	 * the singleton before `woocommerce_queue_class` callbacks are attached. When not ready, a strict
	 * call throws and a non-strict call raises a notice naming the public method and gets null.
	 *
	 * @param array  $options Normalised options.
	 * @param string $method  Public method being called, named in the notice.
	 * @return \WC_Queue_Interface|null The queue to use, or null when not ready.
	 * @throws \RuntimeException When `strict` is set and the queue is not ready.
	 */
	private function ready_queue( array $options, string $method ): ?\WC_Queue_Interface {
		if ( did_action( 'plugins_loaded' ) ) {
			$queue = $this->select_queue( $options );
			if ( $this->is_queue_ready( $queue ) ) {
				return $queue;
			}
		}

		$message = __( 'The queue is not ready to accept calls yet. Check Scheduler::is_ready() and call this after init.', 'woocommerce' );

		if ( $options['strict'] ) {
			throw new \RuntimeException( esc_html( $message ) );
		}

		wc_doing_it_wrong( __CLASS__ . '::' . $method, $message, '11.3.0' );

		return null;
	}

	/**
	 * Bring the options into a known shape: `unique`, `strict` and `queue` always, `priority` only
	 * when the caller gave one, and nothing else.
	 *
	 * The priority is kept as given: the queue that receives it applies its own validation and
	 * default, so the scheduler never has to know what a backend considers valid.
	 *
	 * @param array  $options Options as given by the caller.
	 * @param string $method  Public method the options were passed to, named in the notice for an invalid queue.
	 * @return array
	 */
	private function normalize_options( array $options, string $method ): array {
		$queue = $options['queue'] ?? SchedulerQueue::ACTIVE;
		if ( ! in_array( $queue, SchedulerQueue::get_all(), true ) ) {
			wc_doing_it_wrong( __CLASS__ . '::' . $method, __( 'The queue option must be one of the SchedulerQueue values. Falling back to the active queue.', 'woocommerce' ), '11.3.0' );
			$queue = SchedulerQueue::ACTIVE;
		}

		$normalized = array(
			QueueCapability::UNIQUE => ! empty( $options[ QueueCapability::UNIQUE ] ),
			'strict'                => ! empty( $options['strict'] ),
			'queue'                 => $queue,
		);

		if ( array_key_exists( QueueCapability::PRIORITY, $options ) ) {
			$normalized[ QueueCapability::PRIORITY ] = $options[ QueueCapability::PRIORITY ];
		}

		return $normalized;
	}

	/**
	 * Throw when a strict call asks for an option the selected queue cannot honour natively.
	 *
	 * Only options the caller actually asked for count: `unique` when true, `priority` whenever the
	 * caller passed one, whatever its value.
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
		if ( array_key_exists( QueueCapability::PRIORITY, $options ) ) {
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

		if ( $queue instanceof OptionsAwareQueueInterface ) {
			/* translators: 1: class name of the active queue, 2: scheduling option name */
			return sprintf( __( 'the active queue (%1$s) reports no native support for %2$s', 'woocommerce' ), get_class( $queue ), $capability );
		}

		/* translators: %s: class name of the active queue */
		return sprintf( __( 'the active queue (%s) does not implement OptionsAwareQueueInterface', 'woocommerce' ), get_class( $queue ) );
	}

	/**
	 * The options a queue receives: `priority` only when the caller gave one, and `unique`.
	 *
	 * The `strict` and `queue` keys are the scheduler's own and never leave it.
	 *
	 * @param array $options Normalised options.
	 * @return array
	 */
	private function extract_queue_options( array $options ): array {
		$queue_options = array();

		if ( array_key_exists( QueueCapability::PRIORITY, $options ) ) {
			$queue_options[ QueueCapability::PRIORITY ] = $options[ QueueCapability::PRIORITY ];
		}

		$queue_options[ QueueCapability::UNIQUE ] = $options[ QueueCapability::UNIQUE ];

		return $queue_options;
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
	 * The next event after which the queue may be ready, or null when none is left in this request.
	 *
	 * Before `plugins_loaded` the queue is not selected at all, since that would pin the singleton
	 * before `woocommerce_queue_class` callbacks are attached.
	 *
	 * @param array $options Options as given to when_ready().
	 * @return string|null
	 */
	private function next_readiness_hook( array $options ): ?string {
		if ( ! did_action( 'plugins_loaded' ) ) {
			return 'plugins_loaded';
		}

		$candidates = array();
		$queue      = $this->select_queue( $this->normalize_options( $options, 'when_ready' ) );
		if ( $queue instanceof \WC_Action_Queue && version_compare( $this->get_action_scheduler_version() ?? '0', '3.6.0', '>=' ) ) {
			$candidates[] = 'action_scheduler_init';
		}
		$candidates[] = 'init';
		$candidates[] = 'wp_loaded';

		foreach ( $candidates as $hook ) {
			if ( ! did_action( $hook ) ) {
				return $hook;
			}
		}

		return null;
	}

	/**
	 * Record a recurring registration, or schedule it now if Action Scheduler already asked for
	 * recurring actions in this request.
	 *
	 * @param array $registration The registration as built by ensure_recurring() or ensure_cron().
	 */
	private function register_recurring( array $registration ): void {
		if ( did_action( 'action_scheduler_ensure_recurring_actions' ) ) {
			$this->schedule_registration( $registration );
			return;
		}

		$this->recurring_registrations[] = $registration;

		if ( null === $this->recurring_registry_handler ) {
			$this->recurring_registry_handler = function () {
				foreach ( $this->recurring_registrations as $registration ) {
					$this->schedule_registration( $registration );
				}
			};
		}

		if ( false === has_action( 'action_scheduler_ensure_recurring_actions', $this->recurring_registry_handler ) ) {
			add_action( 'action_scheduler_ensure_recurring_actions', $this->recurring_registry_handler );
		}
	}

	/**
	 * Schedule a recurring registration as a unique action.
	 *
	 * @param array $registration The registration as built by ensure_recurring() or ensure_cron().
	 */
	private function schedule_registration( array $registration ): void {
		$options = array_merge( $registration['options'], array( QueueCapability::UNIQUE => true ) );

		if ( 'cron' === $registration['type'] ) {
			$this->schedule_cron( $registration['timestamp'], $registration['schedule'], $registration['hook'], $registration['args'], $registration['group'], $options );
			return;
		}

		$this->schedule_recurring( $registration['timestamp'], $registration['schedule'], $registration['hook'], $registration['args'], $registration['group'], $options );
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
