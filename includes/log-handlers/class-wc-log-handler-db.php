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
	 * @return bool False if value was not handled and true if value was handled.
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
			'level' => WC_Log_Levels::get_level_severity( $level ),
			'message' => $message,
			'tag' => $tag,
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

}
