<?php
/**
 * Side effect guard class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Runs the steps of a dry run with database writes, email and outbound HTTP
 * dropped, and reports what the steps tried. The real run does these when it
 * saves, so the dry run lists them as side effects.
 *
 * Writes to the WooCommerce and Action Scheduler log tables and to transients
 * are allowed. The guard does not see the object cache, files, PHP state or
 * HTTP clients that do not use the WordPress HTTP API.
 *
 * @since 11.3.0
 */
final class SideEffectGuard {

	/**
	 * Run steps with side effects dropped.
	 *
	 * @param callable $steps Steps that must not change the store.
	 * @return array{0: mixed, 1: string[]} What the steps returned, and a sentence for each kind of side effect they tried.
	 */
	public static function run( callable $steps ): array {
		global $wpdb;

		$attempts   = array();
		$log_tables = '/^\s*INSERT\s+INTO\s+`?(' . preg_quote( $wpdb->prefix, '/' ) . 'woocommerce_log|' . preg_quote( $wpdb->prefix, '/' ) . 'actionscheduler_logs)\b/i';
		$transients = '/^\s*(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+`?' . preg_quote( $wpdb->options, '/' ) . '\b.*\'_(site_)?transient_/is';

		$query = static function ( $sql ) use ( &$attempts, $log_tables, $transients ) {
			if (
				is_string( $sql )
				&& preg_match( '/^\s*(?:\/\*.*?\*\/\s*)*(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE)\b/is', $sql )
				&& ! preg_match( $log_tables, $sql )
				&& ! preg_match( $transients, $sql )
			) {
				$attempts['database'] = __( 'Writes to the database before the save.', 'woocommerce' );
				return '';
			}
			return $sql;
		};
		$mail  = static function () use ( &$attempts ) {
			$attempts['email'] = __( 'Sends an email before the save.', 'woocommerce' );
			return false;
		};
		$http  = static function ( $pre, $args, $url ) use ( &$attempts ) {
			$host = (string) wp_parse_url( $url, PHP_URL_HOST );
			/* translators: %s: host name, such as example.com. */
			$attempts[ 'http:' . $host ] = sprintf( __( 'Makes an HTTP request to %s before the save.', 'woocommerce' ), $host );
			return new \WP_Error( 'woocommerce_ability_dry_run', 'Outbound HTTP is dropped in a dry run.' );
		};

		add_filter( 'query', $query, PHP_INT_MAX );
		add_filter( 'pre_wp_mail', $mail, PHP_INT_MAX );
		add_filter( 'pre_http_request', $http, PHP_INT_MAX, 3 );
		try {
			$result = $steps();
		} finally {
			remove_filter( 'query', $query, PHP_INT_MAX );
			remove_filter( 'pre_wp_mail', $mail, PHP_INT_MAX );
			remove_filter( 'pre_http_request', $http, PHP_INT_MAX );
		}

		return array( $result, array_values( $attempts ) );
	}
}
