<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Logging;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\StringUtil;
use WC_Rate_Limiter;
use WC_Log_Levels;

/**
 * WooCommerce Remote Logger
 *
 * The WooCommerce remote logger class adds functionality to log WooCommerce errors remotely based on if the customer opted in and several other conditions.
 *
 * No personal information is logged, only error information and relevant context.
 *
 * @class RemoteLogger
 * @since 9.2.0
 * @package WooCommerce\Classes
 */
class RemoteLogger extends \WC_Log_Handler {
	const LOG_ENDPOINT                = 'https://public-api.wordpress.com/rest/v1.1/logstash';
	const RATE_LIMIT_ID               = 'woocommerce_remote_logging';
	const RATE_LIMIT_DELAY            = 60; // 1 minute.
	const WC_LATEST_VERSION_TRANSIENT = 'latest_woocommerce_version';
	const FETCH_LATEST_VERSION_RETRY  = 'fetch_latest_woocommerce_version_retry';

	/**
	 * Handle a log entry.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message.
	 * @param array  $context Additional information for log handlers.
	 *
	 * @throws \Exception If the remote logging fails. The error is caught and logged locally.
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $timestamp, $level, $message, $context ) {
		if ( ! $this->should_handle( $level, $message, $context ) ) {
			return false;
		}

		return $this->log( $level, $message, $context );
	}

	/**
	 * Get formatted log data to be sent to the remote logging service.
	 *
	 * This method formats the log data by sanitizing the message, adding default fields, and including additional context
	 * such as backtrace, tags, and extra attributes. It also integrates with WC_Tracks to include blog and store details.
	 * The formatted log data is then filtered before being sent to the remote logging service.
	 *
	 * @param string $level   Log level (e.g., 'error', 'warning', 'info').
	 * @param string $message Log message to be recorded.
	 * @param array  $context Optional. Additional information for log handlers, such as 'backtrace', 'tags', 'extra', and 'error'.
	 *
	 * @return array Formatted log data ready to be sent to the remote logging service.
	 */
	public function get_formatted_log( $level, $message, $context = array() ) {
		$log_data = array(
			// Default fields.
			'feature'    => 'woocommerce_core',
			'severity'   => $level,
			'message'    => $this->sanitize( $message ),
			'host'       => wp_parse_url( home_url(), PHP_URL_HOST ),
			'tags'       => array( 'woocommerce', 'php' ),
			'properties' => array(
				'wc_version'  => WC()->version,
				'php_version' => phpversion(),
				'wp_version'  => get_bloginfo( 'version' ),
				'request_uri' => $this->sanitize_request_uri( filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL ) ),
			),
		);

		if ( isset( $context['backtrace'] ) ) {
			if ( is_array( $context['backtrace'] ) || is_string( $context['backtrace'] ) ) {
				$log_data['trace'] = $this->sanitize_trace( $context['backtrace'] );
			} elseif ( true === $context['backtrace'] ) {
				$log_data['trace'] = $this->sanitize_trace( self::get_backtrace() );
			}
			unset( $context['backtrace'] );
		}

		if ( isset( $context['tags'] ) && is_array( $context['tags'] ) ) {
			$log_data['tags'] = array_merge( $log_data['tags'], $context['tags'] );
			unset( $context['tags'] );
		}

		if ( class_exists( '\WC_Tracks' ) ) {
			$user         = wp_get_current_user();
			$blog_details = \WC_Tracks::get_blog_details( $user->ID );

			if ( is_numeric( $blog_details['blog_id'] ) && $blog_details['blog_id'] > 0 ) {
				$log_data['blog_id'] = $blog_details['blog_id'];
			}

			if ( ! empty( $blog_details['store_id'] ) ) {
				$log_data['properties']['store_id'] = $blog_details['store_id'];
			}
		}

		if ( isset( $context['error'] ) && is_array( $context['error'] ) && ! empty( $context['error']['file'] ) ) {
			$context['error']['file'] = $this->sanitize( $context['error']['file'] );
		}

