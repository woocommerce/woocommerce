<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Integrations\POSCatalog;

use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\ProductMapper;
use WC_Helper_Product;

/**
 * Product mapper test class.
 */
class ProductMapperTest extends \WC_Unit_Test_Case {
	/**
	 * System under test.
	 *
	 * @var ProductMapper
	 */
	private ProductMapper $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new ProductMapper();
		$this->sut->init(); // Could be done through DI, but there are no parameter dependencies.
	}

	/**
	 * Clean up test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->sut->set_fields( null );
	}

	/**
	 * Test mapping a simple product.
	 */
	public function test_map_product_simple_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_description( 'Test Description' );
		$product->set_regular_price( '99.99' );

		$result = $this->sut->map_product( $product );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'id', $result['data'] );
		$this->assertArrayHasKey( 'name', $result['data'] );
		$this->assertArrayHasKey( 'description', $result['data'] );
		$this->assertArrayHasKey( 'price', $result['data'] );
		$this->assertArrayHasKey( 'downloadable', $result['data'] );
		$this->assertArrayHasKey( 'parent_id', $result['data'] );
		$this->assertArrayHasKey( 'images', $result['data'] );
	}

	/**
	 * Test mapping a product with specific fields.
	 */
	public function test_map_product_with_fields(): void {
		$this->sut->set_fields( 'id,name,description' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->set_description( 'Test Description' );
		$product->set_regular_price( '99.99' );

		$result = $this->sut->map_product( $product );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'name', $result['data'] );
		$this->assertArrayHasKey( 'description', $result['data'] );
		$this->assertArrayNotHasKey( 'price', $result['data'] );
		$this->assertArrayNotHasKey( 'downloadable', $result['data'] );
		$this->assertArrayNotHasKey( 'parent_id', $result['data'] );
	}
}
