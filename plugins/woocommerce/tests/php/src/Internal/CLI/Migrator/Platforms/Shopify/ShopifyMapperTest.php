<?php
/**
 * Shopify Mapper Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyMapper;

/**
 * Test cases for ShopifyMapper implementation.
 */
class ShopifyMapperTest extends \WC_Unit_Test_Case {

	/**
	 * The ShopifyMapper instance under test.
	 *
	 * @var ShopifyMapper
	 */
	private ShopifyMapper $mapper;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->mapper = new ShopifyMapper();
	}

	/**
	 * Test that ShopifyMapper implements the PlatformMapperInterface.
	 */
	public function test_implements_platform_mapper_interface() {
		$this->assertInstanceOf( PlatformMapperInterface::class, $this->mapper );
	}

	/**
	 * Test map_product_data method returns expected type and stub value.
	 */
	public function test_map_product_data_returns_array_stub() {
		$platform_data = (object) array( 'id' => 'test_id' );
		$result        = $this->mapper->map_product_data( $platform_data );

		$this->assertIsArray( $result );
		$this->assertEquals( array(), $result );
	}

	/**
	 * Test map_product_data with various platform data objects.
	 */
	public function test_map_product_data_with_different_inputs() {
		$test_cases = array(
			(object) array(),
			(object) array( 'id' => 'shopify_123' ),
			(object) array(
				'id'     => 'shopify_456',
				'title'  => 'Test Product',
				'handle' => 'test-product',
			),
			(object) array(
				'id'       => 'shopify_789',
				'title'    => 'Complex Product',
				'variants' => array(
					(object) array( 'price' => '10.00' ),
				),
			),
		);

		foreach ( $test_cases as $platform_data ) {
			$result = $this->mapper->map_product_data( $platform_data );

			$this->assertIsArray( $result );
			$this->assertEquals( array(), $result ); // Stub implementation always returns empty array.
		}
	}


	/**
	 * Test that the mapper accepts various object types.
	 */
	public function test_accepts_different_object_types() {
		// Test with stdClass.
		$std_object     = new \stdClass();
		$std_object->id = 'test';
		$result         = $this->mapper->map_product_data( $std_object );
		$this->assertIsArray( $result );

		// Test with array cast to object.
		$array_object = (object) array( 'title' => 'Test Product' );
		$result       = $this->mapper->map_product_data( $array_object );
		$this->assertIsArray( $result );

		// Test with custom object.
		$custom_object = new class() {
			/**
			 * Test ID property.
			 *
			 * @var string
			 */
			public $id = 'custom_123';

			/**
			 * Test name property.
			 *
			 * @var string
			 */
			public $name = 'Custom Product';
		};
		$result        = $this->mapper->map_product_data( $custom_object );
		$this->assertIsArray( $result );
	}

	/**
	 * Test that the mapper is ready for future enhancement.
	 */
	public function test_stub_implementation_ready_for_enhancement() {
		// This test documents that this is a stub implementation.
		// Future PRs should replace this method with actual Shopify to WooCommerce data mapping.

		$shopify_product = (object) array(
			'id'           => 'gid://shopify/Product/123456789',
			'title'        => 'Amazing T-Shirt',
			'handle'       => 'amazing-t-shirt',
			'description'  => 'A really amazing t-shirt',
			'product_type' => 'Apparel',
			'vendor'       => 'Cool Brand',
			'variants'     => array(
				(object) array(
					'id'                => 'gid://shopify/ProductVariant/987654321',
					'price'             => '25.00',
					'sku'               => 'AMAZ-TSHIRT-M',
					'inventoryQuantity' => 100,
				),
			),
			'images'       => array(
				(object) array(
					'url'     => 'https://example.com/image.jpg',
					'altText' => 'Amazing T-Shirt',
				),
			),
		);

		$result = $this->mapper->map_product_data( $shopify_product );

		// Current stub behavior - will change when real implementation is added.
		$this->assertEquals( array(), $result, 'Stub returns empty array' );
	}
}
