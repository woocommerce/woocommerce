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
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
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
		remove_all_filters( 'pre_http_request' );
		parent::tearDown();
	}

	/**
	 * Test that a successful REST request returns the decoded body.
	 */
	public function test_rest_request_success(): void {
		add_filter(
			'pre_http_request',
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
			10,
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
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				unset( $preempt, $args );
				$this->assertStringContainsString( 'limit=10', $url );
				$this->assertStringContainsString( 'offset=20', $url );
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'ok' => true ) ),
				);
			},
			10,
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
		add_filter(
			'pre_http_request',
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
		add_filter(
			'pre_http_request',
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
	 * Test that get_site_id() returns the configured ID, or WP_Error if missing.
	 */
	public function test_get_site_id(): void {
		$this->assertSame( 'site-123', $this->client->get_site_id() );

		$empty = new WebflowClient( array() );
		$this->assertInstanceOf( WP_Error::class, $empty->get_site_id() );
	}
}
