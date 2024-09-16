<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Logging;

use Automattic\Jetpack\Constants;


/**
 * UseNonBuiltInFunctions Trait
 *
 * This trait creates a wrapper for non-built-in functions for safety.
 *
 * @since 9.4.0
 * @package Automattic\WooCommerce\Internal\Logging
 */
trait UseNonBuiltInFunctions {

	/**
	 * Wp_parse_url wrapper.
	 *
	 * @param string $url       The URL to parse.
	 * @param int    $component The specific component to retrieve. Use one of the PHP
	 *                          predefined constants to specify which one.
	 *                          Defaults to -1 (= return all parts as an array).
	 * @return mixed False on parse failure; Array of URL components on success;
	 *               When a specific component has been requested: null if the component
	 *               doesn't exist in the given URL; a string or - in the case of
	 *               PHP_URL_PORT - integer when it does. See parse_url()'s return values.
	 */
	private function wp_parse_url( $url, $component = -1 ) {
		try {
			if ( ! function_exists( 'wp_parse_url' ) ) {
				require_once ABSPATH . WPINC . '/http.php';
			}

			return wp_parse_url( $url, $component );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'url'       => $url,
					'component' => $component,
				)
			);

			return null;
		}
	}

	/**
	 * Home_url wrapper.
	 *
	 * @param string $path The path to append to the home URL.
	 * @param string $scheme The scheme to use for the home URL.
	 * @return string The home URL with the path appended.
	 */
	private function home_url( $path = '', $scheme = null ) {
		try {
			if ( ! function_exists( 'home_url' ) ) {
				require_once ABSPATH . WPINC . '/link-template.php';
			}

			return home_url( $path, $scheme );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'path'   => $path,
					'scheme' => $scheme,
				)
			);

			return 'Unable to retrieve';
		}
	}


	/**
	 * Get_bloginfo wrapper.
	 *
	 * @param string $show   Optional. Site info to retrieve. Default empty (site name).
	 * @param string $filter Optional. How to filter what is retrieved. Default 'raw'.
	 * @return string Mostly string values, might be empty.
	 */
	private function get_bloginfo( $show = '', $filter = 'raw' ) {
		try {
			if ( ! function_exists( 'get_bloginfo' ) ) {
				require_once ABSPATH . WPINC . '/general-template.php';
			}

			return get_bloginfo( $show, $filter );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'show'   => $show,
					'filter' => $filter,
				)
			);

			return 'Unable to retrieve';
		}
	}

	/**
	 * Get_option wrapper.
	 *
	 * @param string $option Name of the option to retrieve. Expected to not be SQL-escaped.
	 * @param mixed  $_default Optional. Default value to return if the option does not exist.
	 * @return mixed Value of the option. A value of any type may be returned, including
	 *               scalar (string, boolean, float, integer), null, array, object.
	 *               Scalar and null values will be returned as strings as long as they originate
	 *               from a database stored option value. If there is no option in the database,
	 *               boolean `false` is returned.
	 */
	private function get_option( $option, $_default = false ) {
		try {
			if ( ! function_exists( 'get_option' ) ) {
				require_once ABSPATH . WPINC . '/option.php';
			}

			return get_option( $option, $_default );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'option'   => $option,
					'_default' => $_default,
				)
			);
			return 'Unable to retrieve';
		}
	}


	/**
	 * Get_site_transient wrapper.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 * @return mixed Value of the transient.
	 */
	private function get_site_transient( $transient ) {
		if ( ! function_exists( 'get_site_transient' ) ) {
			require_once ABSPATH . WPINC . '/option.php';
		}

		try {
			return get_site_transient( $transient );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'transient' => $transient,
				)
			);
			return 'Unable to retrieve';
		}
	}

	/**
	 * Set_site_transient wrapper.
	 *
	 * @param string $transient Transient name. Expected to not be SQL-escaped.
	 * @param mixed  $value Value of the transient.
	 * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
	 * @return bool True if the transient was set successfully, false otherwise.
	 */
	private function set_site_transient( $transient, $value, $expiration = 0 ) {
		try {
			if ( ! function_exists( 'set_site_transient' ) ) {
				require_once ABSPATH . WPINC . '/option.php';
			}

			return set_site_transient( $transient, $value, $expiration );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'transient'  => $transient,
					'value'      => $value,
					'expiration' => $expiration,
				)
			);
			return false;
		}
	}

	/**
	 * Wp_safe_remote_post wrapper.
	 *
	 * @param string $url  URL to send the request to.
	 * @param array  $args Optional. Additional arguments for the request.
	 * @return array|WP_Error The response or WP_Error on failure.
	 *
	 * @throws \Exception If wp_safe_remote_post function does not exist.
	 */
	private function wp_safe_remote_post( $url, $args = array() ) {
		try {
			if ( ! function_exists( 'wp_safe_remote_post' ) ) {
				require_once ABSPATH . WPINC . '/http.php';
			}

			return wp_safe_remote_post( $url, $args );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'url'  => $url,
					'args' => $args,
				)
			);

			return new \WP_Error( 'remote_post_error', $e->getMessage() );
		}
	}


	/**
	 * Is_wp_error wrapper.
	 *
	 * @param mixed $thing The variable to check.
	 * @return bool Whether the variable is an instance of WP_Error.
	 *
	 * @throws \Exception If is_wp_error function does not exist.
	 */
	private function is_wp_error( $thing ) {
		try {
			if ( ! function_exists( 'is_wp_error' ) ) {
				require_once ABSPATH . WPINC . '/load.php';
			}

			return is_wp_error( $thing );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'thing' => $thing,
				)
			);

			// We can't determine if the variable is an instance of WP_Error so we throw an exception.
			throw new \Exception( 'is_wp_error function does not exist' );
		}
	}

	/**
	 * Get_plugin_updates wrapper.
	 *
	 * @return array|null The plugin updates array or null if not available.
	 */
	private function get_plugin_updates() {
		try {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( ! function_exists( 'get_plugin_updates' ) ) {
				require_once ABSPATH . 'wp-admin/includes/update.php';
			}

			return get_plugin_updates();
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array()
			);

			// If the function does not exist, return null since the update is not available.
			return null;
		}
	}

	/**
	 * Get_wp_environment_type wrapper.
	 *
	 * @return string The environment type.
	 *
	 * @throws \Exception If wp_get_environment_type function does not exist.
	 */
	private function wp_get_environment_type() {
		try {
			if ( ! function_exists( 'wp_get_environment_type' ) ) {
				require_once ABSPATH . WPINC . '/load.php';
			}

			return wp_get_environment_type();
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array()
			);

			// If the function does not exist, return production since the wp default value is production.
			return 'production';
		}
	}

	/**
	 * Wp_json_encode wrapper.
	 *
	 * @param mixed $data The data to encode.
	 * @return string The JSON encoded string.
	 *
	 * @throws \Exception If wp_json_encode function does not exist.
	 */
	private function wp_json_encode( $data ) {
		try {
			if ( ! function_exists( 'wp_json_encode' ) ) {
				require_once ABSPATH . WPINC . '/functions.php';
			}

			return wp_json_encode( $data );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array(
					'data' => $data,
				)
			);

			return 'Unable to encode';
		}
	}

	/**
	 * Get_wc_version wrapper.
	 *
	 * @return string The WooCommerce version.
	 *
	 * @throws \Exception If get_wc_version function does not exist.
	 */
	private function get_wc_version() {
		try {
			return Constants::get_constant( 'WC_VERSION' );
		} catch ( \Throwable $e ) {
			$this->log_wrapper_error(
				__FUNCTION__,
				$e->getMessage(),
				array()
			);

			return 'Unable to retrieve';
		}
	}

	/**
	 * Wc_get_logger wrapper.
	 *
	 * @return \WC_Logger The WooCommerce logger.
	 *
	 * @throws \Exception If wc_get_logger function does not exist.
	 */
	private function wc_get_logger() {
		try {
			if ( ! function_exists( 'wc_get_logger' ) ) {
				require_once WC_ABSPATH . 'includes/class-wc-logger.php';
			}

			return wc_get_logger();
		} catch ( \Throwable $e ) {
			throw new \Exception( 'wc_get_logger function does not exist' );
		}
	}

	/**
	 * Wc_print_r wrapper.
	 *
	 * @param mixed $data The data to print.
	 * @param bool  $_return Whether to return the output instead of printing it.
	 * @return string The printed data.
	 */
	private function wc_print_r( $data, $_return = false ) {
		if ( ! function_exists( 'wc_print_r' ) ) {
			require_once WC_ABSPATH . 'includes/wc-core-functions.php';
		}

		return wc_print_r( $data, $_return );
	}


	/**
	 * Log wrapper function errors to "local logging" for debugging.
	 *
	 * @param string $function_name The name of the wrapped function.
	 * @param string $error_message The error message.
	 * @param array  $context       Additional context for the error.
	 */
	private function log_wrapper_error( $function_name, $error_message, $context = array() ) {
		$this->wc_get_logger()->error(
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
