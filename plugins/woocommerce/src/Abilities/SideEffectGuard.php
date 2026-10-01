<?php
/**
 * SideEffectGuard class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Runs code that must not have side effects with database writes, outgoing
 * email and outbound HTTP blocked. In-memory writes run validate(), apply(),
 * object validators and extension field handlers inside it. Use it to run
 * those steps for a preview.
 *
 * @since 11.3.0
 */
final class SideEffectGuard {

	/**
	 * Run the steps with side effects blocked.
	 *
	 * Database writes are dropped, wp_mail() returns false and HTTP requests
	 * return a WP_Error. When the steps tried any of them, the result is a
	 * `woocommerce_in_memory_write_side_effect` error and the attempts are
	 * logged. Guards are removed before this method returns or throws.
	 *
	 * @param callable $steps Code that must not change the store.
	 * @return mixed|\WP_Error What the steps returned, or an error when they tried a side effect.
	 * @throws \Throwable When the steps throw without having tried a side effect.
	 *
	 * @since 11.3.0
	 */
	public static function run( callable $steps ) {
		global $wpdb;

		$attempts = array();
		$result   = null;
		// A step may log: WC_Log_Handler_DB writes to woocommerce_log, and Action Scheduler logs to actionscheduler_logs. Neither changes store data.
		$log_tables = '/^\s*INSERT\s+INTO\s+`?(' . preg_quote( $wpdb->prefix, '/' ) . 'woocommerce_log|' . preg_quote( $wpdb->prefix, '/' ) . 'actionscheduler_logs)\b/i';

		$query = static function ( $sql ) use ( &$attempts, $log_tables ) {
			if (
				is_string( $sql )
				&& preg_match( '/^\s*(?:\/\*.*?\*\/\s*)*(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE)\b/is', $sql )
				&& ! preg_match( $log_tables, $sql )
			) {
				$attempts['database'][] = $sql;
				return '';
			}
			return $sql;
		};
		$mail  = static function ( $pre, $atts ) use ( &$attempts ) {
			$attempts['email'][] = $atts['subject'] ?? '';
			return false;
		};
		$http  = static function ( $pre, $args, $url ) use ( &$attempts ) {
			$attempts['http'][] = wp_parse_url( $url, PHP_URL_HOST );
			return new \WP_Error( 'woocommerce_in_memory_write_side_effect', 'Outbound HTTP is blocked here.' );
		};

		add_filter( 'query', $query, PHP_INT_MAX );
		add_filter( 'pre_wp_mail', $mail, PHP_INT_MAX, 2 );
		add_filter( 'pre_http_request', $http, PHP_INT_MAX, 3 );
		try {
			$result = $steps();
		} catch ( \Throwable $exception ) {
			if ( empty( $attempts ) ) {
				throw $exception;
			}
		} finally {
			remove_filter( 'query', $query, PHP_INT_MAX );
			remove_filter( 'pre_wp_mail', $mail, PHP_INT_MAX );
			remove_filter( 'pre_http_request', $http, PHP_INT_MAX );
		}

		if ( empty( $attempts ) ) {
			return $result;
		}

		wc_get_logger()->error(
			'A step that must not have side effects tried one. Nothing was saved.',
			array(
				'source'   => 'woocommerce-abilities',
				'attempts' => $attempts,
			)
		);

		$labels = array(
			'database' => __( 'a database write', 'woocommerce' ),
			'email'    => __( 'an email', 'woocommerce' ),
			'http'     => __( 'an HTTP request', 'woocommerce' ),
		);
		return new \WP_Error(
			'woocommerce_in_memory_write_side_effect',
			sprintf(
				/* translators: %s: comma-separated list such as "a database write, an email". */
				__( 'The change was refused because a step that must not have side effects tried %s. Nothing was saved.', 'woocommerce' ),
				implode( ', ', array_intersect_key( $labels, $attempts ) )
			),
			array(
				'status'    => 500,
				'attempted' => array_keys( $attempts ),
			)
		);
	}
}
