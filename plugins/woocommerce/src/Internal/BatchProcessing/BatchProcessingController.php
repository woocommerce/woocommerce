<?php
/**
 * This class is a helper intended to handle data processings that need to happen in batches in a deferred way.
 * It abstracts away the nuances of (re)scheduling actions and dealing with errors.
 *
 * Usage:
 *
 * 1. Create a class that implements BatchProcessorInterface.
 *    The class must either be registered in the dependency injection container, or have a public parameterless constructor,
 *    or an instance must be provided via the 'woocommerce_get_batch_processor' filter.
 * 2. Whenever there's data to be processed invoke the 'enqueue_processor' method in this class,
 *    passing the class name of the processor.
 *
 * That's it, processing will be performed in batches inside scheduled actions; enqueued processors will only
 * be dequeued once they notify that no more items are left to process (or when `force_clear_all_processes` is invoked).
 * Failed batches will be retried after a while.
 *
 * There are also a few public methods to get the list of currently enqueued processors
 * and to check if a given processor is enqueued/actually scheduled.
 */

namespace Automattic\WooCommerce\Internal\BatchProcessing;

/**
 * Class BatchProcessingController
 *
 * @package Automattic\WooCommerce\Internal\BatchProcessing.
 */
class BatchProcessingController {
	/*
	 * Identifier of a "watchdog" action that will schedule a processing action
	 * for any processor that is enqueued but not yet scheduled
	 * (because it's been just enqueued or because it threw an error while processing a batch),
	 * that's one single action that reschedules itself continuously.
	 */
	const WATCHDOG_ACTION_NAME = 'wc_schedule_pending_batch_processes';

	/*
	 * Identifier of the action that will do the actual batch processing.
	 * There's one action per enqueued processor that will keep rescheduling itself
	 * as long as there are still pending items to process
	 * (except if there's an error that caused no items to be processed at all).
	 */
	const PROCESS_SINGLE_BATCH_ACTION_NAME = 'wc_run_batch_process';

	const ENQUEUED_PROCESSORS_OPTION_NAME = 'wc_pending_batch_processes';
	const ACTION_GROUP                    = 'wc_batch_processes';

	/**
	 * Maximum number of failures per processor before it gets dequeued.
	 */
	const FAILING_PROCESS_MAX_ATTEMPTS_DEFAULT = 5;

	/**
	 * Seconds to wait for the enqueued-processors lock before giving up and proceeding without it.
	 *
	 * Kept small deliberately: enqueue_processor() runs on the 'shutdown' hook of nearly every request under
	 * continuous HPOS background sync, so a long wait could pile requests up. The guarded critical section is just
	 * a couple of option queries, so one second is ample headroom for an uncontended writer while bounding the
	 * worst-case stall when the lock is genuinely contended (in which case the mutation falls back to running
	 * unguarded, which is no worse than the historical behavior).
	 *
	 * @since 11.0.0
	 */
	const ENQUEUED_PROCESSORS_LOCK_TIMEOUT = 1;

	/**
	 * Instance of WC_Logger class.
	 *
	 * @var \WC_Logger_Interface
	 */
	private $logger;

	/**
	 * BatchProcessingController constructor.
	 *
	 * Schedules the necessary actions to process batches.
	 */
	public function __construct() {
		add_action(
			self::WATCHDOG_ACTION_NAME,
			function () {
				$this->handle_watchdog_action();
			}
		);

		add_action(
			self::PROCESS_SINGLE_BATCH_ACTION_NAME,
			function ( $batch_process ) {
				$this->process_next_batch_for_single_processor( $batch_process );
			},
			10,
			2
		);

		add_action(
			'shutdown',
			function () {
				$this->remove_or_retry_failed_processors();
			}
		);

		$this->logger = wc_get_logger();
	}

