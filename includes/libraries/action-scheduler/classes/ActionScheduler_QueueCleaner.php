<?php

/**
 * Class ActionScheduler_QueueCleaner
 */
class ActionScheduler_QueueCleaner {

	/** @var int */
	protected $batch_size;

	/** @var ActionScheduler_Store */
	private $store = null;

	/**
	 * 31 days in seconds.
	 *
	 * @var int
	 */
	private $month_in_seconds = 2678400;

	/**
	 * Five minutes in seconds
	 *
	 * @var int
	 */
	private $five_minutes = 300;

	/**
	 * ActionScheduler_QueueCleaner constructor.
	 *
	 * @param ActionScheduler_Store $store      The store instance.
	 * @param int                   $batch_size The batch size.
	 */
	public function __construct( ActionScheduler_Store $store = null, $batch_size = 20 ) {
		$this->store = $store ? $store : ActionScheduler_Store::instance();
		$this->batch_size = $batch_size;
	}

	public function delete_old_actions() {
		$lifespan = apply_filters( 'action_scheduler_retention_period', $this->month_in_seconds );
		$cutoff = as_get_datetime_object($lifespan.' seconds ago');

		$statuses_to_purge = array(
			ActionScheduler_Store::STATUS_COMPLETE,
			ActionScheduler_Store::STATUS_CANCELED,
		);

		foreach ( $statuses_to_purge as $status ) {
			$actions_to_delete = $this->store->query_actions( array(
				'status'           => $status,
				'modified'         => $cutoff,
				'modified_compare' => '<=',
				'per_page'         => $this->get_batch_size(),
			) );

			foreach ( $actions_to_delete as $action_id ) {
				try {
					$this->store->delete_action( $action_id );
				} catch ( Exception $e ) {

					/**
					 * Notify 3rd party code of exceptions when deleting a completed action older than the retention period
					 *
					 * This hook provides a way for 3rd party code to log or otherwise handle exceptions relating to their
					 * actions.
					 *
					 * @since 2.0.0
					 *
					 * @param int $action_id The scheduled actions ID in the data store
					 * @param Exception $e The exception thrown when attempting to delete the action from the data store
					 * @param int $lifespan The retention period, in seconds, for old actions
					 * @param int $count_of_actions_to_delete The number of old actions being deleted in this batch
					 */
					do_action( 'action_scheduler_failed_old_action_deletion', $action_id, $e, $lifespan, count( $actions_to_delete ) );
				}
			}
		}
	}

	public function reset_timeouts() {
		$timeout = apply_filters( 'action_scheduler_timeout_period', $this->five_minutes );
		if ( $timeout < 0 ) {
			return;
		}
		$cutoff = as_get_datetime_object($timeout.' seconds ago');
		$actions_to_reset = $this->store->query_actions( array(
			'status'           => ActionScheduler_Store::STATUS_PENDING,
			'modified'         => $cutoff,
			'modified_compare' => '<=',
			'claimed'          => true,
			'per_page'         => $this->get_batch_size(),
		) );

		foreach ( $actions_to_reset as $action_id ) {
			$this->store->unclaim_action( $action_id );
			do_action( 'action_scheduler_reset_action', $action_id );
		}
	}

	public function mark_failures() {
		$timeout = apply_filters( 'action_scheduler_failure_period', $this->five_minutes );
		if ( $timeout < 0 ) {
			return;
		}
		$cutoff = as_get_datetime_object($timeout.' seconds ago');
		$actions_to_reset = $this->store->query_actions( array(
			'status'           => ActionScheduler_Store::STATUS_RUNNING,
			'modified'         => $cutoff,
			'modified_compare' => '<=',
			'per_page'         => $this->get_batch_size(),
		) );

		foreach ( $actions_to_reset as $action_id ) {
			$this->store->mark_failure( $action_id );
			do_action( 'action_scheduler_failed_action', $action_id, $timeout );
		}
	}

	/**
	 * Do all of the cleaning actions.
	 *
	 * @author Jeremy Pry
	 */
	public function clean() {
		$this->delete_old_actions();
		$this->reset_timeouts();
		$this->mark_failures();
	}

	/**
	 * Get the batch size for cleaning the queue.
	 *
	 * @author Jeremy Pry
	 * @return int
	 */
	protected function get_batch_size() {
		/**
		 * Filter the batch size when cleaning the queue.
		 *
		 * @param int $batch_size The number of actions to clean in one batch.
		 */
		return absint( apply_filters( 'action_scheduler_cleanup_batch_size', $this->batch_size ) );
	}
}
