<?php
/**
 * Shopify Fetcher Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify;

use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyFetcher;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyClient;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for ShopifyFetcher.
 */
class ShopifyFetcherTest extends WC_Unit_Test_Case {

	/**
	 * The ShopifyFetcher instance.
	 *
	 * @var ShopifyFetcher
	 */
	private $fetcher;

	/**
	 * Mock ShopifyClient.
	 *
	 * @var ShopifyClient|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_shopify_client;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock WP_CLI class if it doesn't exist.
		if ( ! class_exists( 'WP_CLI' ) ) {
			require_once __DIR__ . '/../../Mocks/MockWPCLI.php';
		}

		$credentials = array(
			'shop_url'     => 'https://test-shop.myshopify.com',
			'access_token' => 'test-access-token',
		);

		$this->fetcher             = new ShopifyFetcher( $credentials );
		$this->mock_shopify_client = $this->createMock( ShopifyClient::class );

		// Use reflection to inject the mock client.
		$reflection = new \ReflectionClass( $this->fetcher );
		$property   = $reflection->getProperty( 'shopify_client' );
		$property->setAccessible( true );
		$property->setValue( $this->fetcher, $this->mock_shopify_client );
	}

	/**
	 * Test successful product count fetching.
	 */
	public function test_fetch_total_count_success(): void {
		// Mock successful API response.
		$mock_response = (object) array( 'count' => 1023 );

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'rest_request' )
			->with(
				'/products/count.json',
				array(), // No filters.
				'GET',
				array()
			)
			->willReturn( $mock_response );

		$result = $this->fetcher->fetch_total_count( array() );

