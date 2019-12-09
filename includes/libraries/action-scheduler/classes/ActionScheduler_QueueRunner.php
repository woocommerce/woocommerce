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
		ActionScheduler_Compatibility::raise_memory_limit();
		ActionScheduler_Compatibility::raise_time_limit( $this->get_time_limit() );
		do_action( 'action_scheduler_before_process_queue' );
		$this->run_cleanup();
		$processed_actions = 0;
		if ( $this->store->get_claim_count() < $this->get_allowed_concurrent_batches() ) {
			$batch_size = apply_filters( 'action_scheduler_queue_runner_batch_size', 25 );
			do {
				$processed_actions_in_batch = $this->do_batch( $batch_size );
				$processed_actions         += $processed_actions_in_batch;
			} while ( $processed_actions_in_batch > 0 && ! $this->batch_limits_exceeded( $processed_actions ) ); // keep going until we run out of actions, time, or memory
		}

		do_action( 'action_scheduler_after_process_queue' );
		return $processed_actions;
	}

	protected function do_batch( $size = 100 ) {
		$claim = $this->store->stake_claim($size);
		$this->monitor->attach($claim);
		$processed_actions = 0;

		foreach ( $claim->get_actions() as $action_id ) {
			// bail if we lost the claim
			if ( ! in_array( $action_id, $this->store->find_actions_by_claim_id( $claim->get_id() ) ) ) {
				break;
			}
			$this->process_action( $action_id );
			$processed_actions++;

			if ( $this->batch_limits_exceeded( $processed_actions ) ) {
				break;
			}
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
			'display'  => __( 'Every minute', 'woocommerce' ),
		);

		return $schedules;
	}
}
