<?php
/**
 * Tests for the visitor id endpoint.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;
use WorDBless\BaseTestCase;

/**
 * Tests for WC_Analytics_Visitor.
 */
class WC_Analytics_Visitor_Test extends BaseTestCase {

	const ROUTE = '/woocommerce-analytics/v1/visitor';

	/**
	 * Snapshot of $_SERVER to restore after each test.
	 *
	 * @var array
	 */
	private $server_snapshot = array();

	/**
	 * Set up.
	 */
	public function set_up(): void {
		parent::set_up();
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION );
		$GLOBALS['wp_rest_server']  = null;
		$this->server_snapshot      = $_SERVER;
		$_SERVER['REQUEST_METHOD']  = 'POST';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15';
		unset( $_COOKIE['tk_ai'], $_SERVER['HTTPS'] );
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		delete_option( Woocommerce_Analytics::PROXY_TRACKING_EVER_ENABLED_OPTION );
		$GLOBALS['wp_rest_server'] = null;
		$_SERVER                   = $this->server_snapshot;
		unset( $_COOKIE['tk_ai'] );
		parent::tear_down();
	}

	/**
	 * Put a cookie on the request the way PHP would: raw header plus the decoded superglobal.
	 *
	 * @param string $raw Raw cookie value as the browser sends it.
	 */
	private function send_cookie_value( string $raw ): void {
		$_SERVER['HTTP_COOKIE'] = 'other=1; tk_ai=' . $raw . '; last=2';
		$_COOKIE['tk_ai']       = urldecode( $raw );
	}

	/**
	 * Dispatch a POST to the endpoint.
	 *
	 * @return \WP_REST_Response
	 */
	private function dispatch(): \WP_REST_Response {
		Woocommerce_Analytics::register_rest_routes();
		return rest_get_server()->dispatch( new \WP_REST_Request( 'POST', self::ROUTE ) );
	}

	/**
	 * The route exists whether or not proxy tracking was ever enabled.
	 */
	public function test_route_is_registered_without_proxy_tracking(): void {
		Woocommerce_Analytics::register_rest_routes();

		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( self::ROUTE, $routes );
		$this->assertArrayNotHasKey( '/woocommerce-analytics/v1/track', $routes );
	}

	/**
	 * A browser without a cookie gets a new 24-character id.
	 */
	public function test_mints_an_id_when_none_is_sent(): void {
		$response = $this->dispatch();
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsString( $data['anon_id'] );
		$this->assertSame( 24, strlen( $data['anon_id'] ) );
		$this->assertMatchesRegularExpression( '/^[A-Za-z0-9_-]{24}$/', $data['anon_id'], 'URL-safe base64, no + or /.' );
		$this->assertFalse( $data['reused'] );
	}

	/**
	 * The cookie attributes: one year, Path=/, no Domain, not HttpOnly, SameSite=Lax, Secure
	 * only on SSL. Headers cannot be observed under PHPUnit, so the options are asserted.
	 */
	public function test_cookie_options(): void {
		$options = WC_Analytics_Visitor::get_cookie_options();

		$this->assertEqualsWithDelta( time() + 365 * DAY_IN_SECONDS, $options['expires'], 5 );
		$this->assertSame( '/', $options['path'] );
		$this->assertArrayNotHasKey( 'domain', $options );
		$this->assertFalse( $options['httponly'] );
		$this->assertSame( 'Lax', $options['samesite'] );
		$this->assertFalse( $options['secure'] );

		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue( WC_Analytics_Visitor::get_cookie_options()['secure'] );
	}

	/**
	 * The cookie the browser sent is re-issued with the same value, so the expiry rolls and a
	 * script-written cookie becomes an HTTP one.
	 */
	public function test_reissues_the_cookie_the_browser_sent(): void {
		$this->send_cookie_value( 'jetpack:abcDEF123456789' );

		$data = $this->dispatch()->get_data();

		$this->assertSame( 'jetpack:abcDEF123456789', $data['anon_id'] );
		$this->assertTrue( $data['reused'] );
	}

	/**
	 * An id the client script minted with standard base64 keeps its `+` and `/`: the raw
	 * header is read, not the url-decoded superglobal where `+` becomes a space.
	 */
	public function test_keeps_a_base64_id_with_plus_and_slash(): void {
		$this->send_cookie_value( 'aB+cD/eF0123456789ghij==' );

		$data = $this->dispatch()->get_data();

		$this->assertSame( 'aB+cD/eF0123456789ghij==', $data['anon_id'] );
		$this->assertTrue( $data['reused'] );
	}

	/**
	 * A malformed value is not echoed back; a fresh id replaces it.
	 */
	public function test_replaces_a_malformed_cookie_value(): void {
		$this->send_cookie_value( '<script>alert(1)</script>' );

		$data = $this->dispatch()->get_data();

		$this->assertFalse( $data['reused'] );
		$this->assertSame( 24, strlen( $data['anon_id'] ) );
		$this->assertStringNotContainsString( 'script', $data['anon_id'] );
	}

	/**
	 * A value with a trailing newline (reachable through the url-decoded superglobal) is
	 * not echoed either.
	 */
	public function test_replaces_a_value_with_a_trailing_newline(): void {
		unset( $_SERVER['HTTP_COOKIE'] );
		$_COOKIE['tk_ai'] = "abcdefgh0123\n";

		$data = $this->dispatch()->get_data();

		$this->assertFalse( $data['reused'] );
		$this->assertSame( 24, strlen( $data['anon_id'] ) );
		$this->assertStringNotContainsString( 'script', $data['anon_id'] );
	}

	/**
	 * Bots get no id.
	 */
	public function test_bots_get_nothing(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

		$response = $this->dispatch();

		$this->assertSame( 200, $response->get_status() );
		$this->assertNull( $response->get_data()['anon_id'] );
	}

	/**
	 * The response is never cacheable.
	 */
	public function test_response_is_not_cacheable(): void {
		$headers = $this->dispatch()->get_headers();

		$this->assertSame( 'no-store', $headers['Cache-Control'] );
	}
}
