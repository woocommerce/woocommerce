<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Mapping;

use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductLoader;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductMapperInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductWalker;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\ProductMapper;
use Automattic\WooCommerce\Internal\ProductFeed\Mapping\ProductShapeMapperInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Utils\MemoryManager;
use WC_Helper_Product;
use WC_Product;

/**
 * Tests for the ProductShapeMapperInterface contract.
 */
class ProductShapeMapperInterfaceTest extends \WC_Unit_Test_Case {
	/**
	 * Test container.
	 *
	 * @var TestContainer
	 */
	private $test_container;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->test_container = wc_get_container();
	}

	/**
	 * Clean up test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->test_container->reset_all_replacements();
	}

	/**
	 * @testdox The deprecated push-feed ProductMapperInterface should remain a subtype of ProductShapeMapperInterface, so existing feed mappers keep working during the deprecation window.
	 */
	public function test_feed_product_mapper_interface_is_a_product_shape_mapper(): void {
		$this->assertTrue(
			is_subclass_of( ProductMapperInterface::class, ProductShapeMapperInterface::class ),
			'The deprecated ProductMapperInterface should extend ProductShapeMapperInterface'
		);
	}

	/**
	 * @testdox The built-in POS catalog mapper should satisfy ProductShapeMapperInterface.
	 */
	public function test_pos_catalog_mapper_is_a_product_shape_mapper(): void {
		$mapper = wc_get_container()->get( ProductMapper::class );

		$this->assertInstanceOf(
			ProductShapeMapperInterface::class,
			$mapper,
			'The POS catalog mapper should be consumable through the delivery-agnostic interface'
		);
	}

	/**
	 * @testdox The ProductShapeMapperInterface contract (param + return type) should be stable; changing it is a conscious BC decision.
	 */
	public function test_product_shape_mapper_interface_signature_is_stable(): void {
		$method = new \ReflectionMethod( ProductShapeMapperInterface::class, 'map_product' );
		$params = $method->getParameters();

		$this->assertCount( 1, $params, 'map_product() should take exactly one parameter.' );
		$this->assertSame( WC_Product::class, (string) $params[0]->getType(), 'map_product() should accept a WC_Product.' );
		$this->assertSame( 'array', (string) $method->getReturnType(), 'map_product() should return an array.' );
	}

	/**
	 * Demonstrates the decoupling-from-feed-machinery path: an implementation can be
	 * instantiated and invoked with no container, validator, or feed present. This is a
	 * structural/usability demonstration, not a signature guard — see
	 * test_product_shape_mapper_interface_signature_is_stable() for the contract tripwire.
	 *
	 * @testdox A pull/query consumer should be able to use a shape mapper standalone, without any feed machinery.
	 */
	public function test_shape_mapper_is_usable_without_feed_machinery(): void {
		$mapper = new class() implements ProductShapeMapperInterface {
			/**
			 * Map a product to an array shape.
			 *
			 * @param WC_Product $product The product to map.
			 * @return array The mapped product data.
			 */
			public function map_product( WC_Product $product ): array {
				return array(
					'id'    => (string) $product->get_id(),
					'title' => $product->get_name(),
				);
			}
		};

		$product = WC_Helper_Product::create_simple_product();

		$mapped = $mapper->map_product( $product );

		$this->assertSame( (string) $product->get_id(), $mapped['id'] );
		$this->assertSame( $product->get_name(), $mapped['title'] );
	}

	/**
	 * @testdox The ProductWalker should accept a mapper that only implements ProductShapeMapperInterface, not the push-feed flavored interface.
	 */
	public function test_product_walker_accepts_plain_shape_mapper(): void {
		$product = WC_Helper_Product::create_simple_product();

		$mock_loader = $this->createMock( ProductLoader::class );
		$mock_loader->method( 'get_products' )->willReturn(
			(object) array(
				'products'      => array( $product ),
				'total'         => 1,
				'max_num_pages' => 1,
			)
		);
		$this->test_container->replace( ProductLoader::class, $mock_loader );

		$mock_memory_manager = $this->createMock( MemoryManager::class );
		$mock_memory_manager->method( 'get_available_memory' )->willReturn( 100 );
		$this->test_container->replace( MemoryManager::class, $mock_memory_manager );

		$mock_mapper = $this->createMock( ProductShapeMapperInterface::class );
		$mock_mapper->expects( $this->once() )
			->method( 'map_product' )
			->with( $this->isInstanceOf( WC_Product::class ) )
			->willReturn( array( 'id' => $product->get_id() ) );

		$mock_validator = $this->createMock( FeedValidatorInterface::class );
		$mock_validator->method( 'validate_entry' )->willReturn( array() );

		$mock_integration = $this->createMock( IntegrationInterface::class );
		$mock_integration->method( 'get_product_mapper' )->willReturn( $mock_mapper );
		$mock_integration->method( 'get_feed_validator' )->willReturn( $mock_validator );
		$mock_integration->method( 'get_product_feed_query_args' )->willReturn( array() );

		$mock_feed = $this->createMock( FeedInterface::class );
		$mock_feed->expects( $this->once() )
			->method( 'add_entry' )
			->with( array( 'id' => $product->get_id() ) );

		$processed = ProductWalker::from_integration( $mock_integration, $mock_feed )->walk();

		$this->assertSame( 1, $processed, 'The walker should process the single product through the plain shape mapper' );
	}
}
