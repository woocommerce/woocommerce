<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Logging;

/**
 * SafeGlobalFunctionProxy Class
 *
 * This class creates a wrapper for non-built-in functions for safety.
 *
 * @since 9.4.0
 * @package Automattic\WooCommerce\Internal\Logging
 */
class SafeGlobalFunctionProxy {

	/**
	 * Load missing function if we know where to find it.
	 * Modify this file to add more functions to the map.
	 *
	 * @param string $name The name of the function to load.
	 * @return void
	 * @throws \Exception If the function is missing and could not be loaded.
	 */
	private static function maybe_load_missing_function( $name ) {
		$function_map = array(
			'wp_parse_url'            => ABSPATH . WPINC . '/http.php',
			'home_url'                => ABSPATH . WPINC . '/link-template.php',
			'get_bloginfo'            => ABSPATH . WPINC . '/general-template.php',
			'get_option'              => ABSPATH . WPINC . '/option.php',
			'get_site_transient'      => ABSPATH . WPINC . '/option.php',
			'set_site_transient'      => ABSPATH . WPINC . '/option.php',
			'wp_safe_remote_post'     => ABSPATH . WPINC . '/http.php',
			'is_wp_error'             => ABSPATH . WPINC . '/load.php',
			'get_plugin_updates'      => array( ABSPATH . 'wp-admin/includes/update.php', ABSPATH . 'wp-admin/includes/plugin.php' ),
			'wp_get_environment_type' => ABSPATH . WPINC . '/load.php',
			'wp_json_encode'          => ABSPATH . WPINC . '/functions.php',
			'wc_get_logger'           => WC_ABSPATH . 'includes/class-wc-logger.php',
			'wc_print_r'              => WC_ABSPATH . 'includes/wc-core-functions.php',
		);

		if ( ! function_exists( $name ) ) {
			if ( isset( $function_map[ $name ] ) ) {
				$files = (array) $function_map[ $name ];
				foreach ( $files as $file ) {
					require_once $file;
				}
			} else {
				throw new \Exception( sprintf( 'Function %s does not exist and could not be loaded.', esc_html( $name ) ) );
			}
		}
	}

	/**
	 * Proxy for trapping all calls on SafeGlobalFunctionProxy.
	 * Use this for calling WP and WC global functions safely.
	 * Example usage:
	 *
	 * SafeGlobalFunctionProxy::wp_parse_url('https://example.com', PHP_URL_PATH);
	 *
	 * @since 9.4.0
	 * @param string $name The name of the function to call.
	 * @param array  $arguments The arguments to pass to the function.
	 * @return mixed The result of the function call, or null if an error occurs.
	 */
	public static function __callStatic( $name, $arguments ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Custom error handler is necessary to convert errors to exceptions
		set_error_handler(
			static function ( int $type, string $message, string $file, int $line ) {
				if ( __FILE__ === $file ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Used to adjust file and line number for accurate error reporting
					$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
					$file  = $trace[2]['file'] ?? $file;
					$line  = $trace[2]['line'] ?? $line;
				}
				$sanitized_message = filter_var( $message, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $message sanitised above. we don't want to rely on esc_html since it's not a PHP built-in
				throw new \ErrorException( $sanitized_message, 0, $type, $file, $line );
			}
		);

		try {
			self::maybe_load_missing_function( $name );
			$results = call_user_func_array( $name, $arguments );
		} catch ( \Throwable $e ) {
			self::log_wrapper_error( $name, $e->getMessage(), $arguments );
			$results = null;
		} finally {
			restore_error_handler();
		}

		return $results;
	}

	/**
	 * Log wrapper function errors to "local logging" for debugging.
	 *
	 * @param string $function_name The name of the wrapped function.
	 * @param string $error_message The error message.
	 * @param array  $context       Additional context for the error.
	 */
	protected static function log_wrapper_error( $function_name, $error_message, $context = array() ) {
		self::maybe_load_missing_function( 'wc_get_logger' );

		wc_get_logger()->error(
			'[Wrapper function error] ' . sprintf( 'Error in %s: %s', $function_name, $error_message ),
			array_merge(
				array(
					'function' => $function_name,
					'source'   => 'remote-logging',
				),
				$context
			)
		);
	}
}
