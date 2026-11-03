<?php
/**
 * Tests for the guard against external HTTP requests from tests.
 *
 * @package WooCommerce\Tests\Framework
 */

declare( strict_types = 1 );

/**
 * WC_External_HTTP_Guard_Test class.
 *
 * Requests go straight to the guard's filter callback, so nothing reaches the network.
 */
class WC_External_HTTP_Guard_Test extends WC_Unit_Test_Case {

	/**
	 * Drop requests recorded here, so the listener does not fail this test for them.
	 */
	public function tearDown(): void {
		WC_External_HTTP_Guard::reset( false );
		parent::tearDown();
	}

	/**
	 * @testdox An unanswered request to an external host is blocked and recorded without its query string.
	 */
	public function test_external_request_is_blocked_and_recorded(): void {
		$result = WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'POST' ), 'https://api.example.com/v1/items?key=secret#top' );

		$this->assertWPError( $result, 'The guard should answer an external request with an error.' );
		$this->assertSame( WC_External_HTTP_Guard::ERROR_CODE, $result->get_error_code(), 'The error should name the guard.' );

		$blocked = WC_External_HTTP_Guard::get_blocked_requests();
		$this->assertCount( 1, $blocked, 'The blocked request should be recorded once.' );
		$this->assertStringStartsWith( 'POST https://api.example.com/v1/items (from ', $blocked[0], 'The record should hold the method and the URL without its query string.' );
		$this->assertStringContainsString( 'class-wc-external-http-guard-test.php:', $blocked[0], 'The record should name the calling file.' );
		$this->assertStringNotContainsString( 'secret', $blocked[0], 'Query strings can hold secrets and should not be recorded.' );
	}

	/**
	 * @testdox A request to an external IP address is blocked.
	 *
	 * @testWith ["http://203.0.113.10/feed"]
	 *           ["https://[2001:db8::1]/feed"]
	 *
	 * @param string $url The request URL.
	 */
	public function test_external_ip_address_is_blocked( string $url ): void {
		$result = WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), $url );

		$this->assertWPError( $result, "The guard should block {$url}." );
		$this->assertCount( 1, WC_External_HTTP_Guard::get_blocked_requests(), "The request to {$url} should be recorded." );
	}

	/**
	 * @testdox A request to a loopback host or without a host passes through untouched.
	 *
	 * @testWith ["http://localhost:8188/wp-admin/admin-ajax.php"]
	 *           ["http://LOCALHOST/feed"]
	 *           ["http://127.0.0.1/feed"]
	 *           ["http://[::1]/feed"]
	 *           ["bad-data-source.json"]
	 *
	 * @param string $url The request URL.
	 */
	public function test_local_request_passes_through( string $url ): void {
		$this->assertFalse( WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), $url ), "The guard should not answer {$url}." );
		$this->assertSame( array(), WC_External_HTTP_Guard::get_blocked_requests(), "The request to {$url} should not be recorded." );
	}

	/**
	 * @testdox A request to the test site's own URLs passes through untouched.
	 */
	public function test_test_site_request_passes_through(): void {
		foreach ( array( home_url( '/shop/' ), site_url( '/wp-admin/admin-ajax.php' ) ) as $url ) {
			$this->assertFalse( WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), $url ), "The guard should not answer {$url}." );
		}
		$this->assertSame( array(), WC_External_HTTP_Guard::get_blocked_requests(), 'Requests to the test site should not be recorded.' );
	}

	/**
	 * @testdox A request another filter already answered passes through untouched.
	 */
	public function test_answered_request_passes_through(): void {
		$response = array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
		);
		$error    = new WP_Error( 'mocked', 'Mocked failure.' );

		$this->assertSame( $response, WC_External_HTTP_Guard::filter_request( $response, array( 'method' => 'GET' ), 'https://api.example.com/feed' ), 'A mocked response should be returned unchanged.' );
		$this->assertSame( $error, WC_External_HTTP_Guard::filter_request( $error, array( 'method' => 'GET' ), 'https://api.example.com/feed' ), 'A mocked error should be returned unchanged.' );
		$this->assertSame( array(), WC_External_HTTP_Guard::get_blocked_requests(), 'Answered requests should not be recorded.' );
	}

	/**
	 * @testdox An external request passes through untouched while external requests are allowed.
	 */
	public function test_external_request_passes_through_when_allowed(): void {
		WC_External_HTTP_Guard::reset( true );

		$this->assertFalse( WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'https://api.example.com/feed' ), 'An allowed request should reach the network.' );
		$this->assertSame( array(), WC_External_HTTP_Guard::get_blocked_requests(), 'An allowed request should not be recorded.' );
	}

	/**
	 * @testdox The guard is registered on pre_http_request after every other filter.
	 */
	public function test_guard_runs_last(): void {
		$this->assertSame( PHP_INT_MAX, has_filter( 'pre_http_request', array( WC_External_HTTP_Guard::class, 'filter_request' ) ), 'The guard should be registered by the test bootstrap at the last priority.' );
	}
}
