<?php

/**
 * Class ActionScheduler_QueueRunner
 */
class ActionScheduler_QueueRunner extends ActionScheduler_Abstract_QueueRunner {
	const WP_CRON_HOOK = 'action_scheduler_run_queue';

	const WP_CRON_SCHEDULE = 'every_minute';

	/** @var ActionScheduler_QueueRunner  */
	private static $runner = null;

	/**
	 * The created time.
	 *
	 * Represents when the queue runner was constructed and used when calculating how long a PHP request has been running.
	 * For this reason it should be as close as possible to the PHP request start time.
	 *
	 * @var int
	 */
	private $created_time;

	/**
	 * @return ActionScheduler_QueueRunner
	 * @codeCoverageIgnore
	 */
	public static function instance() {
		if ( empty(self::$runner) ) {
			$class = apply_filters('action_scheduler_queue_runner_class', 'ActionScheduler_QueueRunner');
			self::$runner = new $class();
		}
		return self::$runner;
	}

	/**
	 * ActionScheduler_QueueRunner constructor.
	 *
	 * @param ActionScheduler_Store             $store
	 * @param ActionScheduler_FatalErrorMonitor $monitor
	 * @param ActionScheduler_QueueCleaner      $cleaner
	 */
	public function __construct( ActionScheduler_Store $store = null, ActionScheduler_FatalErrorMonitor $monitor = null, ActionScheduler_QueueCleaner $cleaner = null ) {
		parent::__construct( $store, $monitor, $cleaner );
		$this->created_time = microtime( true );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function init() {

		add_filter( 'cron_schedules', array( self::instance(), 'add_wp_cron_schedule' ) );

		if ( !wp_next_scheduled(self::WP_CRON_HOOK) ) {
			$schedule = apply_filters( 'action_scheduler_run_schedule', self::WP_CRON_SCHEDULE );
			wp_schedule_event( time(), $schedule, self::WP_CRON_HOOK );
		}

		add_action( self::WP_CRON_HOOK, array( self::instance(), 'run' ) );
	}

	public function run() {
		@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
		@set_time_limit( apply_filters( 'action_scheduler_queue_runner_time_limit', 600 ) );
		do_action( 'action_scheduler_before_process_queue' );
		$this->run_cleanup();
		$count = 0;
		if ( $this->store->get_claim_count() < $this->get_allowed_concurrent_batches() ) {
			$batch_size = apply_filters( 'action_scheduler_queue_runner_batch_size', 25 );
			$count = $this->do_batch( $batch_size );
		}

		do_action( 'action_scheduler_after_process_queue' );
		return $count;
	}

	protected function do_batch( $size = 100 ) {
		$claim = $this->store->stake_claim($size);
		$this->monitor->attach($claim);
		$processed_actions      = 0;
		$maximum_execution_time = $this->get_maximum_execution_time();

		foreach ( $claim->get_actions() as $action_id ) {
			if ( 0 !== $processed_actions ) {
				$time_elapsed            = $this->get_execution_time();
				$average_processing_time = $time_elapsed / $processed_actions;

				// Bail early if the time it has taken to process this batch is approaching the maximum execution time.
				if ( $time_elapsed + ( $average_processing_time * 2 ) > $maximum_execution_time ) {
					break;
				}
			}

			// bail if we lost the claim
			if ( ! in_array( $action_id, $this->store->find_actions_by_claim_id( $claim->get_id() ) ) ) {
				break;
			}
			$this->process_action( $action_id );
			$processed_actions++;
		}
		$this->store->release_claim($claim);
		$this->monitor->detach();
		$this->clear_caches();
		return $processed_actions;
	}

	/**
	 * Running large batches can eat up memory, as WP adds data to its object cache.
	 *
	 * If using a persistent object store, this has the side effect of flushing that
	 * as well, so this is disabled by default. To enable:
	 *
	 * add_filter( 'action_scheduler_queue_runner_flush_cache', '__return_true' );
	 */
	protected function clear_caches() {
		if ( ! wp_using_ext_object_cache() || apply_filters( 'action_scheduler_queue_runner_flush_cache', false ) ) {
			wp_cache_flush();
		}
	}

	public function add_wp_cron_schedule( $schedules ) {
		$schedules['every_minute'] = array(
			'interval' => 60, // in seconds
			'display'  => __( 'Every minute' ),
		);

		return $schedules;
	}

	/**
	 * Get the maximum number of seconds a batch can run for.
	 *
	 * @return int The number of seconds.
	 */
	protected function get_maximum_execution_time() {

		// There are known hosts with a strict 60 second execution time.
		if ( defined( 'WPENGINE_ACCOUNT' ) || defined( 'PANTHEON_ENVIRONMENT' ) ) {
			$maximum_execution_time = 60;
		} elseif ( false !== strpos( getenv( 'HOSTNAME' ), '.siteground.' ) ) {
			$maximum_execution_time = 120;
		} else {
			$maximum_execution_time = ini_get( 'max_execution_time' );
		}

		return absint( apply_filters( 'action_scheduler_maximum_execution_time', $maximum_execution_time ) );
	}

	/**
	 * Get the number of seconds a batch has run for.
	 *
	 * @return int The number of seconds.
	 */
	protected function get_execution_time() {
		$execution_time = microtime( true ) - $this->created_time;

		// Get the CPU time if the hosting environment uses it rather than wall-clock time to calculate a process's execution time.
		if ( function_exists( 'getrusage' ) && apply_filters( 'action_scheduler_use_cpu_execution_time', defined( 'PANTHEON_ENVIRONMENT' ) ) ) {
			$resource_usages = getrusage();

			if ( isset( $resource_usages['ru_stime.tv_usec'], $resource_usages['ru_stime.tv_usec'] ) ) {
				$execution_time = $resource_usages['ru_stime.tv_sec'] + ( $resource_usages['ru_stime.tv_usec'] / 1000000 );
			}
		}

		return $execution_time;
	}
}
