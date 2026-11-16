<?php
/**
 * Blocks HTTP requests from tests to external hosts.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * Answers unmocked requests to external hosts with a WP_Error and records them. Runs last on
 * `pre_http_request`, so mocks and WP_HTTP_TestCase::$http_responder answer first. Only
 * WC_External_HTTP_Guard_Listener installs it, and it fails the test that sent the request,
 * so suites that load the bootstrap without that listener are unaffected.
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
	 * Requests blocked since the list was last taken, as 'METHOD URL (from file:line)'.
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
	 * Install the guard, after WordPress has loaded. Calling it again is harmless.
	 */
	public static function register(): void {
		// Captured once, so a test that changes the home or site URL cannot make an external host count as local.
		if ( array() === self::$local_hosts ) {
			$hosts = array( 'localhost', '127.0.0.1', '::1' );
			foreach ( array( home_url(), site_url() ) as $url ) {
				$host = wp_parse_url( $url, PHP_URL_HOST );
				if ( is_string( $host ) && '' !== $host ) {
					$hosts[] = strtolower( $host );
				}
			}
			self::$local_hosts = array_unique( $hosts );
		}

		add_filter( 'pre_http_request', array( self::class, 'filter_request' ), PHP_INT_MAX, 3 );
		add_action( 'requests-requests.before_redirect', array( self::class, 'check_redirect' ), PHP_INT_MAX );
	}

	/**
	 * Set whether external requests are allowed until the next call.
	 *
	 * @param bool $allow_external Whether external hosts may be reached.
	 */
	public static function allow_external( bool $allow_external ): void {
		self::$allow_external = $allow_external;
	}

	/**
	 * Requests blocked since the list was last taken.
	 *
	 * @return string[] Each as 'METHOD URL (from file:line)', without query strings.
	 */
	public static function get_blocked_requests(): array {
		return self::$blocked;
	}

	/**
	 * Return the requests blocked so far and forget them.
	 *
	 * @return string[] Each as 'METHOD URL (from file:line)', without query strings.
	 */
	public static function take_blocked_requests(): array {
		$blocked       = self::$blocked;
		self::$blocked = array();
		return $blocked;
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
		if ( false !== $preempt || self::$allow_external ) {
			return $preempt;
		}

		// WP_Http::request() strips disallowed protocols only after this filter, so classify the URL it will send.
		$url = wp_kses_bad_protocol( (string) $url, array( 'http', 'https', 'ssl' ) );
		if ( ! self::is_external_url( $url ) ) {
			return $preempt;
		}

		$request = self::record( strtoupper( (string) ( $args['method'] ?? 'GET' ) ), $url );

		return new WP_Error( self::ERROR_CODE, 'The PHPUnit external HTTP guard blocked this request: ' . $request );
	}

	/**
	 * Stop a redirect to an external host. Requests follows redirects without calling
	 * pre_http_request again; WP_Http::request() turns the exception into a WP_Error.
	 *
	 * @param string $location The redirect target.
	 * @throws WpOrg\Requests\Exception For a redirect to an external host.
	 */
	public static function check_redirect( $location ): void {
		if ( self::$allow_external || ! self::is_external_url( (string) $location ) ) {
			return;
		}

		$request = self::record( 'REDIRECT', (string) $location );

		throw new WpOrg\Requests\Exception( esc_html( 'The PHPUnit external HTTP guard blocked this redirect: ' . $request ), self::ERROR_CODE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- The message is escaped; the second argument is a fixed error type, not output.
	}

	/**
	 * Record a blocked request without its query string.
	 *
	 * @param string $method The HTTP method, or REDIRECT.
	 * @param string $url    The URL that would have been requested.
	 * @return string The record, as 'METHOD URL (from file:line)'.
	 */
	private static function record( string $method, string $url ): string {
		$request         = sprintf( '%s %s (from %s)', $method, preg_replace( '/[?#].*$/s', '', $url ), self::caller() );
		self::$blocked[] = $request;
		return $request;
	}

	/**
	 * Whether a URL points at a host other than the test site and the loopback addresses.
	 *
	 * @param string $url The request URL.
	 * @return bool False for a URL without a host or with a scheme other than HTTP(S), which WordPress refuses to send.
	 */
	private static function is_external_url( string $url ): bool {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) ) {
			return false;
		}

		return ! in_array( strtolower( trim( $parts['host'], '[]' ) ), self::$local_hosts, true );
	}

	/**
	 * The file and line that sent the request: the first frame outside WordPress, or else
	 * the first one outside WordPress's HTTP and hook plumbing.
	 *
	 * @return string A path relative to ABSPATH and a line, or 'unknown caller'.
	 */
	private static function caller(): string {
		$fallback = null;
		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $frame ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Names the code that sent the blocked request.
			$file = $frame['file'] ?? '';
			if ( '' === $file || __FILE__ === $file ) {
				continue;
			}
			$location = str_replace( ABSPATH, '', $file ) . ':' . ( $frame['line'] ?? 0 );
			if ( ! preg_match( '#/wp-(?:includes|admin)/#', $file ) ) {
				return $location;
			}
			if ( null === $fallback && ! preg_match( '#/wp-includes/(?:class-wp-http[^/]*|http|class-wp-hook|plugin)\.php$#', $file ) ) {
				$fallback = $location;
			}
		}
		return $fallback ?? 'unknown caller';
	}
}