	/**
	 * Enqueue a processor so that it will get batch processing requests from within scheduled actions.
	 *
	 * @param string $processor_class_name Fully qualified class name of the processor, must implement `BatchProcessorInterface`.
	 */
	public function enqueue_processor( string $processor_class_name ): void {
		/*
		 * Fast path: if the processor is already enqueued and the stored list is clean, there is nothing to write.
		 * This avoids taking the lock and re-reading the option on the hot path, which matters because
		 * DataSynchronizer::handle_continuous_background_sync() calls this on the 'shutdown' hook of every request
		 * when HPOS background sync runs in continuous mode. The check uses the (possibly request-cached) value;
		 * the authoritative re-read happens inside the critical section below only when an update is actually needed.
		 */
		if ( $this->enqueued_list_needs_update( $this->get_enqueued_processors(), $processor_class_name ) ) {
			$this->mutate_enqueued_processors(
				function ( array $pending_updates ) use ( $processor_class_name ): array {
					$deduplicated_updates = $this->sanitize_processor_list( $pending_updates );
					if ( ! in_array( $processor_class_name, $deduplicated_updates, true ) ) {
						$deduplicated_updates[] = $processor_class_name;
					}
					return $deduplicated_updates;
				}
			);
		}

		$this->schedule_watchdog_action( false, true );
	}

	/**
	 * Reduce a processor list to unique, non-empty class-name strings, preserving order.
	 *
	 * The enqueued-processors option is a plain serialized array, so a corrupted option (or the historical
	 * duplicate-accumulation bug) can leave it holding duplicates or non-string values. Sanitizing before any
	 * comparison keeps the mutators safe and heals the stored list on the next write: in particular array_diff()
	 * string-casts its operands and would fatal on an object entry in PHP 8, so removals run on the sanitized list.
	 *
	 * @since 11.0.0
	 *
	 * @param array $processors Raw processor list as read from the option.
	 * @return array Sanitized list of unique class-name strings.
	 */
	private function sanitize_processor_list( array $processors ): array {
		$sanitized = array();
		$seen      = array();
		foreach ( $processors as $value ) {
			if ( is_string( $value ) && ! isset( $seen[ $value ] ) ) {
				$seen[ $value ] = true;
				$sanitized[]    = $value;
			}
		}
		return $sanitized;
	}

	/**
	 * Determine whether the stored enqueued-processors list needs to be rewritten to include a given processor
	 * or to heal pre-existing corruption (duplicates or non-string entries).
	 *
	 * @since 11.0.0
	 *
	 * @param array  $processors           Current list of enqueued processors.
	 * @param string $processor_class_name Fully qualified class name of the processor to ensure is enqueued.
	 *
	 * @return bool True if the list should be rewritten, false if it is already clean and contains the processor.
	 */
	private function enqueued_list_needs_update( array $processors, string $processor_class_name ): bool {
		$seen           = array();
		$contains_class = false;
		foreach ( $processors as $value ) {
			if ( ! is_string( $value ) || isset( $seen[ $value ] ) ) {
				// Non-string entry or duplicate: the list needs healing.
				return true;
			}
			$seen[ $value ] = true;
			if ( $value === $processor_class_name ) {
				$contains_class = true;
			}
		}

		return ! $contains_class;
	}

