<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Helper_API {
	public static $api_base;

	/**
	 * Load
	 *
	 * Allow devs to point the API base to a local API development or staging server.
	 * Note that sslverify will be turned off for the woocommerce.dev + WP_DEBUG combination.
	 * The URL can be changed on plugins_loaded before priority 10.
	 */
	public static function load() {
		self::$api_base = apply_filters( 'woocommerce_helper_api_base', 'https://woocommerce.com/wp-json/helper/1.0' );
		add_filter( 'http_request_host_is_external', array( __CLASS__, '_http_request_host_is_external' ), 10, 2 );
	}

	/**
	 * Perform an HTTP request to the Helper API.
	 *
	 * @param string $endpoint The endpoint to request.
	 * @param array $args Additional data for the request. Set authenticated to a truthy value to enable auth.
	 *
	 * @return array The response from wp_safe_remote_request()
	 */
	public static function request( $endpoint, $args = array() ) {
		$url = self::url( $endpoint );

		if ( ! empty( $args['authenticated'] ) ) {
			self::_authenticate( $url, $args );
		}

		// Disable SSL verification for the dev environment.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && strpos( self::$api_base, 'https://woocommerce.dev/' ) === 0 ) {
			$args['sslverify'] = false;
		}

		// TODO: Check response signatures on certain endpoints.
		return wp_safe_remote_request( $url, $args );
	}

	/**
	 * Adds authentication headers to an HTTP request.
	 *
	 * @param string $url The request URI.
	 * @param array $args By-ref, the args that will be passed to wp_remote_request().
	 *
	 * @return null
	 */
	private static function _authenticate( $url, &$args ) {
		$auth = WC_Helper_Options::get( 'auth' );
		$request_uri = parse_url( $url, PHP_URL_PATH );
		$query_string = parse_url( $url, PHP_URL_QUERY );
		if ( $query_string ) {
			$request_uri .= '?' . $query_string;
		}

		$data = array(
			'host' => parse_url( $url, PHP_URL_HOST ),
			'request_uri' => $request_uri,
			'method' => ! empty( $args['method'] ) ? $args['method'] : 'GET',
		);

		if ( ! empty( $args['body'] ) ) {
			$data['body'] = $args['body'];
		}

		$signature = hash_hmac( 'sha256', json_encode( $data ), $auth['access_token_secret'] );
		if ( empty( $args['headers'] ) ) {
			$args['headers'] = array();
		}

		$args['headers'] = array(
			'Authorization' => 'Bearer ' . $auth['access_token'],
			'X-Woo-Signature' => $signature,
		);
	}

	/**
	 * Wrapper for self::request().
	 */
	public static function get( $endpoint, $args = array() ) {
		$args['method'] = 'GET';
		return self::request( $endpoint, $args );
	}

	/**
	 * Wrapper for self::request().
	 */
	public static function post( $endpoint, $args = array() ) {
		$args['method'] = 'POST';
		return self::request( $endpoint, $args );
	}

	/**
	 * Using the API base, form a request URL from a given endpoint.
	 *
	 * @param string $endpoint The endpoint to request.
	 *
	 * @return string The absolute endpoint URL.
	 */
	public static function url( $endpoint ) {
		$endpoint = ltrim( $endpoint, '/' );
		$endpoint = sprintf( '%s/%s', self::$api_base, $endpoint );
		$endpoint = esc_url_raw( $endpoint );
		return $endpoint;
	}

	/**
	 * Adds woocommerce.dev to "external" URls if WP_DEBUG is on so
	 * that wp_http_validate_url allows the request.
	 *
	 * @param bool $r Whether the host is external
	 * @param string $host The requested host.
	 *
	 * @return bool True if the host is external, false if local/internal.
	 */
	public static function _http_request_host_is_external( $r, $host ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && 'woocommerce.dev' === $host ) {
			$r = true;
		}

		return $r;
	}
}

WC_Helper_API::load();
