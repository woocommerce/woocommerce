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
	 * Dispatch a POST to the endpoint.
	 *
	 * @return \WP_REST_Response
	 */
	private function dispatch(): \WP_REST_Response {
		Woocommerce_Analytics::register_rest_routes();
		return rest_get_server()->dispatch( new \WP_REST_Request( 'POST', self::ROUTE ) );
	}

	/**
	 * The Set-Cookie header of a response, or null.
	 *
	 * @param \WP_REST_Response $response Response.
	 * @return string|null
	 */
	private function set_cookie_header( \WP_REST_Response $response ) {
		$headers = $response->get_headers();
		return isset( $headers['Set-Cookie'] ) ? $headers['Set-Cookie'] : null;
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
	 * A browser without a cookie gets a new 24-character id in the body and in Set-Cookie.
	 */
	public function test_mints_an_id_and_sets_a_lax_cookie_when_none_is_sent(): void {
		$response = $this->dispatch();
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsString( $data['anon_id'] );
		$this->assertSame( 24, strlen( $data['anon_id'] ) );
		$this->assertFalse( $data['reused'] );

		$cookie = $this->set_cookie_header( $response );
		$this->assertNotNull( $cookie );
		$this->assertStringStartsWith( 'tk_ai=' . $data['anon_id'] . '; Path=/; Expires=', $cookie );
		$this->assertStringContainsString( '; Max-Age=' . ( 365 * DAY_IN_SECONDS ) . ';', $cookie );
		$this->assertStringEndsWith( '; SameSite=Lax', $cookie );
		$this->assertStringNotContainsString( 'Domain=', $cookie );
		$this->assertStringNotContainsString( 'HttpOnly', $cookie );
		$this->assertStringNotContainsString( 'Secure', $cookie, 'Secure is only set on SSL requests.' );
	}

	/**
	 * The cookie the browser sent is re-issued with the same value, so the expiry rolls and a
	 * script-written cookie becomes an HTTP one.
	 */
	public function test_reissues_the_cookie_the_browser_sent(): void {
		$_COOKIE['tk_ai'] = 'jetpack:abcDEF123456789+/=';

		$response = $this->dispatch();
		$data     = $response->get_data();

		$this->assertSame( 'jetpack:abcDEF123456789+/=', $data['anon_id'] );
		$this->assertTrue( $data['reused'] );
		$this->assertStringStartsWith( 'tk_ai=jetpack:abcDEF123456789+/=; Path=/;', $this->set_cookie_header( $response ) );
	}

	/**
	 * A malformed value is not echoed back; a fresh id replaces it.
	 */
	public function test_replaces_a_malformed_cookie_value(): void {
		$_COOKIE['tk_ai'] = '<script>alert(1)</script>';

		$response = $this->dispatch();
		$data     = $response->get_data();

		$this->assertFalse( $data['reused'] );
		$this->assertSame( 24, strlen( $data['anon_id'] ) );
		$this->assertStringNotContainsString( 'script', $this->set_cookie_header( $response ) );
	}

	/**
	 * Secure follows is_ssl().
	 */
	public function test_secure_attribute_on_ssl(): void {
		$_SERVER['HTTPS'] = 'on';

		$cookie = $this->set_cookie_header( $this->dispatch() );

		$this->assertStringContainsString( '; Secure; SameSite=Lax', $cookie );
	}

	/**
	 * Bots get no id and no cookie.
	 */
	public function test_bots_get_nothing(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

		$response = $this->dispatch();

		$this->assertSame( 200, $response->get_status() );
		$this->assertNull( $response->get_data()['anon_id'] );
		$this->assertNull( $this->set_cookie_header( $response ) );
	}

	/**
	 * The response is never cacheable.
	 */
	public function test_response_is_not_cacheable(): void {
		$headers = $this->dispatch()->get_headers();

		$this->assertSame( 'no-store', $headers['Cache-Control'] );
	}
}
