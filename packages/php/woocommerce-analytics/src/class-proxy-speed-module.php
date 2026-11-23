<?php
/**
 * Proxy speed module for WooCommerce Analytics.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;

/**
 * Answers proxy tracking requests at the MU-plugin stage, before regular plugins load.
 *
 * Called from the auto-installed MU-plugin, or from host platform code that registers the
 * `woocommerce_analytics_proxy_speed_module_host_provided` filter.
 *
 * @since 0.19.0
 */
class Proxy_Speed_Module {

	/**
	 * Path of the proxy request.
	 *
	 * @var string
	 */
	const PROXY_REQUEST_PATH = 'woocommerce-analytics/v1/track';

	/**
	 * Answer the current request if it is a proxy request this module may serve.
	 *
	 * The caller must exit when this returns true, and let WordPress continue loading otherwise.
	 *
	 * @return bool Whether the request was answered.
	 */
	public static function maybe_serve() {
		if ( ! self::is_proxy_request() ) {
			return false;
		}

		// The WP Consent API has not loaded yet, so let the REST route apply the stored deny.
		if ( self::is_statistics_consent_denied() ) {
			return false;
		}

		// Unauthorized means "do not accelerate", not "do not serve": only the REST route can tell a
		// disabled feature from a revoked module, so fall through and let it answer.
		if ( ! self::is_authorized() ) {
			return false;
		}

		try {
			// Match the superglobals the REST route sees.
			wp_magic_quotes();

			$response = self::handle_events( (string) file_get_contents( 'php://input' ) );
			WC_Analytics_Tracking::send_batched_pixels();
		} catch ( \Throwable $e ) {
			error_log( 'WooCommerce Analytics Proxy Speed Module: Uncaught error while handling the request: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$response = array(
				'status' => 500,
				'data'   => array(
					'success' => false,
					'error'   => 'Internal server error while processing analytics events.',
				),
			);
		}

		if ( ! headers_sent() ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Cache-Control: no-cache, must-revalidate' );
			http_response_code( $response['status'] );
		}

		echo wp_json_encode( $response['data'], JSON_UNESCAPED_SLASHES );

		return true;
	}

	/**
	 * Record the events in a proxy request body, leaving their pixels queued.
	 *
	 * @internal
	 *
	 * @param string $body Raw request body.
	 * @return array{status: int, data: array} The response to send.
	 */
	public static function handle_events( $body ) {
		if ( '' === $body ) {
			return self::error_response( 'Empty request body' );
		}

		$events = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return self::error_response( 'Invalid JSON' );
		}

		// Wrap a single event object or an unexpected scalar.
		if ( ! is_array( $events ) || isset( $events['event_name'] ) ) {
			$events = array( $events );
		}

		if ( count( $events ) > WC_Analytics_Tracking::MAX_CLIENT_EVENTS_PER_REQUEST ) {
			$events = array_slice( $events, 0, WC_Analytics_Tracking::MAX_CLIENT_EVENTS_PER_REQUEST, true );
		}

		$results    = array();
		$has_errors = false;

		foreach ( $events as $index => $event ) {
			if ( empty( $event ) || ! is_array( $event ) ) {
				$results[ $index ] = array(
					'success' => false,
					'error'   => 'Invalid event format',
				);
				$has_errors        = true;
				continue;
			}

			$event_name = $event['event_name'] ?? null;
			$properties = $event['properties'] ?? array();

			if ( ! $event_name || ! is_string( $event_name ) || ! is_array( $properties ) ) {
				$results[ $index ] = array(
					'success' => false,
					'error'   => 'Missing event_name or invalid properties',
				);
				$has_errors        = true;
				continue;
			}

			$result = WC_Analytics_Tracking::record_client_event( $event_name, $properties );

			if ( is_wp_error( $result ) ) {
				$results[ $index ] = array(
					'success' => false,
					'error'   => $result->get_error_message(),
				);
				$has_errors        = true;
				continue;
			}

			$results[ $index ] = array( 'success' => true );
		}

		return array(
			'status' => $has_errors ? 207 : 200,
			'data'   => array(
				'success'               => ! $has_errors,
				'results'               => $results,
				'is_proxy_speed_module' => true,
			),
		);
	}

	/**
	 * Build a 400 response.
	 *
	 * @param string $message Error message.
	 * @return array{status: int, data: array}
	 */
	private static function error_response( $message ) {
		return array(
			'status' => 400,
			'data'   => array(
				'success' => false,
				'error'   => $message,
			),
		);
	}

	/**
	 * Whether the current request is a POST to the proxy endpoint.
	 *
	 * @return bool
	 */
	private static function is_proxy_request() {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'POST' !== $method ) {
			return false;
		}

		$raw_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path    = is_string( $raw_uri ) ? wp_parse_url( $raw_uri, PHP_URL_PATH ) : null;

		if ( ! is_string( $path ) || '' === $path || preg_match( '/[^A-Za-z0-9\-._~\/]/', $path ) ) {
			return false;
		}

		$normalized_path = rtrim( $path, '/' );
		$proxy_suffix    = '/' . self::PROXY_REQUEST_PATH;

		return strlen( $normalized_path ) >= strlen( $proxy_suffix )
			&& substr( $normalized_path, -strlen( $proxy_suffix ) ) === $proxy_suffix;
	}

	/**
	 * Whether the WP Consent API cookie stores a statistics deny.
	 *
	 * Unlike Consent_Manager, this reads the cookie whatever the consent type, which is not known yet.
	 * Only a prefix filter added by an earlier MU-plugin applies this early.
	 *
	 * @return bool
	 */
	private static function is_statistics_consent_denied() {
		$prefix = apply_filters( 'wp_consent_cookie_prefix', 'wp_consent' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Reads a WP Consent API filter.
		if ( ! is_string( $prefix ) ) {
			return false;
		}

		$cookie_name = $prefix . '_statistics';

		return isset( $_COOKIE[ $cookie_name ] ) && 'deny' === $_COOKIE[ $cookie_name ];
	}

	/**
	 * Whether this site authorized the module to serve.
	 *
	 * Features cannot answer this early, because no plugin has registered its filters yet. Only an
	 * explicit yes serves, so a network-wide module cannot answer for a site that never authorized it.
	 *
	 * @return bool
	 */
	private static function is_authorized() {
		return 'yes' === get_option( Woocommerce_Analytics::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
	}
}