		$this->assertEquals( 1023, $result );
	}

	/**
	 * Test product count fetching with status filter.
	 */
	public function test_fetch_total_count_with_status_filter(): void {
		$mock_response = (object) array( 'count' => 1021 );

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'rest_request' )
			->with(
				'/products/count.json',
				array( 'status' => 'active' ), // Status filter applied.
				'GET',
				array()
			)
			->willReturn( $mock_response );

		$result = $this->fetcher->fetch_total_count( array( 'status' => 'ACTIVE' ) );

		$this->assertEquals( 1021, $result );
	}

	/**
	 * Test product count fetching with multiple filters.
	 */
	public function test_fetch_total_count_with_multiple_filters(): void {
		$mock_response = (object) array( 'count' => 25 );

		$expected_query_params = array(
			'status'         => 'draft',
			'vendor'         => 'Nike',
			'product_type'   => 'Shoes',
			'created_at_min' => '2024-01-01T00:00:00Z',
			'created_at_max' => '2024-12-31T23:59:59Z',
			'updated_at_min' => '2024-06-01T00:00:00Z',
			'updated_at_max' => '2024-06-30T23:59:59Z',
		);

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'rest_request' )
			->with(
				'/products/count.json',
				$expected_query_params,
				'GET',
				array()
			)
			->willReturn( $mock_response );

		$filter_args = array(
			'status'         => 'draft',
			'vendor'         => 'Nike',
			'product_type'   => 'Shoes',
			'created_at_min' => '2024-01-01T00:00:00Z',
			'created_at_max' => '2024-12-31T23:59:59Z',
			'updated_at_min' => '2024-06-01T00:00:00Z',
			'updated_at_max' => '2024-06-30T23:59:59Z',
		);

		$result = $this->fetcher->fetch_total_count( $filter_args );

		$this->assertEquals( 25, $result );
	}

	/**
	 * Test product count with specific IDs.
	 */
	public function test_fetch_total_count_with_ids_array(): void {
		// When IDs are provided, should count them directly without API call.
		$this->mock_shopify_client->expects( $this->never() )
			->method( 'rest_request' );

		$result = $this->fetcher->fetch_total_count(
			array(
				'ids' => array( '123', '456', '789' ),
			)
		);

		$this->assertEquals( 3, $result );
	}

	/**
	 * Test product count with comma-separated IDs.
	 */
	public function test_fetch_total_count_with_ids_string(): void {
		// When IDs are provided as string, should count them directly.
		$this->mock_shopify_client->expects( $this->never() )
			->method( 'rest_request' );

		$result = $this->fetcher->fetch_total_count(
			array(
				'ids' => '123,456,789,999',
			)
		);

		$this->assertEquals( 4, $result );
	}

	/**
	 * Test product count with IDs containing empty values.
	 */
	public function test_fetch_total_count_with_ids_filtered(): void {
		$this->mock_shopify_client->expects( $this->never() )
			->method( 'rest_request' );

		$result = $this->fetcher->fetch_total_count(
			array(
				'ids' => array( '123', '', '456', null, '789' ),
			)
		);

		// Should filter out empty values, so only 3 valid IDs.
		$this->assertEquals( 3, $result );
	}

	/**
	 * Test product count with API error.
	 */
	public function test_fetch_total_count_api_error(): void {
		$api_error = new WP_Error( 'api_error', 'Unauthorized' );

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'rest_request' )
			->willReturn( $api_error );

		$result = $this->fetcher->fetch_total_count( array() );

		$this->assertEquals( 0, $result );
		$this->assertStringContainsString( 'Could not fetch total product count', \WP_CLI::$last_warning_message );
	}

	/**
	 * Test product count with missing count field in response.
	 */
	public function test_fetch_total_count_missing_count_field(): void {
		// Mock response without count field.
		$mock_response = (object) array( 'products' => array() );

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'rest_request' )
			->willReturn( $mock_response );

		$result = $this->fetcher->fetch_total_count( array() );

		$this->assertEquals( 0, $result );
		$this->assertStringContainsString( 'missing count field', \WP_CLI::$last_warning_message );
	}

	/**
	 * Test fetch_batch method returns stub data.
	 */
	public function test_fetch_batch_returns_stub(): void {
		// The fetch_batch method should still return stub data for now.
		$result = $this->fetcher->fetch_batch( array( 'limit' => 10 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'cursor', $result );
		$this->assertArrayHasKey( 'has_next_page', $result );
		$this->assertEquals( array(), $result['items'] );
		$this->assertNull( $result['cursor'] );
		$this->assertFalse( $result['has_next_page'] );
	}

	/**
	 * Test status filter conversion to lowercase.
	 */
	public function test_status_filter_conversion(): void {
		$mock_response = (object) array( 'count' => 5 );
		$this->mock_shopify_client->expects( $this->once() )
			->method( 'rest_request' )
			->with(
				'/products/count.json',
				array( 'status' => 'archived' ), // Should be lowercase.
				'GET',
				array()
			)
			->willReturn( $mock_response );

		// Pass uppercase status, should be converted to lowercase.
		$result = $this->fetcher->fetch_total_count( array( 'status' => 'ARCHIVED' ) );

		$this->assertEquals( 5, $result );
	}

	/**
	 * Test that unknown filter arguments are ignored.
	 */
	public function test_unknown_filters_ignored(): void {
		$mock_response = (object) array( 'count' => 100 );

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'rest_request' )
			->with(
				'/products/count.json',
				array( 'status' => 'active' ), // Only known filters should be passed.
				'GET',
				array()
			)
			->willReturn( $mock_response );

		$filter_args = array(
			'status'        => 'active',
			'unknown_arg'   => 'should_be_ignored',
			'another_bogus' => 'also_ignored',
		);

		$result = $this->fetcher->fetch_total_count( $filter_args );

		$this->assertEquals( 100, $result );
	}

	/**
	 * Test successful product batch fetching with GraphQL.
	 */
	public function test_fetch_batch_success(): void {
		$mock_response_data = (object) array(
			'products' => (object) array(
				'edges'    => array(
					(object) array(
						'cursor' => 'cursor1',
						'node'   => (object) array(
							'id'       => 'gid://shopify/Product/123',
							'title'    => 'Test Product 1',
							'status'   => 'ACTIVE',
							'variants' => (object) array(
								'edges' => array(
									(object) array(
										'node' => (object) array(
											'id'    => 'gid://shopify/ProductVariant/456',
											'title' => 'Default Title',
										),
									),
								),
							),
						),
					),
					(object) array(
						'cursor' => 'cursor2',
						'node'   => (object) array(
							'id'       => 'gid://shopify/Product/124',
							'title'    => 'Test Product 2',
							'status'   => 'DRAFT',
							'variants' => (object) array(
								'edges' => array(),
							),
						),
					),
				),
				'pageInfo' => (object) array(
					'hasNextPage' => true,
					'endCursor'   => 'cursor2',
				),
			),
		);

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'graphql_request' )
			->with(
				$this->stringContains( 'query GetShopifyProducts' ),
				array(
					'first'         => 5,
					'variantsFirst' => 100,
				)
			)
			->willReturn( $mock_response_data );

		$result = $this->fetcher->fetch_batch( array( 'limit' => 5 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'has_next_page', $result );
		$this->assertArrayHasKey( 'cursor', $result );

		$this->assertCount( 2, $result['items'] );
		$this->assertTrue( $result['has_next_page'] );
		$this->assertEquals( 'cursor2', $result['cursor'] );

		// Verify product data structure.
		$first_product = $result['items'][0];
		$this->assertEquals( 'Test Product 1', $first_product->node->title );
		$this->assertEquals( 'ACTIVE', $first_product->node->status );
		$this->assertCount( 1, $first_product->node->variants->edges );
	}

	/**
	 * Test product batch fetching with cursor pagination.
	 */
	public function test_fetch_batch_with_cursor(): void {
		$mock_response_data = (object) array(
			'products' => (object) array(
				'edges'    => array(
					(object) array(
						'cursor' => 'cursor3',
						'node'   => (object) array(
							'id'    => 'gid://shopify/Product/125',
							'title' => 'Test Product 3',
						),
					),
				),
				'pageInfo' => (object) array(
					'hasNextPage' => false,
					'endCursor'   => 'cursor3',
				),
			),
		);

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'graphql_request' )
			->with(
				$this->stringContains( 'query GetShopifyProducts' ),
				array(
					'first'         => 3,
					'after'         => 'cursor2',
					'variantsFirst' => 100,
				)
			)
			->willReturn( $mock_response_data );

		$result = $this->fetcher->fetch_batch(
			array(
				'limit'        => 3,
				'after_cursor' => 'cursor2',
			)
		);

		$this->assertCount( 1, $result['items'] );
		$this->assertFalse( $result['has_next_page'] );
		$this->assertEquals( 'cursor3', $result['cursor'] );
	}

	/**
	 * Test product batch fetching with GraphQL error.
	 */
	public function test_fetch_batch_graphql_error(): void {
		$error_response = new WP_Error( 'graphql_error', 'GraphQL query failed: Syntax error' );

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'graphql_request' )
			->willReturn( $error_response );

		$result = $this->fetcher->fetch_batch( array( 'limit' => 5 ) );

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result['items'] );
		$this->assertFalse( $result['has_next_page'] );
		$this->assertNull( $result['cursor'] );
	}

	/**
	 * Test product batch fetching with invalid response structure.
	 */
	public function test_fetch_batch_invalid_response_structure(): void {
		// Mock response missing expected data structure.
		$invalid_response = (object) array(
			'invalidField' => 'unexpected',
		);

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'graphql_request' )
			->willReturn( $invalid_response );

		$result = $this->fetcher->fetch_batch( array( 'limit' => 5 ) );

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result['items'] );
		$this->assertFalse( $result['has_next_page'] );
		$this->assertNull( $result['cursor'] );
	}

	/**
	 * Test product batch fetching with empty results.
	 */
	public function test_fetch_batch_empty_results(): void {
		$mock_response_data = (object) array(
			'products' => (object) array(
				'edges'    => array(),
				'pageInfo' => (object) array(
					'hasNextPage' => false,
					'endCursor'   => null,
				),
			),
		);

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'graphql_request' )
			->willReturn( $mock_response_data );

		$result = $this->fetcher->fetch_batch( array( 'limit' => 5 ) );

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result['items'] );
		$this->assertFalse( $result['has_next_page'] );
		$this->assertNull( $result['cursor'] );
	}

	/**
	 * Test GraphQL variables building helper method.
	 */
	public function test_build_graphql_variables(): void {
		$reflection = new \ReflectionClass( $this->fetcher );
		$method     = $reflection->getMethod( 'build_graphql_variables' );
		$method->setAccessible( true );

		// Test with all parameters.
		$variables = array(
			'first'         => 10,
			'after'         => 'cursor123',
			'variantsFirst' => 100,
		);
		$this->assertEquals(
			$variables,
			$method->invoke(
				$this->fetcher,
				array(
					'limit'        => 10,
					'after_cursor' => 'cursor123',
				)
			)
		);

		// Test with null cursor (should filter out null values).
		$variables = array(
			'first'         => 5,
			'variantsFirst' => 100,
		);
		$this->assertEquals(
			$variables,
			$method->invoke(
				$this->fetcher,
				array(
					'limit'        => 5,
					'after_cursor' => null,
				)
			)
		);

		// Test with empty cursor (empty strings are filtered out).
		$variables = array(
			'first'         => 15,
			'variantsFirst' => 100,
		);
		$this->assertEquals(
			$variables,
			$method->invoke(
				$this->fetcher,
				array(
					'limit'        => 15,
					'after_cursor' => '',
				)
			)
		);
	}

	/**
	 * Test product batch fetching with large limit.
	 */
	public function test_fetch_batch_large_limit(): void {
		$mock_response_data = (object) array(
			'products' => (object) array(
				'edges'    => array_fill(
					0,
					50,
					(object) array(
						'cursor' => 'cursor_n',
						'node'   => (object) array(
							'id'    => 'gid://shopify/Product/n',
							'title' => 'Product N',
						),
					)
				),
				'pageInfo' => (object) array(
					'hasNextPage' => true,
					'endCursor'   => 'cursor_50',
				),
			),
		);

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'graphql_request' )
			->with(
				$this->stringContains( 'query GetShopifyProducts' ),
				array(
					'first'         => 50,
					'variantsFirst' => 100,
				)
			)
			->willReturn( $mock_response_data );

		$result = $this->fetcher->fetch_batch( array( 'limit' => 50 ) );

		$this->assertCount( 50, $result['items'] );
		$this->assertTrue( $result['has_next_page'] );
	}

	/**
	 * Test product batch fetching validates minimum limit.
	 */
	public function test_fetch_batch_minimum_limit(): void {
		$mock_response_data = (object) array(
			'products' => (object) array(
				'edges'    => array(
					(object) array(
						'cursor' => 'cursor1',
						'node'   => (object) array(
							'id'    => 'gid://shopify/Product/1',
							'title' => 'Single Product',
						),
					),
				),
				'pageInfo' => (object) array(
					'hasNextPage' => false,
					'endCursor'   => 'cursor1',
				),
			),
		);

		$this->mock_shopify_client->expects( $this->once() )
			->method( 'graphql_request' )
			->with(
				$this->stringContains( 'query GetShopifyProducts' ),
				array(
					'first'         => 1,
					'variantsFirst' => 100,
				)
			)
			->willReturn( $mock_response_data );

		$result = $this->fetcher->fetch_batch( array( 'limit' => 1 ) );

		$this->assertCount( 1, $result['items'] );
		$this->assertFalse( $result['has_next_page'] );
	}
}
