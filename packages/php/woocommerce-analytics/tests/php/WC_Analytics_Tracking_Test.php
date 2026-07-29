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
	 * Reset cached static state after each test.
	 *
	 * Note: PHP constants like REST_REQUEST cannot be undefined once set, so
	 * any constant a test defines leaks for the rest of the process. Tests
	 * that depend on the absence of those constants must run first, or move
	 * to a class annotated with @runInSeparateProcess.
	 */
	public function tear_down(): void {
		$this->reset_tracking_static_state();
		unset( $_COOKIE['tk_ai'] );
		parent::tear_down();
	}

	/**
	 * Use reflection to clear the `cached_visitor_id` and `pixel_batch_queue`
	 * statics between tests so each case starts from a known-empty state.
	 */
	private function reset_tracking_static_state(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );

		$visitor = $reflection->getProperty( 'cached_visitor_id' );
		$visitor->setAccessible( true );
		$visitor->setValue( null, null );

		$queue = $reflection->getProperty( 'pixel_batch_queue' );
		$queue->setAccessible( true );
		$queue->setValue( null, array() );
	}

	/**
	 * Read the current `pixel_batch_queue` static via reflection. Used to
	 * verify whether a pixel was queued for shutdown delivery.
	 *
	 * @return array
	 */
	private function get_pixel_batch_queue(): array {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		return $property->getValue();
	}

	/**
	 * Must run BEFORE any test defines REST_REQUEST: once leaked, the cookie-less guard
	 * would skip on its own and mask a broken bot check. The injected tk_ai cookie is a
	 * second belt so the bot path is the only thing that can produce the skip.
	 */
	public function test_record_event_skips_bots(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1 (+http://www.google.com/bot.html)';
		$_COOKIE['tk_ai']           = 'test-visitor-id-1234567890ab';

		$captured = array();
		$filter   = function ( $pre, $args, $url ) use ( &$captured ) {
			if ( false !== strpos( $url, 'pixel.wp.com' ) ) {
				$captured[] = $url;
			}
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '',
			);
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$result = WC_Analytics_Tracking::record_event( 'add_to_cart' );

		remove_filter( 'pre_http_request', $filter, 10 );
		unset( $_SERVER['HTTP_USER_AGENT'] );

		$this->assertTrue( $result, 'record_event should skip bot traffic.' );
		$this->assertCount( 0, $captured, 'No pixel.wp.com request should fire for bot UA.' );
		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'No pixel should be queued for bot UA.' );
	}

	/**
	 * With no `tk_ai` cookie and proxy tracking off, get_visitor_id() must return null
	 * instead of minting a fresh id. Minting-and-attributing here was the Nov 2025
	 * regression that produced one-event "visitors" — overwhelmingly UA-spoofing crawler
	 * traffic that never persists a cookie — inflating Tracks session counts.
	 *
	 * Order-independent: the cookie-less path no longer branches on REST_REQUEST, and the
	 * method no longer sets a cookie server-side (real browsers get `tk_ai` client-side).
	 */
	public function test_get_visitor_id_returns_null_for_non_rest_request_without_cookie(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$method     = $reflection->getMethod( 'get_visitor_id' );
		$method->setAccessible( true );

		$this->assertNull(
			$method->invoke( null ),
			'A freshly-minted id must not be returned for cookie-less non-REST requests.'
		);
	}

	/**
	 * Companion behavioral assertion: record_event() emits/queues no pixel for a
	 * cookie-less request — the crawler path that inflated session counts. Order-independent
	 * (the skip no longer depends on REST_REQUEST).
	 */
	public function test_record_event_skips_non_rest_request_without_cookie(): void {
		$captured = array();
		$filter   = function ( $pre, $args, $url ) use ( &$captured ) {
			if ( false !== strpos( $url, 'pixel.wp.com' ) ) {
				$captured[] = $url;
			}
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '',
			);
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$result = WC_Analytics_Tracking::record_event( 'add_to_cart' );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertTrue( $result, 'record_event should return true (skipped) for cookie-less non-REST contexts.' );
		$this->assertCount( 0, $captured, 'No pixel.wp.com request should fire when no tk_ai cookie is present in a non-REST context.' );
		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'No pixel should be queued when no tk_ai cookie is present in a non-REST context.' );
	}

	/**
	 * record_event() should short-circuit (no pixel emitted) when called from
	 * a REST request that has no `tk_ai` cookie. Generating a one-shot id
	 * here would fragment Nosara/Tracks sessions across cookie-less
	 * integrations.
	 *
	 * Asserts both the skip return value AND the absence of any HTTP egress
	 * (intercepted via `pre_http_request`) or queued batch entry — since
	 * `record_event()` returns true both on skip and on successful emission,
	 * the return value alone is not sufficient evidence of the skip.
	 */
	public function test_record_event_skips_rest_request_without_cookie(): void {
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}

		$captured = array();
		$filter   = function ( $pre, $args, $url ) use ( &$captured ) {
			if ( false !== strpos( $url, 'pixel.wp.com' ) ) {
				$captured[] = $url;
			}
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '',
			);
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$result = WC_Analytics_Tracking::record_event( 'add_to_cart' );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertTrue( $result, 'record_event should return true (skipped) for cookie-less REST contexts.' );
		$this->assertCount( 0, $captured, 'No pixel.wp.com request should fire when no tk_ai cookie is present in REST context.' );
		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'No pixel should be queued for batch when no tk_ai cookie is present in REST context.' );
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
}
