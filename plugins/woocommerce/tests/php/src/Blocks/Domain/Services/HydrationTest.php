<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Tests for the Hydration class
 *
 * @since $VID:$
 */
class HydrationTest extends \WP_UnitTestCase {
	/**
	 * Instance of Hydration for testing.
	 *
	 * @var Hydration
	 */
	private $hydration;

	/**
	 * Mock AssetDataRegistry.
	 *
	 * @var AssetDataRegistry
	 */
	private $mock_asset_registry;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Initialize Store API.
		StoreApi::container();

		$this->mock_asset_registry = $this->createMock( AssetDataRegistry::class );
		$this->hydration           = new Hydration( $this->mock_asset_registry );
	}

	/**
	 * Test that get_rest_api_response_data handles cart endpoint without parameters.
	 */
	public function test_get_rest_api_response_data_cart_without_params() {
		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/cart' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertArrayHasKey( 'headers', $result );
	}

	/**
	 * Test that get_rest_api_response_data handles cart endpoint with query parameters.
	 */
	public function test_get_rest_api_response_data_cart_with_query_params() {
		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/cart?context=edit' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertArrayHasKey( 'headers', $result );
	}

	/**
	 * Test that get_rest_api_response_data returns empty array for non-store routes.
	 */
	public function test_get_rest_api_response_data_rejects_non_store_routes() {
		$result = $this->hydration->get_rest_api_response_data( '/wp/v2/posts' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test that get_rest_api_response_data handles invalid routes.
	 */
	public function test_get_rest_api_response_data_handles_invalid_routes() {
		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/nonexistent' );

		$this->assertIsArray( $result );
		// Should fall back to rest_preload_api_request which may return empty or have body key.
		$this->assertTrue(
			empty( $result ) || isset( $result['body'] ),
			'Invalid route should return empty array or array with body key'
		);
	}

	/**
	 * Test that get_rest_api_response_data handles products endpoint.
	 */
	public function test_get_rest_api_response_data_products() {
		// Create a test product.
		$product = \WC_Helper_Product::create_simple_product();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * Test that get_rest_api_response_data handles products with query parameters.
	 */
	public function test_get_rest_api_response_data_products_with_query_params() {
		// Create test products.
		$product1 = \WC_Helper_Product::create_simple_product();
		$product2 = \WC_Helper_Product::create_simple_product();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products?per_page=1' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		// Clean up.
		$product1->delete( true );
		$product2->delete( true );
	}

	/**
	 * Test that get_rest_api_response_data handles product with ID in URL.
	 */
	public function test_get_rest_api_response_data_product_with_id() {
		// Create a test product.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product for Hydration' );
		$product->save();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products/' . $product->get_id() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		// Verify that we got the correct product.
		if ( isset( $result['body']['id'] ) ) {
			$this->assertEquals( $product->get_id(), $result['body']['id'] );
		}
		if ( isset( $result['body']['name'] ) ) {
			$this->assertEquals( 'Test Product for Hydration', $result['body']['name'] );
		}

		// Clean up.
		$product->delete( true );
	}

	/**
	 * Test that get_rest_api_response_data handles product with ID and query params.
	 */
	public function test_get_rest_api_response_data_product_with_id_and_query_params() {
		// Create a test product.
		$product = \WC_Helper_Product::create_simple_product();
		$product->save();

		$result = $this->hydration->get_rest_api_response_data(
			'/wc/store/v1/products/' . $product->get_id() . '?context=edit'
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * Test that encoded query parameters are properly handled.
	 */
	public function test_get_rest_api_response_data_with_encoded_query_params() {
		// Create test products with specific names.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->save();

		// Use encoded space in search query.
		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products?search=Test%20Product' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );

		// Clean up.
		$product->delete( true );
	}
}