	/**
	 * Run a read-modify-write of the enqueued-processors option inside a short-lived critical section.
	 *
	 * The list is stored in a single option and mutated by several code paths (including the per-request
	 * 'shutdown' enqueue from continuous HPOS background sync), so an unguarded read-modify-write can lose
	 * updates under concurrency: two requests read the same list, each writes its own version, and the later
	 * write silently drops the other's change. This serializes those mutations with a MySQL named lock and
	 * re-reads the freshest persisted value inside the lock so the mutator always operates on current state.
	 *
	 * The lock is best-effort: if it cannot be acquired (timeout, an environment without GET_LOCK, or a multi-server
	 * database layout — e.g. HyperDB/LudicrousDB — where the GET_LOCK SELECT is routed to a different connection
	 * than the write) the mutation still proceeds, which is no worse than the previous lock-free behavior. The
	 * fresh re-read inside the section narrows the race window even when the lock provides no real exclusion, and
	 * the watchdog reconciles any residual divergence on its next run.
	 *
	 * @since 11.0.0
	 *
	 * @param callable $mutator Receives the current list (array of class-name strings) and returns the new list.
	 *
	 * @return array The list as persisted (the mutator's return value).
	 */
	private function mutate_enqueued_processors( callable $mutator ): array {
		// Resolve the lock name once so acquire and release always target the exact same named lock, even if a
		// hook fired during the write (e.g. by update_option()) were to mutate $wpdb->prefix mid-section.
		$lock_name     = $this->get_enqueued_processors_lock_name();
		$lock_acquired = $this->acquire_enqueued_processors_lock( $lock_name );
		try {
			/*
			 * Drop any request-cached copy so the read below reflects writes committed by concurrent requests
			 * that landed after this request first read the option. Both the per-option entry AND the shared
			 * 'notoptions' entry must be cleared: when the option does not yet exist (first enqueue, or after a
			 * corrupted option was deleted), get_option() short-circuits on a stale 'notoptions' hit and would
			 * otherwise return the default empty list even though a concurrent request just created the row.
			 */
			wp_cache_delete( self::ENQUEUED_PROCESSORS_OPTION_NAME, 'options' );
			wp_cache_delete( 'notoptions', 'options' );
			$current = $this->get_enqueued_processors();
			$updated = $mutator( $current );
			if ( $updated !== $current ) {
				$this->set_enqueued_processors( $updated );
			}
			return $updated;
		} finally {
			if ( $lock_acquired ) {
				$this->release_enqueued_processors_lock( $lock_name );
			}
		}
	}

	/**
	 * Build the MySQL named-lock identifier for the enqueued-processors critical section.
	 *
	 * GET_LOCK names are scoped to the whole MySQL server (shared across databases), so the lock is namespaced
	 * to this install to avoid unrelated sites on the same server contending. Lock names are capped at 64
	 * characters, so the install-specific part is hashed.
	 *
	 * @since 11.0.0
	 *
	 * @return string Lock name.
	 */
	private function get_enqueued_processors_lock_name(): string {
		global $wpdb;
		$db_name = defined( 'DB_NAME' ) ? DB_NAME : '';
		return 'wc_pending_batch_processes_' . md5( $wpdb->prefix . $db_name );
	}

