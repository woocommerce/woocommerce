<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles log entries by writing to database.
 *
 * @class          WC_Log_Handler_DB
 * @version        1.0.0
 * @package        WooCommerce/Classes/Log_Handlers
 * @category       Class
 * @author         WooThemes
 */
class WC_Log_Handler_DB extends WC_Log_Handler {

	/**
	 * Handle a log entry.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context {
	 *     Additional information for log handlers.
	 *
	 *     @type string $source Optional. Source will be available in log table.
	 *                  If no source is provided, attempt to provide sensible default.
	 * }
	 *
	 * @see WC_Log_Handler_DB::get_log_source() for default source.
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $timestamp, $level, $message, $context ) {

		if ( isset( $context['source'] ) && $context['source'] ) {
			$source = $context['source'];
		} else {
			$source = $this->get_log_source();
		}

		return $this->add( $timestamp, $level, $message, $source, $context );
	}

	/**
	 * Add a log entry to chosen file.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param string $source Log source. Useful for filtering and sorting.
	 * @param array $context {
	 *     Context will be serialized and stored in database.
	 * }
	 *
	 * @return bool True if write was successful.
	 */
	protected static function add( $timestamp, $level, $message, $source, $context ) {
		global $wpdb;

		$insert = array(
			'timestamp' => date( 'Y-m-d H:i:s', $timestamp ),
			'level' => WC_Log_Levels::get_level_severity( $level ),
			'message' => $message,
			'source' => $source,
		);

		$format = array(
			'%s',
			'%d',
			'%s',
			'%s',
			'%s', // possible serialized context
		);

		if ( ! empty( $context ) ) {
			$insert['context'] = serialize( $context );
		}

		return false !== $wpdb->insert( "{$wpdb->prefix}woocommerce_log", $insert, $format );
	}

	/**
	 * Clear all logs from the DB.
	 *
	 * @return bool True if flush was successful.
	 */
	public static function flush() {
		global $wpdb;

		return $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_log" );
	}

	/**
	 * Delete selected logs from DB.
	 *
	 * @param int|string|array Log ID or array of Log IDs to be deleted.
	 *
	 * @return bool
	 */
	public static function delete( $log_ids ) {
		global $wpdb;

		if ( ! is_array( $log_ids ) ) {
			$log_ids = array( $log_ids );
		}

		$format = array_fill( 0, count( $log_ids ), '%d' );

		$query_in = '(' . implode( ',', $format ) . ')';

		$query = $wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}woocommerce_log WHERE log_id IN {$query_in}",
			$log_ids
		);

		return $wpdb->query( $query );
	}

	/**
	 * Get appropriate source based on file name.
	 *
	 * Try to provide an appropriate source in case none is provided.
	 *
	 * @return string Text to use as log source. "" (empty string) if none is found.
	 */
	protected static function get_log_source() {
		static $ignore_files = array( 'class-wc-log-handler-db', 'class-wc-logger' );

		/**
		 * PHP < 5.3.6 correct behavior
		 * @see http://php.net/manual/en/function.debug-backtrace.php#refsect1-function.debug-backtrace-parameters
		 */
		if ( defined( 'DEBUG_BACKTRACE_IGNORE_ARGS' ) ) {
			$debug_backtrace_arg = DEBUG_BACKTRACE_IGNORE_ARGS;
		} else {
			$debug_backtrace_arg = false;
		}

		$trace = debug_backtrace( $debug_backtrace_arg );
		foreach ( $trace as $t ) {
			if ( isset( $t['file'] ) ) {
				$filename = pathinfo( $t['file'], PATHINFO_FILENAME );
				if ( ! in_array( $filename, $ignore_files ) ) {
					return $filename;
				}
			}
		}

		return '';
	}

}
