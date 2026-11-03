<?php
/**
 * Blocks HTTP requests from tests to external hosts.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * Guard against tests reaching external hosts over HTTP.
 *
 * Runs last on `pre_http_request`, so test mocks, WP_HTTP_TestCase::$http_responder
 * and every other filter get the first chance to answer. A request still unanswered
 * and bound for a host other than the test site gets a WP_Error instead of reaching
 * the network, and WC_External_HTTP_Guard_Listener fails the test that sent it.
 * A test that needs the real network declares it with `@group external-http`.
 */
final class WC_External_HTTP_Guard {

	/**
	 * The PHPUnit group that allows a test to reach external hosts.
	 */
	const GROUP = 'external-http';

	/**
	 * The WP_Error code returned for a blocked request.
	 */
	const ERROR_CODE = 'wc_tests_external_http_blocked';

	/**
	 * Requests blocked during the current test, as 'METHOD URL (from file:line)'.
	 *
	 * @var string[]
	 */
	private static $blocked = array();

	/**
	 * Whether the current test may reach external hosts.
	 *
	 * @var bool
	 */
	private static $allow_external = false;

	/**
	 * Lowercase hosts that count as local, captured when the guard is registered.
	 *
	 * @var string[]
	 */
	private static $local_hosts = array();

	/**
	 * Install the guard. Call once, after WordPress has loaded.
	 */
	public static function register(): void {
		$hosts = array( 'localhost', '127.0.0.1', '::1' );
		foreach ( array( home_url(), site_url() ) as $url ) {
			$host = wp_parse_url( $url, PHP_URL_HOST );
			if ( is_string( $host ) && '' !== $host ) {
				$hosts[] = strtolower( $host );
			}
		}
		// Captured once, so a test that changes the home or site URL cannot make an external host count as local.
		self::$local_hosts = array_values( array_unique( $hosts ) );

		add_filter( 'pre_http_request', array( self::class, 'filter_request' ), PHP_INT_MAX, 3 );
	}

	/**
	 * Start a test: forget earlier requests and set whether external requests are allowed.
	 *
	 * @param bool $allow_external Whether the test may reach external hosts.
	 */
	public static function reset( bool $allow_external ): void {
		self::$blocked        = array();
		self::$allow_external = $allow_external;
	}

	/**
	 * Requests blocked since the last reset.
	 *
	 * @return string[] Each as 'METHOD URL (from file:line)', without query strings.
	 */
	public static function get_blocked_requests(): array {
		return self::$blocked;
	}

	/**
	 * Block an unanswered request to an external host.
	 *
	 * @param false|array|WP_Error $preempt A response from an earlier filter, or false.
	 * @param array                $args    The request arguments.
	 * @param string               $url     The request URL.
	 * @return false|array|WP_Error The earlier answer unchanged, or an error for a blocked request.
	 */
	public static function filter_request( $preempt, $args, $url ) {
		if ( false !== $preempt || self::$allow_external || ! self::is_external_url( (string) $url ) ) {
			return $preempt;
		}

		$method  = is_array( $args ) && isset( $args['method'] ) ? strtoupper( (string) $args['method'] ) : 'GET';
		$request = sprintf( '%s %s (from %s)', $method, preg_replace( '/[?#].*$/s', '', (string) $url ), self::caller() );

		self::$blocked[] = $request;

		return new WP_Error( self::ERROR_CODE, 'The PHPUnit external HTTP guard blocked this request: ' . $request );
	}

	/**
	 * Whether a URL points at a host other than the test site and the loopback addresses.
	 *
	 * @param string $url The request URL.
	 * @return bool False for a URL without a host, which WordPress rejects on its own.
	 */
	private static function is_external_url( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		return ! in_array( strtolower( trim( $host, '[]' ) ), self::$local_hosts, true );
	}

	/**
	 * The file and line that sent the request, skipping WordPress's HTTP and hook plumbing.
	 *
	 * @return string A path relative to ABSPATH and a line, or 'unknown caller'.
	 */
	private static function caller(): string {
		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $frame ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Names the code that sent the blocked request.
			$file = $frame['file'] ?? '';
			if ( '' === $file || __FILE__ === $file || preg_match( '#/wp-includes/(?:class-wp-http[^/]*|http|class-wp-hook|plugin)\.php$#', $file ) ) {
				continue;
			}
			return str_replace( ABSPATH, '', $file ) . ':' . ( $frame['line'] ?? 0 );
		}
		return 'unknown caller';
	}
}
