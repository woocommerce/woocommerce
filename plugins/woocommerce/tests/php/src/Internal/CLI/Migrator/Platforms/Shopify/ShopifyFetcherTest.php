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

		$this->fetcher             = new ShopifyFetcher();
		$this->mock_shopify_client = $this->createMock( ShopifyClient::class );
		$this->fetcher->init( $this->mock_shopify_client );
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
		$this->assertArrayHasKey( 'hasNextPage', $result );
		$this->assertEquals( array(), $result['items'] );
		$this->assertNull( $result['cursor'] );
		$this->assertFalse( $result['hasNextPage'] );
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
}
