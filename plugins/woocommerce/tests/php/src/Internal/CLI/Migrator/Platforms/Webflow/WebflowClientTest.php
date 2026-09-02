<?php
/**
 * Webflow Client Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow;

use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow\WebflowClient;
use WC_Unit_Test_Case;
use WP_Error;

// Shadows sleep() in the WebflowClient namespace so retry/backoff tests run instantly.
require_once __DIR__ . '/functions-mock.php';

/**
 * Tests for WebflowClient.
 */
class WebflowClientTest extends WC_Unit_Test_Case {

	/**
	 * Default test credentials.
	 *
	 * @var array
	 */
	private array $test_credentials;

	/**
	 * Client under test.
	 *
	 * @var WebflowClient
	 */
	private WebflowClient $client;

	/**
	 * HTTP filter callbacks registered during the test, removed in tearDown.
	 *
	 * @var array<callable>
	 */
	private array $http_filters = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->http_filters     = array();
		$this->test_credentials = array(
			'site_id'      => 'site-123',
			'access_token' => 'ws-test-token',
		);
		$this->client           = new WebflowClient( $this->test_credentials );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->http_filters as $cb ) {
			remove_filter( 'pre_http_request', $cb );
		}
		$this->http_filters = array();
		parent::tearDown();
	}

	/**
	 * Test that a successful REST request returns the decoded body.
	 */
	public function test_rest_request_success(): void {
		$this->add_http_filter(
			function ( $preempt, $args, $url ) {
				unset( $preempt );
				$this->assertStringContainsString( 'api.webflow.com/v2/sites/site-123/products', $url );
				$this->assertSame( 'Bearer ws-test-token', $args['headers']['Authorization'] );
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'items'      => array(),
							'pagination' => array( 'total' => 0 ),
						)
					),
				);
			},
			3
		);

		$result = $this->client->rest_request( '/sites/site-123/products' );

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertIsObject( $result );
		$this->assertSame( 0, (int) $result->pagination->total );
	}

	/**
	 * Test that query parameters end up in the URL.
	 */
	public function test_rest_request_with_query_params(): void {
		$this->add_http_filter(
			function ( $preempt, $args, $url ) {
				unset( $preempt, $args );
				$this->assertStringContainsString( 'limit=10', $url );
				$this->assertStringContainsString( 'offset=20', $url );
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'ok' => true ) ),
				);
			},
			3
		);

		$result = $this->client->rest_request(
			'/sites/site-123/products',
			array(
				'limit'  => 10,
				'offset' => 20,
			)
		);

		$this->assertNotInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test that missing credentials produce a WP_Error.
	 */
	public function test_rest_request_missing_credentials(): void {
		$empty_client = new WebflowClient( array() );

		$result = $empty_client->rest_request( '/sites/whatever/products' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'api_error', $result->get_error_code() );
		$this->assertStringContainsString( 'not configured', $result->get_error_message() );
	}

	/**
	 * Test that HTTP errors surface as WP_Error.
	 */
	public function test_rest_request_http_error(): void {
		$this->add_http_filter(
			function () {
				return new WP_Error( 'http_request_failed', 'Connection refused' );
			}
		);

		$result = $this->client->rest_request( '/sites/site-123/products' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'Connection refused', $result->get_error_message() );
	}

	/**
	 * Test that a non-2xx API response surfaces as WP_Error with the API message.
	 */
	public function test_rest_request_api_error(): void {
		$this->add_http_filter(
			function () {
				return array(
					'response' => array( 'code' => 403 ),
					'body'     => wp_json_encode( array( 'message' => 'OAuthForbidden' ) ),
				);
			}
		);

		$result = $this->client->rest_request( '/sites/site-123/products' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( '403', $result->get_error_message() );
		$this->assertStringContainsString( 'OAuthForbidden', $result->get_error_message() );
	}

	/**
	 * Test that a 429 response is retried and a following success is returned.
	 *
	 * Also exercises the retry-after header parsing. sleep() is shadowed (see
	 * functions-mock.php), so this does not actually wait.
	 */
	public function test_rest_request_retries_on_429_then_succeeds(): void {
		$attempts = 0;
		$this->add_http_filter(
			function () use ( &$attempts ) {
				++$attempts;
				if ( 1 === $attempts ) {
					return array(
						'response' => array( 'code' => 429 ),
						'headers'  => array( 'retry-after' => '1' ),
						'body'     => wp_json_encode( array( 'message' => 'TooManyRequests' ) ),
					);
				}
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode(
						array(
							'items'      => array(),
							'pagination' => array( 'total' => 0 ),
						)
					),
				);
			}
		);

		$result = $this->client->rest_request( '/sites/site-123/products' );

		$this->assertSame( 2, $attempts );
		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertIsObject( $result );
		$this->assertSame( 0, (int) $result->pagination->total );
	}

	/**
	 * Test that repeated 429 responses exhaust the retry budget and surface a WP_Error.
	 */
	public function test_rest_request_exhausts_retry_budget_on_repeated_429(): void {
		$attempts = 0;
		$this->add_http_filter(
			function () use ( &$attempts ) {
				++$attempts;
				return array(
					'response' => array( 'code' => 429 ),
					'headers'  => array( 'retry-after' => '1' ),
					'body'     => wp_json_encode( array( 'message' => 'TooManyRequests' ) ),
				);
			}
		);

		$result = $this->client->rest_request( '/sites/site-123/products' );

		// Initial attempt + MAX_RETRIES (3) retries = 4 requests.
		$this->assertSame( 4, $attempts );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'api_error', $result->get_error_code() );
		$this->assertStringContainsString( 'retry budget', $result->get_error_message() );
	}

	/**
	 * Test that get_site_id() returns the configured ID, or WP_Error if missing.
	 */
	public function test_get_site_id(): void {
		$this->assertSame( 'site-123', $this->client->get_site_id() );

		$empty = new WebflowClient( array() );
		$this->assertInstanceOf( WP_Error::class, $empty->get_site_id() );
	}

	/**
	 * Register a pre_http_request filter and track it for cleanup.
	 *
	 * @param callable $callback   Filter callback.
	 * @param int      $args_count Number of accepted arguments (default 1).
	 */
	private function add_http_filter( callable $callback, int $args_count = 1 ): void {
		add_filter( 'pre_http_request', $callback, 10, $args_count );
		$this->http_filters[] = $callback;
	}
}
