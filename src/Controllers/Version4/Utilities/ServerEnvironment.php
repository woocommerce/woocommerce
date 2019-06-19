<?php
/**
 * Server information for status report.
 *
 * @package WooCommerce/Utilities
 */

namespace WooCommerce\RestApi\Controllers\Version4\Utilities;

/**
 * ServerEnvironment class.
 */
class ServerEnvironment {
	/**
	 * Get array of environment information. Includes thing like software
	 * versions, and various server settings.
	 *
	 * @return array
	 */
	public function get_environment_info() {
		$post_request     = $this->test_post_request();
		$get_request      = $this->test_get_request();
		$database_version = wc_get_server_database_version();

		// Return all environment info. Described by JSON Schema.
		return array(
			'home_url'                  => get_option( 'home' ),
			'site_url'                  => get_option( 'siteurl' ),
			'version'                   => WC()->version,
			'log_directory'             => \WC_LOG_DIR,
			'log_directory_writable'    => (bool) @fopen( \WC_LOG_DIR . 'test-log.log', 'a' ), // phpcs:ignore
			'wp_version'                => get_bloginfo( 'version' ),
			'wp_multisite'              => is_multisite(),
			'wp_memory_limit'           => $this->get_wp_memory_limit(),
			'wp_debug_mode'             => $this->is_constant_true( 'WP_DEBUG' ),
			'wp_cron'                   => ! $this->is_constant_true( 'DISABLE_WP_CRON' ),
			'language'                  => get_locale(),
			'external_object_cache'     => wp_using_ext_object_cache(),
			'server_info'               => $this->get_server_software(),
			'php_version'               => phpversion(),
			'php_post_max_size'         => wc_let_to_num( ini_get( 'post_max_size' ) ),
			'php_max_execution_time'    => ini_get( 'max_execution_time' ),
			'php_max_input_vars'        => ini_get( 'max_input_vars' ),
			'curl_version'              => $this->get_curl_version(),
			'suhosin_installed'         => extension_loaded( 'suhosin' ),
			'max_upload_size'           => wp_max_upload_size(),
			'mysql_version'             => $database_version['number'],
			'mysql_version_string'      => $database_version['string'],
			'default_timezone'          => date_default_timezone_get(),
			'fsockopen_or_curl_enabled' => $this->fsockopen_or_curl_enabled(),
			'soapclient_enabled'        => class_exists( 'SoapClient' ),
			'domdocument_enabled'       => class_exists( 'DOMDocument' ),
			'gzip_enabled'              => is_callable( 'gzopen' ),
			'mbstring_enabled'          => extension_loaded( 'mbstring' ),
			'remote_post_successful'    => $post_request['success'],
			'remote_post_response'      => $post_request['response'],
			'remote_get_successful'     => $get_request['success'],
			'remote_get_response'       => $get_request['response'],
		);
	}

	/**
	 * Test POST to an external server.
	 *
	 * @return array
	 */
	protected function test_post_request() {
		$post_response_code = get_transient( 'woocommerce_test_remote_post' );

		if ( false === $post_response_code || is_wp_error( $post_response_code ) ) {
			$response = wp_safe_remote_post(
				'https://www.paypal.com/cgi-bin/webscr',
				array(
					'timeout'     => 10,
					'user-agent'  => 'WooCommerce/' . WC()->version,
					'httpversion' => '1.1',
					'body'        => array(
						'cmd' => '_notify-validate',
					),
				)
			);
			if ( ! is_wp_error( $response ) ) {
				$post_response_code = $response['response']['code'];
			}
			set_transient( 'woocommerce_test_remote_post', $post_response_code, HOUR_IN_SECONDS );
		}

		if ( is_wp_error( $post_response_code ) ) {
			return array(
				'success'  => false,
				'response' => $post_response_code->get_error_message(),
			);
		}

		return array(
			'success'  => $post_response_code >= 200 && $post_response_code < 300,
			'response' => $post_response_code,
		);
	}

	/**
	 * Test GET to an external server.
	 *
	 * @return array
	 */
	protected function test_get_request() {
		$get_response_code = get_transient( 'woocommerce_test_remote_get' );

		if ( false === $get_response_code || is_wp_error( $get_response_code ) ) {
			$response = wp_safe_remote_get( 'https://woocommerce.com/wc-api/product-key-api?request=ping&network=' . ( is_multisite() ? '1' : '0' ) );
			if ( ! is_wp_error( $response ) ) {
				$get_response_code = $response['response']['code'];
			}
			set_transient( 'woocommerce_test_remote_get', $get_response_code, HOUR_IN_SECONDS );
		}

		if ( is_wp_error( $get_response_code ) ) {
			return array(
				'success'  => false,
				'response' => $get_response_code->get_error_message(),
			);
		}

		return array(
			'success'  => $get_response_code >= 200 && $get_response_code < 300,
			'response' => $get_response_code,
		);
	}

	/**
	 * Return if a constant is defined and true.
	 *
	 * @param string $name Constant name.
	 * @return bool
	 */
	protected function is_constant_true( $name ) {
		return defined( $name ) && (bool) constant( $name );
	}

	/**
	 * Return info about server software running.
	 *
	 * @return string
	 */
	protected function get_server_software() {
		return isset( $_SERVER['SERVER_SOFTWARE'] ) ? wc_clean( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
	}

	/**
	 * Get CURL version running on server.
	 *
	 * @return string
	 */
	protected function get_curl_version() {
		$curl_version = '';
		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
		} elseif ( extension_loaded( 'curl' ) ) {
			$curl_version = __( 'cURL installed but unable to retrieve version.', 'woocommerce' );
		}
		return $curl_version;
	}

	/**
	 * Get WP memory limit.
	 *
	 * @return string
	 */
	protected function get_wp_memory_limit() {
		$wp_memory_limit = wc_let_to_num( WP_MEMORY_LIMIT );
		if ( function_exists( 'memory_get_usage' ) ) {
			$wp_memory_limit = max( $wp_memory_limit, wc_let_to_num( @ini_get( 'memory_limit' ) ) );
		}
		return (string) $wp_memory_limit;
	}

	/**
	 * See if modules are enabled.
	 *
	 * @return bool
	 */
	protected function fsockopen_or_curl_enabled() {
		return function_exists( 'fsockopen' ) || function_exists( 'curl_init' );
	}

}