	/**
	 * Acquire the enqueued-processors named lock, waiting up to ENQUEUED_PROCESSORS_LOCK_TIMEOUT seconds.
	 *
	 * Failures (no $wpdb, GET_LOCK unavailable, or a database error) are swallowed and reported as "not acquired"
	 * so the caller can fall back to its best-effort unguarded path rather than fataling — this runs on the
	 * 'shutdown' hook of essentially every request under continuous background sync.
	 *
	 * @since 11.0.0
	 *
	 * @param string $lock_name The named-lock identifier to acquire.
	 * @return bool True if the lock was acquired (and must be released by the caller), false otherwise.
	 */
	private function acquire_enqueued_processors_lock( string $lock_name ): bool {
		global $wpdb;
		if ( ! $wpdb instanceof \wpdb ) {
			return false;
		}

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$acquired = $wpdb->get_var(
				$wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, self::ENQUEUED_PROCESSORS_LOCK_TIMEOUT )
			);
		} catch ( \Throwable $e ) {
			return false;
		}

		return '1' === (string) $acquired;
	}

	/**
	 * Release the enqueued-processors named lock previously acquired by acquire_enqueued_processors_lock().
	 *
	 * @since 11.0.0
	 *
	 * @param string $lock_name The named-lock identifier to release (the same value passed to acquire).
	 */
	private function release_enqueued_processors_lock( string $lock_name ): void {
		global $wpdb;
		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$query = $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name );
		if ( ! is_string( $query ) ) {
			return;
		}

		try {
			/*
			 * $query is built by $wpdb->prepare() above; it is assigned to a variable only so it can be type-checked
			 * before being passed to query() (prepare() returns string|null), hence the NotPrepared suppression.
			 */
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $query );
		} catch ( \Throwable $e ) {
			// Best-effort: the lock is released automatically when the database session ends regardless.
			return;
		}
	}

	/**
	 * Schedule the watchdog action.
	 *
	 * @param bool $with_delay Whether to delay the action execution. Should be true when rescheduling, false when enqueueing.
	 * @param bool $unique     Whether to make the action unique.
	 */
	private function schedule_watchdog_action( bool $with_delay = false, bool $unique = false ): void {
		$time = time();
		if ( $with_delay ) {
			/**
			 * Modify the delay interval for the batch processor's watchdog events.
			 *
			 * @since 8.2.0
			 *
			 * @param int $delay Time, in seconds, before the watchdog process will run. Defaults to 3600 (1 hour).
			 */
			$time += apply_filters( 'woocommerce_batch_processor_watchdog_delay_seconds', HOUR_IN_SECONDS );
		}

		if ( ! as_has_scheduled_action( self::WATCHDOG_ACTION_NAME ) ) {
			as_schedule_single_action(
				$time,
				self::WATCHDOG_ACTION_NAME,
				array(),
				self::ACTION_GROUP,
				$unique
			);
		}
	}

	/**
	 * Schedule a processing action for all the processors that are enqueued but not scheduled
	 * (because they have just been enqueued, or because the processing for a batch failed).
	 */
	private function handle_watchdog_action(): void {
		$pending_processes = $this->get_enqueued_processors();
		if ( empty( $pending_processes ) ) {
			return;
		}
		foreach ( $pending_processes as $process_name ) {
			if ( ! $this->is_scheduled( $process_name ) ) {
				$this->schedule_batch_processing( $process_name );
			}
		}
		$this->schedule_watchdog_action( true );
	}

	/**
	 * Process a batch for a single processor, and handle any required rescheduling or state cleanup.
	 *
	 * @param string $processor_class_name Fully qualified class name of the processor.
	 *
	 * @throws \Exception If error occurred during batch processing.
	 */
	private function process_next_batch_for_single_processor( string $processor_class_name ): void {
		if ( ! $this->is_enqueued( $processor_class_name ) ) {
			return;
		}

		$batch_processor = $this->get_processor_instance( $processor_class_name );
		$error           = $this->process_next_batch_for_single_processor_core( $batch_processor );
		$still_pending   = count( $batch_processor->get_next_batch_to_process( 1 ) ) > 0;
		if ( ( $error instanceof \Exception ) ) {
			// The batch processing failed and no items were processed:
			// reschedule the processing with a delay, unless this is a repeatead failure.
			if ( $this->is_consistently_failing( $batch_processor ) ) {
				$this->log_consistent_failure( $batch_processor, $this->get_process_details( $batch_processor ) );
				$this->remove_processor( $processor_class_name );
			} else {
				$this->schedule_batch_processing( $processor_class_name, true );
			}

			throw $error;
		}
		if ( $still_pending ) {
			$this->schedule_batch_processing( $processor_class_name );
		} else {
			$this->dequeue_processor( $processor_class_name );
		}
	}

	/**
	 * Process a batch for a single processor, updating state and logging any error.
	 *
	 * @param BatchProcessorInterface $batch_processor Batch processor instance.
	 *
	 * @return null|\Exception Exception if error occurred, null otherwise.
	 */
	private function process_next_batch_for_single_processor_core( BatchProcessorInterface $batch_processor ): ?\Exception {
		$details    = $this->get_process_details( $batch_processor );
		$time_start = microtime( true );
		$batch      = $batch_processor->get_next_batch_to_process( $details['current_batch_size'] );
		if ( empty( $batch ) ) {
			return null;
		}
		try {
			$batch_processor->process_batch( $batch );
			$time_taken = microtime( true ) - $time_start;
			$this->update_processor_state( $batch_processor, $time_taken );
		} catch ( \Exception $exception ) {
			$time_taken = microtime( true ) - $time_start;
			$this->log_error( $exception, $batch_processor, $batch );
			$this->update_processor_state( $batch_processor, $time_taken, $exception );
			return $exception;
		}
		return null;
	}

	/**
	 * Get the current state for a given enqueued processor.
	 *
	 * @param BatchProcessorInterface $batch_processor Batch processor instance.
	 *
	 * @return array Current state for the processor, or a "blank" state if none exists yet.
	 */
	private function get_process_details( BatchProcessorInterface $batch_processor ): array {
		$defaults = array(
			'total_time_spent'    => 0,
			'current_batch_size'  => $batch_processor->get_default_batch_size(),
			'last_error'          => null,
			'recent_failures'     => 0,
			'batch_first_failure' => null,
			'batch_last_failure'  => null,
		);

		$process_details = get_option( $this->get_processor_state_option_name( $batch_processor ) );
		$process_details = wp_parse_args( is_array( $process_details ) ? $process_details : array(), $defaults );

		return $process_details;
	}

	/**
	 * Get the name of the option where we will be saving state for a given processor.
	 *
	 * @param BatchProcessorInterface|string $batch_processor Batch processor instance or class name.
	 *
	 * @return string Option name.
	 */
	private function get_processor_state_option_name( $batch_processor ): string {
		$class_name = is_a( $batch_processor, BatchProcessorInterface::class ) ? get_class( $batch_processor ) : $batch_processor;
		$class_md5  = md5( $class_name );
		// truncate the class name so we know that it will fit in the option name column along with md5 hash and prefix.
		$class_name = substr( $class_name, 0, 140 );
		return 'wc_batch_' . $class_name . '_' . $class_md5;
	}

	/**
	 * Update the state for a processor after a batch has completed processing.
	 *
	 * @param BatchProcessorInterface $batch_processor Batch processor instance.
	 * @param float                   $time_taken Time take by the batch to complete processing.
	 * @param \Exception|null         $last_error Exception object in processing the batch, if there was one.
	 */
	private function update_processor_state( BatchProcessorInterface $batch_processor, float $time_taken, ?\Exception $last_error = null ): void {
		$current_status                      = $this->get_process_details( $batch_processor );
		$current_status['total_time_spent'] += $time_taken;
		$current_status['last_error']        = null !== $last_error ? $last_error->getMessage() : null;

		if ( null !== $last_error ) {
			$current_status['recent_failures']    = ( $current_status['recent_failures'] ?? 0 ) + 1;
			$current_status['batch_last_failure'] = current_time( 'mysql' );

			if ( is_null( $current_status['batch_first_failure'] ) ) {
				$current_status['batch_first_failure'] = $current_status['batch_last_failure'];
			}
		} else {
			$current_status['recent_failures']     = 0;
			$current_status['batch_first_failure'] = null;
			$current_status['batch_last_failure']  = null;
		}

		update_option( $this->get_processor_state_option_name( $batch_processor ), $current_status, false );
	}

	/**
	 * Removes the option where we store state for a given processor.
	 *
	 * @since 9.1.0
	 *
	 * @param string $processor_class_name Fully qualified class name of the processor.
	 */
	private function clear_processor_state( string $processor_class_name ): void {
		delete_option( $this->get_processor_state_option_name( $processor_class_name ) );
	}

	/**
	 * Schedule a processing action for a single processor.
	 *
	 * @param string $processor_class_name Fully qualified class name of the processor.
	 * @param bool   $with_delay   Whether to schedule the action for immediate execution or for later.
	 */
	private function schedule_batch_processing( string $processor_class_name, bool $with_delay = false ): void {
		$time = $with_delay ? time() + MINUTE_IN_SECONDS : time();
		as_schedule_single_action( $time, self::PROCESS_SINGLE_BATCH_ACTION_NAME, array( $processor_class_name ) );
	}

	/**
	 * Check if a batch processing action is already scheduled for a given processor.
	 * Differs from `as_has_scheduled_action` in that this excludes actions in progress.
	 *
	 * @param string $processor_class_name Fully qualified class name of the batch processor.
	 *
	 * @return bool True if a batch processing action is already scheduled for the processor.
	 */
	public function is_scheduled( string $processor_class_name ): bool {
		return as_has_scheduled_action( self::PROCESS_SINGLE_BATCH_ACTION_NAME, array( $processor_class_name ) );
	}

	/**
	 * Get an instance of a processor given its class name.
	 *
	 * @param string $processor_class_name Full class name of the batch processor.
	 *
	 * @return BatchProcessorInterface Instance of batch processor for the given class.
	 * @throws \Exception If it's not possible to get an instance of the class.
	 */
	private function get_processor_instance( string $processor_class_name ): BatchProcessorInterface {

		$container = wc_get_container();
		$processor = $container->has( $processor_class_name ) ? $container->get( $processor_class_name ) : null;

		/**
		 * Filters the instance of a processor for a given class name.
		 *
		 * @param object|null $processor The processor instance given by the dependency injection container, or null if none was obtained.
		 * @param string $processor_class_name The full class name of the processor.
		 * @return BatchProcessorInterface|null The actual processor instance to use, or null if none could be retrieved.
		 *
		 * @since 6.8.0.
		 */
		$processor = apply_filters( 'woocommerce_get_batch_processor', $processor, $processor_class_name );
		if ( ! isset( $processor ) && class_exists( $processor_class_name ) ) {
			// This is a fallback for when the batch processor is not registered in the container.
			$processor = new $processor_class_name();
		}
		if ( ! is_a( $processor, BatchProcessorInterface::class ) ) {
			throw new \Exception( "Unable to initialize batch processor instance for $processor_class_name" );
		}
		return $processor;
	}

	/**
	 * Helper method to get list of all the enqueued processors.
	 *
	 * @return array List (of string) of the class names of the enqueued processors.
	 */
	public function get_enqueued_processors(): array {
		$enqueued_processors = get_option( self::ENQUEUED_PROCESSORS_OPTION_NAME, array() );

		if ( ! is_array( $enqueued_processors ) ) {
			$this->logger->error( 'Could not fetch list of processors. Clearing up queue.', array( 'source' => 'batch-processing' ) );
			delete_option( self::ENQUEUED_PROCESSORS_OPTION_NAME );
			$enqueued_processors = array();
		}

		return $enqueued_processors;
	}

	/**
	 * Dequeue a processor once it has no more items pending processing.
	 *
	 * @param string $processor_class_name Full processor class name.
	 */
	private function dequeue_processor( string $processor_class_name ): void {
		// Always resolve membership authoritatively inside the lock (no unguarded fast-path read): this runs when a
		// batch finishes, not on a per-request hot path, so correctness is preferred over skipping the lock.
		$removed = false;
		$this->mutate_enqueued_processors(
			function ( array $pending_processes ) use ( $processor_class_name, &$removed ): array {
				if ( ! in_array( $processor_class_name, $pending_processes, true ) ) {
					return $pending_processes;
				}
				$removed = true;
				return array_values( array_diff( $this->sanitize_processor_list( $pending_processes ), array( $processor_class_name ) ) );
			}
		);

		if ( $removed ) {
			$this->clear_processor_state( $processor_class_name );
		}
	}

	/**
	 * Helper method to set the enqueued processor class names.
	 *
	 * @param array $processors List of full processor class names.
	 */
	private function set_enqueued_processors( array $processors ): void {
		update_option( self::ENQUEUED_PROCESSORS_OPTION_NAME, $processors, false );
	}

	/**
	 * Check if a particular processor is enqueued.
	 *
	 * @param string $processor_class_name Fully qualified class name of the processor.
	 *
	 * @return bool True if the processor is enqueued.
	 */
	public function is_enqueued( string $processor_class_name ): bool {
		return in_array( $processor_class_name, $this->get_enqueued_processors(), true );
	}

	/**
	 * Dequeue and de-schedule a processor instance so that it won't be processed anymore.
	 *
	 * @param string $processor_class_name Fully qualified class name of the processor.
	 * @return bool True if the processor has been dequeued, false if the processor wasn't enqueued (so nothing has been done).
	 */
	public function remove_processor( string $processor_class_name ): bool {
		// Resolve membership authoritatively inside the lock (no unguarded fast-path read). remove_processor() is
		// not a per-request hot path — the continuous-sync 'shutdown' handler enqueues rather than removes — so it
		// always takes the lock and re-reads the freshest list rather than acting on a possibly-stale cached copy.
		$was_enqueued = false;
		$this->mutate_enqueued_processors(
			function ( array $enqueued_processors ) use ( $processor_class_name, &$was_enqueued ): array {
				if ( ! in_array( $processor_class_name, $enqueued_processors, true ) ) {
					return $enqueued_processors;
				}
				$was_enqueued = true;
				return array_values( array_diff( $this->sanitize_processor_list( $enqueued_processors ), array( $processor_class_name ) ) );
			}
		);

		if ( ! $was_enqueued ) {
			return false;
		}

		/*
		 * The new list (which may now be empty) was persisted atomically inside the critical section above. Only the
		 * removed processor's own scheduling needs tearing down here, so we unschedule by class name rather than
		 * wiping every action. This intentionally does NOT unschedule the watchdog, even when the list is now empty:
		 * handle_watchdog_action() returns without rescheduling itself once the list is empty (so a lingering
		 * watchdog fires at most once more and then stops). force_clear_all_processes() is deliberately avoided: its
		 * own unguarded read-modify-write would clobber a processor that a concurrent request enqueued in the gap
		 * after this mutation committed — the exact lost-update race this change exists to prevent. Leaving the
		 * watchdog in place lets it pick up such a concurrent enqueue; in the narrow window where the watchdog is
		 * already mid-run, the 'shutdown' reconciler remove_or_retry_failed_processors() reschedules the
		 * enqueued-but-unscheduled processor on the next request.
		 */
		as_unschedule_all_actions( self::PROCESS_SINGLE_BATCH_ACTION_NAME, array( $processor_class_name ) );
		$this->clear_processor_state( $processor_class_name );

		return true;
	}

	/**
	 * Dequeues and de-schedules all the processors.
	 */
	public function force_clear_all_processes(): void {
		as_unschedule_all_actions( self::PROCESS_SINGLE_BATCH_ACTION_NAME );
		as_unschedule_all_actions( self::WATCHDOG_ACTION_NAME );

		foreach ( $this->get_enqueued_processors() as $processor ) {
			$this->clear_processor_state( $processor );
		}

		update_option( self::ENQUEUED_PROCESSORS_OPTION_NAME, array(), false );
	}

	/**
	 * Log an error that happened while processing a batch.
	 *
	 * @param \Exception              $error Exception object to log.
	 * @param BatchProcessorInterface $batch_processor Batch processor instance.
	 * @param array                   $batch Batch that was being processed.
	 */
	protected function log_error( \Exception $error, BatchProcessorInterface $batch_processor, array $batch ): void {
		$error_message = "Error processing batch for {$batch_processor->get_name()}: {$error->getMessage()}";
		$error_context = array(
			'exception' => $error,
			'source'    => 'batch-processing',
		);

		// Log only first and last, as the entire batch may be too big.
		if ( count( $batch ) > 0 ) {
			$error_context = array_merge(
				$error_context,
				array(
					'batch_start' => $batch[0],
					'batch_end'   => end( $batch ),
				)
			);
		}

		/**
		 * Filters the error message for a batch processing.
		 *
		 * @param string $error_message The error message that will be logged.
		 * @param \Exception $error The exception that was thrown by the processor.
		 * @param BatchProcessorInterface $batch_processor The processor that threw the exception.
		 * @param array $batch The batch that was being processed.
		 * @param array $error_context Context to be passed to the logging function.
		 * @return string The actual error message that will be logged.
		 *
		 * @since 6.8.0
		 */
		$error_message = apply_filters( 'wc_batch_processing_log_message', $error_message, $error, $batch_processor, $batch, $error_context );

		$this->logger->error( $error_message, $error_context );
	}

	/**
	 * Determines whether a given processor is consistently failing based on how many recent consecutive failures it has had.
	 *
	 * @since 9.1.0
	 *
	 * @param BatchProcessorInterface $batch_processor The processor that we want to check.
	 * @return boolean TRUE if processor is consistently failing. FALSE otherwise.
	 */
	private function is_consistently_failing( BatchProcessorInterface $batch_processor ): bool {
		$process_details = $this->get_process_details( $batch_processor );
		$max_attempts    = absint(
			/**
			 * Controls the failure threshold for batch processors. That is, the number of times we'll attempt to
			 * process a batch that has resulted in a failure. Once above this threshold, the processor won't be
			 * re-scheduled and will be removed from the queue.
			 *
			 * @since 9.1.0
			 *
			 * @param int $failure_threshold Maximum number of times for the processor to try processing a given batch.
			 * @param BatchProcessorInterface $batch_processor The processor instance.
			 * @param array $process_details Array with batch processor state.
			 */
			apply_filters(
				'wc_batch_processing_max_attempts',
				self::FAILING_PROCESS_MAX_ATTEMPTS_DEFAULT,
				$batch_processor,
				$process_details
			)
		);

		return absint( $process_details['recent_failures'] ?? 0 ) >= max( $max_attempts, 1 );
	}

	/**
	 * Creates log entry with details about a batch processor that is consistently failing.
	 *
	 * @since 9.1.0
	 *
	 * @param BatchProcessorInterface $batch_processor The batch processor instance.
	 * @param array                   $process_details Failing process details.
	 */
	private function log_consistent_failure( BatchProcessorInterface $batch_processor, array $process_details ): void {
		$this->logger->error(
			"Batch processor {$batch_processor->get_name()} appears to be failing consistently: {$process_details['recent_failures']} unsuccessful attempt(s). No further attempts will be made.",
			array(
				'source'        => 'batch-processing',
				'failures'      => $process_details['recent_failures'],
				'first_failure' => $process_details['batch_first_failure'],
				'last_failure'  => $process_details['batch_last_failure'],
			)
		);
	}

	/**
	 * Hooked onto 'shutdown'. This cleanup routine checks enqueued processors and whether they are scheduled or not to
	 * either re-eschedule them or remove them from the queue.
	 * This prevents stale states where Action Scheduler won't schedule any more attempts but we still report the
	 * processor as enqueued.
	 *
	 * @since 9.1.0
	 */
	private function remove_or_retry_failed_processors(): void {
		if ( ! did_action( 'wp_loaded' ) ) {
			return;
		}

		$last_error = error_get_last();
		if ( ! is_null( $last_error ) && in_array( $last_error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			return;
		}

		// The most efficient way to check for an existing action is to use `as_has_scheduled_action`, but in unusual
		// cases where another plugin has loaded a very old version of Action Scheduler, it may not be available to us.
		$has_scheduled_action = function_exists( 'as_has_scheduled_action') ? 'as_has_scheduled_action' : 'as_next_scheduled_action';

		if ( call_user_func( $has_scheduled_action, self::WATCHDOG_ACTION_NAME ) ) {
			return;
		}

		/*
		 * Sanitize before array_diff()/array_filter(): array_diff() string-casts its operands (fatal on an object
		 * entry in PHP 8) and is_scheduled() is typed string, so a corrupted option must be reduced to class-name
		 * strings first.
		 */
		$enqueued_processors    = $this->sanitize_processor_list( $this->get_enqueued_processors() );
		$unscheduled_processors = array_diff( $enqueued_processors, array_filter( $enqueued_processors, array( $this, 'is_scheduled' ) ) );

		foreach ( $unscheduled_processors as $processor ) {
			try {
				$instance = $this->get_processor_instance( $processor );
			} catch ( \Exception $e ) {
				continue;
			}

			$exception = new \Exception( 'Processor is enqueued but not scheduled. Background job was probably killed or marked as failed. Reattempting execution.' );
			$this->update_processor_state( $instance, 0, $exception );
			$this->log_error( $exception, $instance, array() );

			if ( $this->is_consistently_failing( $instance ) ) {
				$this->log_consistent_failure( $instance, $this->get_process_details( $instance ) );
				$this->remove_processor( $processor );
			} else {
				$this->schedule_batch_processing( $processor, true );
			}
		}
	}
}
