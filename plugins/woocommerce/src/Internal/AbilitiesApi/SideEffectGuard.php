<?php
/**
 * Side effect guard class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Runs the steps of an ActionableAbility that come before the save with
 * database writes, email and outbound HTTP blocked. A step that tries one
 * makes the change fail, and nothing is saved.
 *
 * Writes to the WooCommerce and Action Scheduler log tables and to transients
 * are allowed. The guard does not see the object cache, files, PHP state or
 * HTTP clients that do not use the WordPress HTTP API.
 *
 * @since 11.3.0
 */
final class SideEffectGuard {

	/**
	 * Run steps with side effects blocked.
	 *
	 * @param string   $ability_name Ability name.
	 * @param callable $steps        Steps that must not change the store.
	 * @return mixed What the steps returned, or a WP_Error when they tried a side effect.
	 * @throws \Throwable When the steps throw without having tried a side effect.
	 */
	public static function run( string $ability_name, callable $steps ) {
		global $wpdb;

		$attempts   = array();
		$result     = null;
		$log_tables = '/^\s*INSERT\s+INTO\s+`?(' . preg_quote( $wpdb->prefix, '/' ) . 'woocommerce_log|' . preg_quote( $wpdb->prefix, '/' ) . 'actionscheduler_logs)\b/i';
		$transients = '/^\s*(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+`?' . preg_quote( $wpdb->options, '/' ) . '\b.*\'_(site_)?transient_/is';

		$query = static function ( $sql ) use ( &$attempts, $log_tables, $transients ) {
			if (
				is_string( $sql )
				&& preg_match( '/^\s*(?:\/\*.*?\*\/\s*)*(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE)\b/is', $sql )
				&& ! preg_match( $log_tables, $sql )
				&& ! preg_match( $transients, $sql )
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
			return new \WP_Error( 'woocommerce_ability_side_effect', 'Outbound HTTP is blocked before the save.' );
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
			sprintf( 'Ability "%s" tried a side effect before the save. Nothing was saved.', $ability_name ),
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
			'woocommerce_ability_side_effect',
			sprintf(
				/* translators: %s: comma-separated list such as "a database write, an email". */
				__( 'The change was refused because a step before the save tried %s. Nothing was saved.', 'woocommerce' ),
				implode( ', ', array_intersect_key( $labels, $attempts ) )
			),
			array(
				'status'    => 500,
				'attempted' => array_keys( $attempts ),
			)
		);
	}
}
