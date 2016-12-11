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
	 *     @type string $tag Optional. Tag will be available in log table. Default '';
	 * }
	 *
	 * @return bool True on success.
	 */
	public function handle( $timestamp, $level, $message, $context ) {

		if ( isset( $context['tag'] ) && $context['tag'] ) {
			$tag = $context['tag'];
		} else {
			$tag = '';
		}

		return $this->add( $timestamp, $level, $message, $tag, $context );
	}

	/**
	 * Add a log entry to chosen file.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param string $tag Log tag. Useful for filtering and sorting.
	 * @param array $context {
	 *     Context will be serialized and stored in database.
	 * }
	 *
	 * @return bool True if write was successful.
	 */
	public static function add( $timestamp, $level, $message, $tag, $context ) {
		global $wpdb;

		$insert = array(
			'timestamp' => date( 'Y-m-d H:i:s', $timestamp ),
			'level' => $level,
			'message' => $message,
			'tag' => $tag,
		);

		if ( ! empty( $context ) ) {
			$insert['context'] = serialize( $context );
		}

		return false !== $wpdb->insert( "{$wpdb->prefix}woocommerce_log", $insert );
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

}
