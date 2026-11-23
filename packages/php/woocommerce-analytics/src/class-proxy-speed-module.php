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
 * Called by host platform code that also returns true from the
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
	 * Answer the current request and exit if it is a proxy request this module may serve.
	 *
	 * Returns without output otherwise, so WordPress continues loading and the REST route answers.
	 *
	 * @return void
	 */
	public static function maybe_serve() {
		if ( self::respond() ) {
			exit;
		}
	}

	/**
	 * Send the response to a proxy request this module may serve.
	 *
	 * @return bool Whether a response was sent.
	 */
	private static function respond() {
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
	 * @param string $body Raw request body.
	 * @return array{status: int, data: array} The response to send.
	 */
	private static function handle_events( $body ) {
		if ( '' === $body ) {
			return self::error_response( 'Empty request body' );
		}

		$events = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return self::error_response( 'Invalid JSON' );
		}

		$data = WC_Analytics_Tracking::record_client_events( $events );

		$data['is_proxy_speed_module'] = true;

		return array(
			'status' => $data['success'] ? 200 : 207,
			'data'   => $data,
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

		if ( ! is_string( $path ) || preg_match( '/[^A-Za-z0-9\-._~\/]/', $path ) ) {
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
	 * Only a prefix filter registered before this runs applies, which is why hosts call it on `muplugins_loaded`.
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
