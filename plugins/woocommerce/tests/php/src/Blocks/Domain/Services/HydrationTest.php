<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Tests for the Hydration class.
 */
class HydrationTest extends \WP_UnitTestCase {
	/**
	 * Instance of Hydration for testing.
	 *
	 * @var Hydration
	 */
	private $hydration;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Initialize Store API.
		StoreApi::container();

		$this->hydration = new Hydration( $this->createMock( AssetDataRegistry::class ) );
	}

	/**
	 * Test that get_rest_api_response_data handles cart endpoint and returns valid cart structure.
	 */
	public function test_get_rest_api_response_data_cart_without_params() {
		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/cart' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertArrayHasKey( 'headers', $result );

		// Verify cart response has expected structure.
		$cart = $result['body'];
		$this->assertArrayHasKey( 'items', $cart, 'Cart should have items array' );
		$this->assertArrayHasKey( 'totals', $cart, 'Cart should have totals' );
		$this->assertArrayHasKey( 'coupons', $cart, 'Cart should have coupons array' );
		$this->assertArrayHasKey( 'shipping_rates', $cart, 'Cart should have shipping_rates' );
		$this->assertIsArray( $cart['items'] );
		$this->assertIsArray( $cart['coupons'] );
		$this->assertObjectHasProperty( 'total_items', $cart['totals'] );
		$this->assertObjectHasProperty( 'total_price', $cart['totals'] );
		$this->assertObjectHasProperty( 'currency_code', $cart['totals'] );
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
	 * Test that get_rest_api_response_data handles products with stock_status query parameter.
	 */
	public function test_get_rest_api_response_data_products_with_query_params() {
		$out_of_stock_product = \WC_Helper_Product::create_simple_product();
		$out_of_stock_product->set_name( 'Out of Stock Product' );
		$out_of_stock_product->set_stock_status( 'outofstock' );
		$out_of_stock_product->save();

		$in_stock_product = \WC_Helper_Product::create_simple_product();
		$in_stock_product->set_name( 'In Stock Product' );
		$in_stock_product->set_stock_status( 'instock' );
		$in_stock_product->save();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products?stock_status[]=outofstock' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );
		$this->assertNotEmpty( $result['body'], 'Should return at least one out of stock product' );

		// Verify all returned products have outofstock status.
		$found_out_of_stock = false;
		foreach ( $result['body'] as $product_data ) {
			$this->assertArrayHasKey( 'is_in_stock', $product_data );
			$this->assertFalse( $product_data['is_in_stock'], 'Returned product should be out of stock' );

			if ( $product_data['id'] === $out_of_stock_product->get_id() ) {
				$found_out_of_stock = true;
			}

			// Ensure in stock product is NOT in results.
			$this->assertNotEquals(
				$in_stock_product->get_id(),
				$product_data['id'],
				'In stock product should not appear in outofstock filter results'
			);
		}

		$this->assertTrue( $found_out_of_stock, 'Out of stock product should be in results' );

		$out_of_stock_product->delete( true );
		$in_stock_product->delete( true );
	}

	/**
	 * Test that get_rest_api_response_data handles multiple query parameters (parent + type).
	 */
	public function test_get_rest_api_response_data_with_multiple_query_params() {
		$variable_product = \WC_Helper_Product::create_variation_product();
		$variable_product->save();

		$result = $this->hydration->get_rest_api_response_data(
			'/wc/store/v1/products?parent[]=' . $variable_product->get_id() . '&type=variation'
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		// Each returned product should be a variation of the parent.
		foreach ( $result['body'] as $variation_data ) {
			$this->assertArrayHasKey( 'id', $variation_data );
			$this->assertArrayHasKey( 'parent', $variation_data );
			$this->assertEquals( $variable_product->get_id(), $variation_data['parent'] );
			$this->assertEquals( 'variation', $variation_data['type'] );
		}

		$variable_product->delete( true );
	}

	/**
	 * Test that get_rest_api_response_data handles product with ID in URL.
	 */
	public function test_get_rest_api_response_data_product_with_id() {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product for Hydration' );
		$product->save();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products/' . $product->get_id() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		if ( isset( $result['body']['id'] ) ) {
			$this->assertEquals( $product->get_id(), $result['body']['id'] );
		}
		if ( isset( $result['body']['name'] ) ) {
			$this->assertEquals( 'Test Product for Hydration', $result['body']['name'] );
		}

		$product->delete( true );
	}

	/**
	 * Test that encoded query parameters are properly handled.
	 */
	public function test_get_rest_api_response_data_with_encoded_query_params() {
		$matching_product = \WC_Helper_Product::create_simple_product();
		$matching_product->set_name( 'Unique Hydration Test Product' );
		$matching_product->save();

		// Create a product that should not match in search results.
		$non_matching_product = \WC_Helper_Product::create_simple_product();
		$non_matching_product->set_name( 'Unrelated Item' );
		$non_matching_product->save();

		$result = $this->hydration->get_rest_api_response_data( '/wc/store/v1/products?search=Unique%20Hydration%20Test' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'body', $result );
		$this->assertIsArray( $result['body'] );

		// Verify we got results and the matching product is included.
		$this->assertNotEmpty( $result['body'], 'Search should return at least one product' );

		$found_matching_product = false;
		foreach ( $result['body'] as $product_data ) {
			if ( $product_data['id'] === $matching_product->get_id() ) {
				$found_matching_product = true;
				$this->assertEquals( 'Unique Hydration Test Product', $product_data['name'] );
			}
			$this->assertNotEquals( $non_matching_product->get_id(), $product_data['id'], 'Non-matching product should not appear in search results' );
		}

		$this->assertTrue( $found_matching_product, 'Matching product should be in search results' );

		$matching_product->delete( true );
		$non_matching_product->delete( true );
	}
}
