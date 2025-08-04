<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify;

use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyClient;
use Automattic\WooCommerce\Internal\CLI\Migrator\Core\CredentialManager;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for ShopifyClient.
 */
class ShopifyClientTest extends WC_Unit_Test_Case {

	/**
	 * The ShopifyClient instance.
	 *
	 * @var ShopifyClient
	 */
	private $client;

	/**
	 * Mock credential manager.
	 *
	 * @var CredentialManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_credential_manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->client                  = new ShopifyClient();
		$this->mock_credential_manager = $this->createMock( CredentialManager::class );
		$this->client->init( $this->mock_credential_manager );
	}

	/**
	 * Test successful REST API request.
	 */
	public function test_rest_request_success(): void {
		// Mock credentials.
		$this->mock_credential_manager->method( 'get_credentials' )
			->with( 'shopify' )
			->willReturn(
				array(
					'shop_url'     => 'test-store.myshopify.com',
					'access_token' => 'test-token-123',
				)
			);

		// Mock successful HTTP response.
		$mock_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'count' => 42 ) ),
		);

		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) use ( $mock_response ) {
				// Verify the request URL is correct.
				$this->assertStringContainsString( 'test-store.myshopify.com/admin/api/2025-04/products/count.json', $url );
				// Verify the authorization header.
				$this->assertEquals( 'test-token-123', $parsed_args['headers']['X-Shopify-Access-Token'] );
				return $mock_response;
			},
			10,
			3
		);

		$result = $this->client->rest_request( '/products/count.json' );

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 42, $result->count );

		// Clean up filter.
		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Test REST request with query parameters.
	 */
	public function test_rest_request_with_query_params(): void {
		$this->mock_credential_manager->method( 'get_credentials' )
			->willReturn(
				array(
					'shop_url'     => 'test-store.myshopify.com',
					'access_token' => 'test-token-123',
				)
			);

		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) {
				// Verify query parameters are added to URL.
				$this->assertStringContainsString( 'status=active', $url );
				$this->assertStringContainsString( 'vendor=Nike', $url );
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'count' => 15 ) ),
				);
			},
			10,
			3
		);

		$result = $this->client->rest_request(
			'/products/count.json',
			array(
				'status' => 'active',
				'vendor' => 'Nike',
			)
		);

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 15, $result->count );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Test REST request with missing credentials.
	 */
	public function test_rest_request_missing_credentials(): void {
		$this->mock_credential_manager->method( 'get_credentials' )
			->with( 'shopify' )
			->willReturn( array() );

		$result = $this->client->rest_request( '/products/count.json' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'api_error', $result->get_error_code() );
		$this->assertStringContainsString( 'not configured', $result->get_error_message() );
	}

	/**
	 * Test REST request with partial credentials.
	 */
	public function test_rest_request_partial_credentials(): void {
		$this->mock_credential_manager->method( 'get_credentials' )
			->with( 'shopify' )
			->willReturn(
				array(
					'shop_url' => 'test-store.myshopify.com',
				// Missing access_token.
				)
			);

		$result = $this->client->rest_request( '/products/count.json' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'api_error', $result->get_error_code() );
	}

	/**
	 * Test REST request with HTTP error.
	 */
	public function test_rest_request_http_error(): void {
		$this->mock_credential_manager->method( 'get_credentials' )
			->willReturn(
				array(
					'shop_url'     => 'test-store.myshopify.com',
					'access_token' => 'test-token-123',
				)
			);

		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'http_request_failed', 'Connection timeout' );
			}
		);

		$result = $this->client->rest_request( '/products/count.json' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'api_error', $result->get_error_code() );
		$this->assertStringContainsString( 'Connection timeout', $result->get_error_message() );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Test REST request with API error response.
	 */
	public function test_rest_request_api_error(): void {
		$this->mock_credential_manager->method( 'get_credentials' )
			->willReturn(
				array(
					'shop_url'     => 'test-store.myshopify.com',
					'access_token' => 'invalid-token',
				)
			);

		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 401 ),
					'body'     => wp_json_encode(
						array(
							'errors' => array( 'Unauthorized' ),
						)
					),
				);
			}
		);

		$result = $this->client->rest_request( '/products/count.json' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'api_error', $result->get_error_code() );
		$this->assertStringContainsString( '401', $result->get_error_message() );
		$this->assertStringContainsString( 'Unauthorized', $result->get_error_message() );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Test REST request with invalid JSON response.
	 */
	public function test_rest_request_invalid_json(): void {
		$this->mock_credential_manager->method( 'get_credentials' )
			->willReturn(
				array(
					'shop_url'     => 'test-store.myshopify.com',
					'access_token' => 'test-token-123',
				)
			);

		add_filter(
			'pre_http_request',
			function () {
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => 'invalid json {',
				);
			}
		);

		$result = $this->client->rest_request( '/products/count.json' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'api_error', $result->get_error_code() );
		$this->assertStringContainsString( 'Failed to decode', $result->get_error_message() );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Test URL building with protocol handling.
	 */
	public function test_url_building_protocol_handling(): void {
		$this->mock_credential_manager->method( 'get_credentials' )
			->willReturn(
				array(
					'shop_url'     => 'test-store.myshopify.com', // No protocol.
					'access_token' => 'test-token-123',
				)
			);

		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) {
				// Should add https:// protocol.
				$this->assertStringStartsWith( 'https://test-store.myshopify.com', $url );
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'count' => 0 ) ),
				);
			},
			10,
			3
		);

		$this->client->rest_request( '/products/count.json' );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Test POST request with body.
	 */
	public function test_post_request_with_body(): void {
		$this->mock_credential_manager->method( 'get_credentials' )
			->willReturn(
				array(
					'shop_url'     => 'test-store.myshopify.com',
					'access_token' => 'test-token-123',
				)
			);

		$request_body = array( 'product' => array( 'title' => 'Test Product' ) );

		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args ) use ( $request_body ) {
				$this->assertEquals( 'POST', $parsed_args['method'] );
				$this->assertEquals( wp_json_encode( $request_body ), $parsed_args['body'] );
				$this->assertEquals( 'application/json', $parsed_args['headers']['Content-Type'] );
				return array(
					'response' => array( 'code' => 201 ),
					'body'     => wp_json_encode( array( 'product' => array( 'id' => 123 ) ) ),
				);
			},
			10,
			3
		);

		$result = $this->client->rest_request( '/products.json', array(), 'POST', $request_body );

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 123, $result->product->id );

		remove_all_filters( 'pre_http_request' );
	}
}