		$extra_attrs = $context['extra'] ?? array();
		unset( $context['extra'] );
		// Merge the extra attributes with the remaining context since we can't send arbitrary fields to Logstash.
		$log_data['extra'] = array_merge( $extra_attrs, $context );

		/**
		 * Filters the formatted log data before sending it to the remote logging service.
		 * Returning a non-array value will prevent the log from being sent.
		 *
		 * @since 9.2.0
		 *
		 * @param array  $log_data The formatted log data.
		 * @param string $level    The log level (e.g., 'error', 'warning').
		 * @param string $message  The log message.
		 * @param array  $context  The original context array.
		 *
		 * @return array The filtered log data.
		 */
		return apply_filters( 'woocommerce_remote_logger_formatted_log_data', $log_data, $level, $message, $context );
	}

	/**
	 * Determines if remote logging is allowed based on the following conditions:
	 *
	 * 1. The feature flag for remote error logging is enabled.
	 * 2. The user has opted into tracking/logging.
	 * 3. The store is allowed to log based on the variant assignment percentage.
	 * 4. The current WooCommerce version is the latest so we don't log errors that might have been fixed in a newer version.
	 *
	 * @return bool
	 */
	public function is_remote_logging_allowed() {
		if ( ! FeaturesUtil::feature_is_enabled( 'remote_logging' ) ) {
			return false;
		}

		if ( ! \WC_Site_Tracking::is_tracking_enabled() ) {
			return false;
		}

		if ( ! $this->is_variant_assignment_allowed() ) {
			return false;
		}

		if ( ! $this->is_latest_woocommerce_version() ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether to handle or ignore log.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message to be recorded.
	 * @param array  $context Additional information for log handlers.
	 *
	 * @return bool True if the log should be handled.
	 */
	protected function should_handle( $level, $message, $context ) {
		if ( ! $this->is_remote_logging_allowed() ) {
			return false;
		}
		// Ignore logs that are less severe than critical. This is temporary to prevent sending too many logs to the remote logging service. We can consider remove this if the remote logging service can handle more logs.
		if ( WC_Log_Levels::get_level_severity( $level ) < WC_Log_Levels::get_level_severity( WC_Log_Levels::CRITICAL ) ) {
			return false;
		}

		if ( $this->is_third_party_error( (string) $message, (array) $context ) ) {
			return false;
		}

		if ( WC_Rate_Limiter::retried_too_soon( self::RATE_LIMIT_ID ) ) {
			error_log( 'Remote logging throttled.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		return true;
	}


	/**
	 * Send the log to the remote logging service.
	 *
	 * @param string $level   Log level (e.g., 'error', 'warning', 'info').
	 * @param string $message Log message to be recorded.
	 * @param array  $context Optional. Additional information for log handlers, such as 'backtrace', 'tags', 'extra', and 'error'.
	 *
	 * @throws \Exception If the remote logging fails. The error is caught and logged locally.
	 * @return bool
	 */
	private function log( $level, $message, $context ) {
		try {
			$log_data = $this->get_formatted_log( $level, $message, $context );

			// Ensure the log data is valid.
			if ( ! is_array( $log_data ) || empty( $log_data['message'] ) || empty( $log_data['feature'] ) ) {
				return false;
			}

			$body = array(
				'params' => wp_json_encode( $log_data ),
			);

			WC_Rate_Limiter::set_rate_limit( self::RATE_LIMIT_ID, self::RATE_LIMIT_DELAY );

			if ( $this->is_dev_or_local_environment() ) {
				return false;
			}

			$response = wp_safe_remote_post(
				self::LOG_ENDPOINT,
				array(
					'body'     => wp_json_encode( $body ),
					'timeout'  => 2,
					'headers'  => array(
						'Content-Type' => 'application/json',
					),
					'blocking' => false,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new \Exception( $response->get_error_message() );
			}

			return true;
		} catch ( \Exception $e ) {
			// Log the error locally if the remote logging fails.
			error_log( 'Remote logging failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}
	}

	/**
	 * Check if the store is allowed to log based on the variant assignment percentage.
	 *
	 * @return bool
	 */
	private function is_variant_assignment_allowed() {
		$assignment = get_option( 'woocommerce_remote_variant_assignment', 0 );
		return ( $assignment <= 12 ); // Considering 10% of the 0-120 range.
	}

	/**
	 * Check if the current WooCommerce version is the latest.
	 *
	 * @return bool
	 */
	private function is_latest_woocommerce_version() {
		$latest_wc_version = $this->fetch_latest_woocommerce_version();

		if ( is_null( $latest_wc_version ) ) {
			return false;
		}

		return version_compare( WC()->version, $latest_wc_version, '>=' );
	}

	/**
	 * Check if the error exclusively contains third-party stack frames for fatal-errors source context.
	 *
	 * @param string $message The error message.
	 * @param array  $context The error context.
	 *
	 * @return bool
	 */
	protected function is_third_party_error( string $message, array $context ): bool {
		// Only check for fatal-errors source context.
		if ( ! isset( $context['source'] ) || 'fatal-errors' !== $context['source'] ) {
			return false;
		}

		// If backtrace is not available, we can't determine if the error is third-party. Log it for further investigation.
		if ( ! isset( $context['backtrace'] ) || ! is_array( $context['backtrace'] ) ) {
			return false;
		}

		$wc_plugin_dir = StringUtil::normalize_local_path_slashes( WC_ABSPATH );

		// Check if the error message contains the WooCommerce plugin directory.
		if ( str_contains( $message, $wc_plugin_dir ) ) {
			return false;
		}

		// Check if the backtrace contains the WooCommerce plugin directory.
		foreach ( $context['backtrace'] as $trace ) {
			if ( is_string( $trace ) && str_contains( $trace, $wc_plugin_dir ) ) {
				return false;
			}

			if ( is_array( $trace ) && isset( $trace['file'] ) && str_contains( $trace['file'], $wc_plugin_dir ) ) {
				return false;
			}
		}

		/**
		 * Filter to allow other plugins to overwrite the result of the third-party error check for remote logging.
		 *
		 * @since 9.2.0
		 *
		 * @param bool   $is_third_party_error The result of the third-party error check.
		 * @param string $message              The error message.
		 * @param array  $context              The error context.
		 */
		return apply_filters( 'woocommerce_remote_logging_is_third_party_error', true, $message, $context );
	}

	/**
	 * Fetch the latest WooCommerce version using the WordPress API and cache it.
	 *
	 * @return string|null
	 */
	private function fetch_latest_woocommerce_version() {
		$cached_version = get_transient( self::WC_LATEST_VERSION_TRANSIENT );
		if ( $cached_version ) {
			return $cached_version;
		}

		$retry_count = get_transient( self::FETCH_LATEST_VERSION_RETRY );
		if ( false === $retry_count || ! is_numeric( $retry_count ) ) {
			$retry_count = 0;
		}

		if ( $retry_count >= 3 ) {
			return null;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		// Fetch the latest version from the WordPress API.
		$plugin_info = plugins_api( 'plugin_information', array( 'slug' => 'woocommerce' ) );

		if ( is_wp_error( $plugin_info ) ) {
			++$retry_count;
			set_transient( self::FETCH_LATEST_VERSION_RETRY, $retry_count, HOUR_IN_SECONDS );
			return null;
		}

		if ( ! empty( $plugin_info->version ) ) {
			$latest_version = $plugin_info->version;
			set_transient( self::WC_LATEST_VERSION_TRANSIENT, $latest_version, WEEK_IN_SECONDS );
			delete_transient( self::FETCH_LATEST_VERSION_RETRY );
			return $latest_version;
		}

		return null;
	}

	/**
	 * Sanitize the content to exclude sensitive data.
	 *
	 * The trace is sanitized by:
	 *
	 * 1. Remove the absolute path to the WooCommerce plugin directory.
	 * 2. Remove the absolute path to the WordPress root directory.
	 *
	 * For example, the trace:
	 *
	 * /var/www/html/wp-content/plugins/woocommerce/includes/class-wc-remote-logger.php on line 123
	 * will be sanitized to: **\/woocommerce/includes/class-wc-remote-logger.php on line 123
	 *
	 * @param string $message The message to sanitize.
	 * @return string The sanitized message.
	 */
	private function sanitize( $message ) {
		if ( ! is_string( $message ) ) {
			return $message;
		}

		$wc_path = StringUtil::normalize_local_path_slashes( WC_ABSPATH );
		$wp_path = StringUtil::normalize_local_path_slashes( ABSPATH );

		$sanitized = str_replace(
			array( $wc_path, $wp_path ),
			array( '**/' . dirname( WC_PLUGIN_BASENAME ) . '/', '**/' ),
			$message
		);

		return $sanitized;
	}

	/**
	 * Sanitize the error trace to exclude sensitive data.
	 *
	 * @param array|string $trace The error trace.
	 * @return string The sanitized trace.
	 */
	private function sanitize_trace( $trace ): string {
		if ( is_string( $trace ) ) {
			return $this->sanitize( $trace );
		}

		if ( ! is_array( $trace ) ) {
			return '';
		}

		$sanitized_trace = array_map(
			function ( $trace_item ) {
				if ( is_array( $trace_item ) && isset( $trace_item['file'] ) ) {
					$trace_item['file'] = $this->sanitize( $trace_item['file'] );
					return $trace_item;
				}

				return $this->sanitize( $trace_item );
			},
			$trace
		);

		$is_array_by_file = isset( $sanitized_trace[0]['file'] );
		if ( $is_array_by_file ) {
			return wc_print_r( $sanitized_trace, true );
		}

		return implode( "\n", $sanitized_trace );
	}

	/**
	 * Check if the current environment is development or local.
	 *
	 * Creates a helper method so we can easily mock this in tests.
	 *
	 * @return bool
	 */
	protected function is_dev_or_local_environment() {
		return in_array( wp_get_environment_type(), array( 'development', 'local' ), true );
	}
	/**
	 * Sanitize the request URI to only allow certain query parameters.
	 *
	 * @param string $request_uri The request URI to sanitize.
	 * @return string The sanitized request URI.
	 */
	private function sanitize_request_uri( $request_uri ) {
		$default_whitelist = array(
			'path',
			'page',
			'step',
			'task',
			'tab',
			'section',
			'status',
			'post_type',
			'taxonomy',
			'action',
		);

		/**
		 * Filter to allow other plugins to whitelist request_uri query parameter values for unmasked remote logging.
		 *
		 * @since 9.4.0
		 *
		 * @param string   $default_whitelist The default whitelist of query parameters.
		 */
		$whitelist = apply_filters( 'woocommerce_remote_logger_request_uri_whitelist', $default_whitelist );

		$parsed_url = wp_parse_url( $request_uri );
		if ( ! isset( $parsed_url['query'] ) ) {
			return $request_uri;
		}

		parse_str( $parsed_url['query'], $query_params );

		foreach ( $query_params as $key => &$value ) {
			if ( ! in_array( $key, $whitelist, true ) ) {
				$value = 'xxxxxx';
			}
		}

		$parsed_url['query'] = http_build_query( $query_params );
		return $this->build_url( $parsed_url );
	}

	/**
	 * Build a URL from its parsed components.
	 *
	 * @param array $parsed_url The parsed URL components.
	 * @return string The built URL.
	 */
	private function build_url( $parsed_url ) {
		$path     = $parsed_url['path'] ?? '';
		$query    = isset( $parsed_url['query'] ) ? "?{$parsed_url['query']}" : '';
		$fragment = isset( $parsed_url['fragment'] ) ? "#{$parsed_url['fragment']}" : '';

		return "$path$query$fragment";
	}
}
