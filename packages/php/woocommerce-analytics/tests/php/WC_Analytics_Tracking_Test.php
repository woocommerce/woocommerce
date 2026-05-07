<?php
/**
 * Tests for WC_Analytics_Tracking::record_event() cookie-less skip behavior.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WorDBless\BaseTestCase;

/**
 * Tests for WC_Analytics_Tracking::record_event().
 *
 * Verifies that events fired from contexts that cannot persist a `tk_ai`
 * cookie (REST/XMLRPC/cron/CLI) are skipped instead of producing fresh
 * random anonymous ids that fragment downstream sessions.
 */
class WC_Analytics_Tracking_Test extends BaseTestCase {

	/**
	 * Reset cached static state and superglobals before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->reset_tracking_static_state();
		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * Reset cached static state after each test so context constants don't leak.
	 */
	public function tear_down(): void {
		$this->reset_tracking_static_state();
		unset( $_COOKIE['tk_ai'] );
		parent::tear_down();
	}

	/**
	 * Use reflection to clear the `cached_visitor_id` static between tests.
	 */
	private function reset_tracking_static_state(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'cached_visitor_id' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * record_event() should short-circuit (no pixel emitted) when called from a
	 * REST request that has no `tk_ai` cookie. Generating a one-shot id here
	 * would fragment Nosara/Tracks sessions across cookie-less integrations.
	 */
	public function test_record_event_skips_rest_request_without_cookie(): void {
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}

		$result = WC_Analytics_Tracking::record_event( 'add_to_cart' );

		$this->assertTrue( $result, 'record_event should return true (skipped) for cookie-less REST contexts.' );
	}

	/**
	 * When the `tk_ai` cookie is present, get_visitor_id() should return its
	 * value verbatim — even inside a REST request. This is the precondition
	 * that lets record_event() proceed past the cookie-less skip guard for
	 * real visitors whose action arrived via Store API, mobile app, or AJAX
	 * with cookies forwarded.
	 */
	public function test_get_visitor_id_returns_cookie_value_in_rest_context(): void {
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}

		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';

		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$method     = $reflection->getMethod( 'get_visitor_id' );
		$method->setAccessible( true );
		$visitor_id = $method->invoke( null );

		$this->assertSame( 'test-visitor-id-1234567890ab', $visitor_id, 'Cookie value should be returned verbatim when present.' );
	}

	/**
	 * Bot user-agents should still be filtered out. This test guards against the
	 * new visitor-id check accidentally relaxing the existing bot check.
	 */
	public function test_record_event_skips_bots(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1 (+http://www.google.com/bot.html)';

		$result = WC_Analytics_Tracking::record_event( 'add_to_cart' );

		$this->assertTrue( $result, 'record_event should skip bot traffic.' );

		unset( $_SERVER['HTTP_USER_AGENT'] );
	}
}
