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
	 * Drop requests recorded here and disallow external requests again, so the listener does not fail this test.
	 */
	public function tearDown(): void {
		try {
			WC_External_HTTP_Guard::take_blocked_requests();
			WC_External_HTTP_Guard::allow_external( false );
		} finally {
			parent::tearDown();
		}
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
		$this->assertStringNotContainsString( 'secret', $blocked[0], 'The record should not include the query string.' );
	}

	/**
	 * @testdox A request to an external IP address or a disguised external host is blocked.
	 *
	 * @testWith ["http://203.0.113.10/feed"]
	 *           ["https://[2001:db8::1]/feed"]
	 *           ["https://localhost@evil.example/feed"]
	 *           ["ftp:https://api.example.com/feed"]
	 *           ["ftp://example.org/path:https://api.example.com/feed"]
	 *
	 * @param string $url The request URL.
	 */
	public function test_external_ip_address_is_blocked( string $url ): void {
		$result = WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), $url );

		$this->assertWPError( $result, "The guard should block {$url}." );
		$this->assertCount( 1, WC_External_HTTP_Guard::get_blocked_requests(), "The request to {$url} should be recorded." );
	}

	/**
	 * @testdox A request to a loopback host, or one WordPress refuses to send, passes through untouched.
	 *
	 * @testWith ["http://localhost:8188/wp-admin/admin-ajax.php"]
	 *           ["http://LOCALHOST/feed"]
	 *           ["http://127.0.0.1/feed"]
	 *           ["http://[::1]/feed"]
	 *           ["bad-data-source.json"]
	 *           ["ftp://api.example.com/feed"]
	 *           ["//api.example.com/feed"]
	 *
	 * @param string $url The request URL.
	 */
	public function test_request_that_stays_local_passes_through( string $url ): void {
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
		WC_External_HTTP_Guard::allow_external( true );

		$this->assertFalse( WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'https://api.example.com/feed' ), 'An allowed request should reach the network.' );
		$this->assertSame( array(), WC_External_HTTP_Guard::get_blocked_requests(), 'An allowed request should not be recorded.' );
	}

	/**
	 * @testdox A request sent through a WordPress admin helper names the code that called the helper.
	 */
	public function test_record_names_caller_outside_wordpress(): void {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$result = download_url( 'https://api.example.com/plugin.zip' );

		$this->assertWPError( $result, 'The guard should block the download.' );
		$this->assertSame( WC_External_HTTP_Guard::ERROR_CODE, $result->get_error_code(), 'The error should name the guard.' );
		$blocked = WC_External_HTTP_Guard::get_blocked_requests();
		$this->assertCount( 1, $blocked, 'The blocked download should be recorded once.' );
		$this->assertStringContainsString( 'class-wc-external-http-guard-test.php:', $blocked[0], 'The record should name this test, not the WordPress helper it called.' );
	}

	/**
	 * @testdox A disguised request is recorded as the URL WordPress would send after stripping disallowed protocols.
	 */
	public function test_disguised_request_is_recorded_as_sent(): void {
		WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'ftp://example.org/path:https://api.example.com/feed' );

		$blocked = WC_External_HTTP_Guard::get_blocked_requests();
		$this->assertCount( 1, $blocked, 'The disguised request should be recorded once.' );
		$this->assertStringStartsWith( 'GET https://api.example.com/feed (from ', $blocked[0], 'The record should hold the URL WordPress would request.' );
	}

	/**
	 * @testdox A redirect from an allowed request to an external host is blocked and recorded.
	 */
	public function test_redirect_to_external_host_is_blocked(): void {
		try {
			self::dispatch_redirect( 'https://api.example.com/next?token=secret' );
			$this->fail( 'The guard should stop a redirect to an external host.' );
		} catch ( WpOrg\Requests\Exception $e ) {
			$this->assertSame( WC_External_HTTP_Guard::ERROR_CODE, $e->getType(), 'The exception should name the guard.' );
		}

		$blocked = WC_External_HTTP_Guard::get_blocked_requests();
		$this->assertCount( 1, $blocked, 'The blocked redirect should be recorded once.' );
		$this->assertStringStartsWith( 'REDIRECT https://api.example.com/next (from ', $blocked[0], 'The record should name the redirect target without its query string.' );
	}

	/**
	 * @testdox A redirect to the test site is followed untouched.
	 */
	public function test_redirect_to_test_site_passes_through(): void {
		self::dispatch_redirect( home_url( '/next/' ) );

		$this->assertSame( array(), WC_External_HTTP_Guard::get_blocked_requests(), 'A redirect to the test site should not be recorded.' );
	}

	/**
	 * Fire the hook WordPress's HTTP API fires before following a redirect, as Requests does.
	 *
	 * @param string $location The redirect target.
	 */
	private static function dispatch_redirect( string $location ): void {
		$headers = array();
		$data    = array();
		$options = array();
		( new WP_HTTP_Requests_Hooks( home_url( '/start/' ), array() ) )->dispatch( 'requests.before_redirect', array( &$location, &$headers, &$data, &$options, null ) );
	}

	/**
	 * @testdox The guard is registered on pre_http_request after every other filter.
	 */
	public function test_guard_runs_last(): void {
		$this->assertSame( PHP_INT_MAX, has_filter( 'pre_http_request', array( WC_External_HTTP_Guard::class, 'filter_request' ) ), 'The guard should be registered at the last priority.' );
		$this->assertSame( PHP_INT_MAX, has_action( 'requests-requests.before_redirect', array( WC_External_HTTP_Guard::class, 'check_redirect' ) ), 'The redirect check should be registered at the last priority.' );
	}
}
