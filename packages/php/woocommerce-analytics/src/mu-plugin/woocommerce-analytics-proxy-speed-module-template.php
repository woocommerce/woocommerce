<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: WooCommerce Analytics - Proxy Speed Module
 * Description: Speeds up WooCommerce Analytics' proxy by handling requests at MU-plugin stage and exiting early.
 * Plugin URI: https://woocommerce.com
 * Author: WooCommerce
 * Version: {{VERSION}}
 * Author URI: https://woocommerce.com
 *
 * Text Domain: woocommerce-analytics
 *
 * This module intercepts proxy tracking requests at the MU-plugin stage (before regular plugins load)
 * and handles them completely, then exits. This dramatically reduces response time by avoiding
 * the full WordPress plugin initialization.
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Analytics Proxy Speed Module
 */
class WooCommerceAnalyticsProxySpeed {

	/**
	 * Path of the proxy request.
	 *
	 * @var string
	 */
	const PROXY_REQUEST_PATH = 'woocommerce-analytics/v1/track';

	/**
	 * Autoloader path - this placeholder is replaced during installation.
	 * DO NOT MODIFY - this value is injected by the parent plugin.
	 *
	 * @var string
	 */
	const AUTOLOADER_PATH = '{{AUTOLOADER_PATH}}';

	/**
	 * Initialize the proxy speed module.
	 *
	 * @return void
	 */
	public function init() {
		// Only intercept POST requests to the proxy endpoint.
		if ( ! $this->is_proxy_request() ) {
			return;
		}

		// If autoloader failed, let WordPress continue loading
		// and fallback to the regular REST API.
		if ( ! $this->load_autoloader() ) {
			return;
		}

		// Handle the request completely and exit.
		$this->handle_proxy_request();
		exit;
	}

	/**
	 * Check if current request is a proxy request.
	 *
	 * @return bool
	 */
	private function is_proxy_request() {
		if ( 'POST' !== $this->get_request_method() ) {
			return false;
		}

		$path = $this->get_request_path();

		if ( '' === $path ) {
			return false;
		}

		$normalized_path = rtrim( $path, '/' );
		$proxy_suffix    = '/' . ltrim( self::PROXY_REQUEST_PATH, '/' );

		if ( strlen( $normalized_path ) < strlen( $proxy_suffix ) ) {
			return false;
		}

		return substr( $normalized_path, -strlen( $proxy_suffix ) ) === $proxy_suffix;
	}

	/**
	 * Load the autoloader.
	 *
	 * At MU-plugin stage, plugins haven't loaded yet, so we bootstrap
	 * the autoloader directly using the path injected during installation.
	 *
	 * @return bool True if autoloader loaded and classes are available.
	 */
	private function load_autoloader() {
		$autoload_path = self::AUTOLOADER_PATH;

		// Validate the path was properly injected (not still a placeholder).
		if ( strpos( $autoload_path, '{{' ) !== false ) {
			error_log( 'WooCommerce Analytics Proxy Speed Module: Autoloader path placeholder was not replaced during installation.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		if ( ! file_exists( $autoload_path ) ) {
			error_log( 'WooCommerce Analytics Proxy Speed Module: Autoloader file not found at: ' . $autoload_path ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		try {
			require_once $autoload_path;
		} catch ( \Throwable $e ) {
			error_log( 'WooCommerce Analytics Proxy Speed Module: Failed to load autoloader: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		if ( ! class_exists( '\Automattic\Woocommerce_Analytics\WC_Analytics_Tracking' ) ) {
			error_log( 'WooCommerce Analytics Proxy Speed Module: WC_Analytics_Tracking class not found after loading autoloader.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		return true;
	}

	/**
	 * Handle the proxy request completely.
	 *
	 * Processes the events and sends the response without loading
	 * regular WordPress plugins.
	 *
	 * @return void
	 */
	private function handle_proxy_request() {
		if ( ! headers_sent() ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Cache-Control: no-cache, must-revalidate' );
		}

		try {
			$this->process_proxy_request();
		} catch ( \Throwable $e ) {
			error_log( 'WooCommerce Analytics Proxy Speed Module: Uncaught error in handle_proxy_request: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$this->send_json_response(
				array(
					'success' => false,
					'error'   => 'Internal server error while processing analytics events.',
				),
				500
			);
		}
	}

	/**
	 * Process the proxy request body: parse events, record them, and send the response.
	 *
	 * @return void
	 */
	private function process_proxy_request() {
		// Apply magic quotes to superglobals ($_GET, $_POST, $_COOKIE, $_REQUEST) for compatibility with the regular API flow.
		if ( function_exists( 'wp_magic_quotes' ) ) {
			wp_magic_quotes();
		}

		$body = file_get_contents( 'php://input' );
		if ( empty( $body ) ) {
			$this->send_json_response(
				array(
					'success' => false,
					'error'   => 'Empty request body',
				),
				400
			);
			return;
		}

		$events = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->send_json_response(
				array(
					'success' => false,
					'error'   => 'Invalid JSON',
				),
				400
			);
			return;
		}

		// Normalize: wrap a single event object or unexpected scalar in an array.
		if ( ! is_array( $events ) || isset( $events['event_name'] ) ) {
			$events = array( $events );
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

			if ( ! $event_name || ! is_array( $properties ) ) {
				$results[ $index ] = array(
					'success' => false,
					'error'   => 'Missing event_name or invalid properties',
				);
				$has_errors        = true;
				continue;
			}

			$result = \Automattic\Woocommerce_Analytics\WC_Analytics_Tracking::record_event( $event_name, $properties );

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

		\Automattic\Woocommerce_Analytics\WC_Analytics_Tracking::send_batched_pixels();

		$this->send_json_response(
			array(
				'success'               => ! $has_errors,
				'results'               => $results,
				'is_proxy_speed_module' => true,
			),
			$has_errors ? 207 : 200
		);
	}

	/**
	 * Send a JSON response.
	 *
	 * @param array $data Response data.
	 * @param int   $status_code HTTP status code.
	 * @return void
	 */
	private function send_json_response( $data, $status_code = 200 ) {
		http_response_code( $status_code );
		echo wp_json_encode( $data, JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Helper method to retrieve the request path.
	 *
	 * Extracts and validates the path component from $_SERVER['REQUEST_URI']
	 * for safe internal matching.
	 *
	 * @return string The validated path, or empty string on failure.
	 */
	private function get_request_path() {
		$raw_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_string( $raw_uri ) ) {
			return '';
		}

		// Extract just the path component to avoid matching against query strings, etc.
		$path = wp_parse_url( $raw_uri, PHP_URL_PATH );

		if ( ! is_string( $path ) ) {
			return '';
		}

		// Ensure the path contains only expected URL path characters.
		if ( preg_match( '/[^A-Za-z0-9\-._~\/]/', $path ) ) {
			return '';
		}

		return $path;
	}

	/**
	 * Helper method to get request method.
	 *
	 * @return string
	 */
	private function get_request_method() {
		return isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
}

( new WooCommerceAnalyticsProxySpeed() )->init();
