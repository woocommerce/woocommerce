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

	abstract public function init();

}
 