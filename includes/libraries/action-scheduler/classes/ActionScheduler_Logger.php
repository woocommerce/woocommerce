<?php

/**
 * Class ActionScheduler_Logger
 * @codeCoverageIgnore
 */
abstract class ActionScheduler_Logger {
	private static $logger = NULL;

	/**
	 * @return ActionScheduler_Logger
	 */
	public static function instance() {
		if ( empty(self::$logger) ) {
			$class = apply_filters('action_scheduler_logger_class', 'ActionScheduler_wpCommentLogger');
			self::$logger = new $class();
		}
		return self::$logger;
	}

	/**
	 * @param string $action_id
	 * @param string $message
	 * @param DateTime $date
	 *
	 * @return string The log entry ID
	 */
	abstract public function log( $action_id, $message, DateTime $date = NULL );

	/**
	 * @param string $entry_id
	 *
	 * @return ActionScheduler_LogEntry
	 */
	abstract public function get_entry( $entry_id );

	/**
	 * @param string $action_id
	 *
	 * @return ActionScheduler_LogEntry[]
	 */
	abstract public function get_logs( $action_id );


	/**
	 * @codeCoverageIgnore
	 */
	public function init() {
		add_action( 'action_scheduler_stored_action', array( $this, 'log_stored_action' ), 10, 1 );
		add_action( 'action_scheduler_canceled_action', array( $this, 'log_canceled_action' ), 10, 1 );
		add_action( 'action_scheduler_before_execute', array( $this, 'log_started_action' ), 10, 1 );
		add_action( 'action_scheduler_after_execute', array( $this, 'log_completed_action' ), 10, 1 );
		add_action( 'action_scheduler_failed_execution', array( $this, 'log_failed_action' ), 10, 2 );
		add_action( 'action_scheduler_failed_action', array( $this, 'log_timed_out_action' ), 10, 2 );
		add_action( 'action_scheduler_unexpected_shutdown', array( $this, 'log_unexpected_shutdown' ), 10, 2 );
		add_action( 'action_scheduler_reset_action', array( $this, 'log_reset_action' ), 10, 1 );
		add_action( 'action_scheduler_execution_ignored', array( $this, 'log_ignored_action' ), 10, 1 );
		add_action( 'action_scheduler_failed_fetch_action', array( $this, 'log_failed_fetch_action' ), 10, 1 );
	}

	public function log_stored_action( $action_id ) {
		$this->log( $action_id, __( 'action created', 'woocommerce' ) );
	}

	public function log_canceled_action( $action_id ) {
		$this->log( $action_id, __( 'action canceled', 'woocommerce' ) );
	}

	public function log_started_action( $action_id ) {
		$this->log( $action_id, __( 'action started', 'woocommerce' ) );
	}

	public function log_completed_action( $action_id ) {
		$this->log( $action_id, __( 'action complete', 'woocommerce' ) );
	}

	public function log_failed_action( $action_id, Exception $exception ) {
		$this->log( $action_id, sprintf( __( 'action failed: %s', 'woocommerce' ), $exception->getMessage() ) );
	}

	public function log_timed_out_action( $action_id, $timeout ) {
		$this->log( $action_id, sprintf( __( 'action timed out after %s seconds', 'woocommerce' ), $timeout ) );
	}

	public function log_unexpected_shutdown( $action_id, $error ) {
		if ( ! empty( $error ) ) {
			$this->log( $action_id, sprintf( __( 'unexpected shutdown: PHP Fatal error %s in %s on line %s', 'woocommerce' ), $error['message'], $error['file'], $error['line'] ) );
		}
	}

	public function log_reset_action( $action_id ) {
		$this->log( $action_id, __( 'action reset', 'woocommerce' ) );
	}

	public function log_ignored_action( $action_id ) {
		$this->log( $action_id, __( 'action ignored', 'woocommerce' ) );
	}

	public function log_failed_fetch_action( $action_id ) {
		$this->log( $action_id, __( 'There was a failure fetching this action', 'woocommerce' ) );
	}
}
