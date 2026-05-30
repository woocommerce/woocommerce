<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Feed;

use WC_Helper_Product;
use WC_Product;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductLoader;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductMapperInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductQueryService;

/**
 * ProductQueryServiceTest class.
 */
class ProductQueryServiceTest extends \WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		remove_all_filters( 'woocommerce_product_feed_args' );
	}

	/**
	 * Clean up test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'woocommerce_product_feed_args' );
		wc_get_container()->reset_all_replacements();
	}

	/**
	 * Data provider for query_products pagination tests.
	 *
	 * @return array Test scenarios.
	 */
	public function provider_query_products(): array {
		return array(
			'No results'               => array(
				'number_of_products' => 0,
				'batch_size'         => 10,
				'page'               => 1,
			),
			'Single page'              => array(
				'number_of_products' => 5,
				'batch_size'         => 10,
				'page'               => 1,
			),
			'Multiple pages'           => array(
				'number_of_products' => 25,
				'batch_size'         => 10,
				'page'               => 2,
			),
			'Page beyond last'         => array(
				'number_of_products' => 5,
				'batch_size'         => 10,
				'page'               => 3,
			),
		);
	}

	/**
	 * Test query_products with varying pagination.
	 *
	 * @param int $number_of_products The number of products to create.
	 * @param int $batch_size         The batch size (limit).
	 * @param int $page               The page number.
	 *
	 * @dataProvider provider_query_products
	 */
	public function test_query_products( int $number_of_products, int $batch_size, int $page ): void {
		// Arrange: Create products.
		$products = array();
		for ( $i = 0; $i < $number_of_products; $i++ ) {
			$products[] = WC_Helper_Product::create_simple_product();
		}

		// Set up mocks.
		$mock_loader    = $this->createMock( ProductLoader::class );
		$mock_mapper    = $this->createMock( ProductMapperInterface::class );
		$mock_validator = $this->createMock( FeedValidatorInterface::class );
		$query_args     = array(
			'status' => array( 'publish' ),
		);

		// Mock the loader to return paginated results.
		$start_index = ( $page - 1 ) * $batch_size;
		$page_slice  = array_slice( $products, $start_index, $batch_size );

		$mock_loader->method( 'get_products' )
			->willReturn(
				(object) array(
					'products'      => $page_slice,
					'total'         => $number_of_products,
					'max_num_pages' => max( 1, (int) ceil( $number_of_products / $batch_size ) ),
				)
			);

		// Mapper returns simple array per product.
		$mock_mapper->method( 'map_product' )
			->willReturnCallback(
				function ( WC_Product $product ) {
					return array(
						'id'   => $product->get_id(),
						'name' => $product->get_name(),
					);
				}
			);

		// Validator passes everything.
		$mock_validator->method( 'validate_entry' )
			->willReturn( array() );

		// Act.
		$service = new ProductQueryService( $mock_mapper, $mock_validator, $mock_loader, $query_args );
		$result  = $service->query_products( array(), $page, $batch_size );

		// Assert.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'products', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'max_num_pages', $result );
		$this->assertSame( $number_of_products, $result['total'] );
		$this->assertCount( count( $page_slice ), $result['products'] );
	}

	/**
	 * Test query_products filters out invalid entries.
	 */
	public function test_query_products_validates_entries(): void {
		// Arrange: Create 3 products.
		$products = array(
			WC_Helper_Product::create_simple_product(),
			WC_Helper_Product::create_simple_product(),
			WC_Helper_Product::create_simple_product(),
		);

		$mock_loader    = $this->createMock( ProductLoader::class );
		$mock_mapper    = $this->createMock( ProductMapperInterface::class );
		$mock_validator = $this->createMock( FeedValidatorInterface::class );

		$mock_loader->method( 'get_products' )
			->willReturn(
				(object) array(
					'products'      => $products,
					'total'         => 3,
					'max_num_pages' => 1,
				)
			);

		$mock_mapper->method( 'map_product' )
			->willReturnCallback(
				function ( WC_Product $product ) {
					return array( 'id' => $product->get_id() );
				}
			);

		// Second product fails validation.
		$call_count = 0;
		$mock_validator->method( 'validate_entry' )
			->willReturnCallback(
				function () use ( &$call_count ) {
					$call_count++;
					return ( 2 === $call_count ) ? array( 'missing_field' ) : array();
				}
			);

		// Act.
		$service = new ProductQueryService( $mock_mapper, $mock_validator, $mock_loader, array() );
		$result  = $service->query_products( array(), 1, 10 );

		// Assert: 3 products queried, 1 filtered -> 2 returned.
		$this->assertCount( 2, $result['products'] );
		$this->assertSame( 3, $result['total'] );
	}

	/**
	 * Test query_products merges additional args.
	 */
	public function test_query_products_merges_args(): void {
		$mock_loader    = $this->createMock( ProductLoader::class );
		$mock_mapper    = $this->createMock( ProductMapperInterface::class );
		$mock_validator = $this->createMock( FeedValidatorInterface::class );

		$base_args = array( 'type' => array( 'simple' ) );

		$mock_loader->expects( $this->once() )
			->method( 'get_products' )
			->with(
				$this->callback(
					function ( $args ) {
						$this->assertArrayHasKey( 'type', $args );
						$this->assertSame( array( 'simple' ), $args['type'] );
						$this->assertArrayHasKey( 'category', $args );
						$this->assertSame( array( 'shirts' ), $args['category'] );
						$this->assertSame( 2, $args['page'] );
						$this->assertSame( 25, $args['limit'] );
						$this->assertTrue( $args['paginate'] );
						return true;
					}
				)
			)
			->willReturn(
				(object) array(
					'products'      => array(),
					'total'         => 0,
					'max_num_pages' => 0,
				)
			);

		$mock_validator->method( 'validate_entry' )->willReturn( array() );

		$service = new ProductQueryService( $mock_mapper, $mock_validator, $mock_loader, $base_args );
		$service->query_products( array( 'category' => array( 'shirts' ) ), 2, 25 );
	}

	/**
	 * Test get_product returns mapped data for valid product.
	 */
	public function test_get_product_returns_mapped_data(): void {
		$product = WC_Helper_Product::create_simple_product();

		$mock_loader    = $this->createMock( ProductLoader::class );
		$mock_mapper    = $this->createMock( ProductMapperInterface::class );
		$mock_validator = $this->createMock( FeedValidatorInterface::class );
		$query_args     = array( 'status' => array( 'publish' ) );

		$mock_loader->method( 'get_products' )
			->willReturn(
				(object) array(
					'products' => array( $product ),
					'total'    => 1,
				)
			);

		$mock_mapper->method( 'map_product' )
			->willReturn( array( 'id' => $product->get_id(), 'name' => 'Test Product' ) );

		$mock_validator->method( 'validate_entry' )->willReturn( array() );

		$service = new ProductQueryService( $mock_mapper, $mock_validator, $mock_loader, $query_args );
		$result  = $service->get_product( $product->get_id() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( $product->get_id(), $result['id'] );
	}

	/**
	 * Test get_product passes query_args to loader.
	 */
	public function test_get_product_passes_query_args_to_loader(): void {
		$mock_loader    = $this->createMock( ProductLoader::class );
		$mock_mapper    = $this->createMock( ProductMapperInterface::class );
		$mock_validator = $this->createMock( FeedValidatorInterface::class );
		$query_args     = array(
			'status' => array( 'publish' ),
			'type'   => array( 'simple' ),
		);

		$mock_loader->expects( $this->once() )
			->method( 'get_products' )
			->with(
				$this->callback(
					function ( $args ) {
						$this->assertSame( array( 'publish' ), $args['status'] );
						$this->assertSame( array( 'simple' ), $args['type'] );
						$this->assertSame( array( 42 ), $args['include'] );
						$this->assertArrayHasKey( 'paginate', $args );
						$this->assertTrue( $args['paginate'] );
						$this->assertSame( 1, $args['limit'] );
						$this->assertSame( 1, $args['page'] );
						return true;
					}
				)
			)
			->willReturn(
				(object) array( 'products' => array() )
			);

		$service = new ProductQueryService( $mock_mapper, $mock_validator, $mock_loader, $query_args );
		$result  = $service->get_product( 42 );

		$this->assertNull( $result );
	}

	/**
	 * Test get_product returns null when validation fails.
	 */
	public function test_get_product_returns_null_when_validation_fails(): void {
		$product = WC_Helper_Product::create_simple_product();

		$mock_loader    = $this->createMock( ProductLoader::class );
		$mock_mapper    = $this->createMock( ProductMapperInterface::class );
		$mock_validator = $this->createMock( FeedValidatorInterface::class );

		$mock_loader->method( 'get_products' )
			->willReturn(
				(object) array( 'products' => array( $product ) )
			);

		$mock_mapper->method( 'map_product' )
			->willReturn( array( 'id' => $product->get_id() ) );

		$mock_validator->method( 'validate_entry' )
			->willReturn( array( 'invalid_sku' ) );

		$service = new ProductQueryService( $mock_mapper, $mock_validator, $mock_loader, array() );
		$result  = $service->get_product( $product->get_id() );

		$this->assertNull( $result );
	}

	/**
	 * Test get_total_count returns correct count.
	 */
	public function test_get_total_count(): void {
		$mock_loader    = $this->createMock( ProductLoader::class );
		$mock_mapper    = $this->createMock( ProductMapperInterface::class );
		$mock_validator = $this->createMock( FeedValidatorInterface::class );
		$base_args      = array( 'type' => array( 'simple' ) );

		$mock_loader->expects( $this->once() )
			->method( 'get_products' )
			->with(
				$this->callback(
					function ( $args ) {
						$this->assertSame( 1, $args['limit'] );
						$this->assertTrue( $args['paginate'] );
						$this->assertArrayHasKey( 'type', $args );
						return true;
					}
				)
			)
			->willReturn(
				(object) array(
					'total'         => 42,
					'max_num_pages' => 42,
					'products'      => array(),
				)
			);

		$service = new ProductQueryService( $mock_mapper, $mock_validator, $mock_loader, $base_args );
		$result  = $service->get_total_count( array() );

		$this->assertSame( 42, $result );
	}

	/**
	 * Test get_total_count merges additional args.
	 */
	public function test_get_total_count_merges_args(): void {
		$mock_loader    = $this->createMock( ProductLoader::class );
		$mock_mapper    = $this->createMock( ProductMapperInterface::class );
		$mock_validator = $this->createMock( FeedValidatorInterface::class );
		$base_args      = array( 'type' => array( 'simple' ) );

		$mock_loader->expects( $this->once() )
			->method( 'get_products' )
			->with(
				$this->callback(
					function ( $args ) use ( $base_args ) {
						$this->assertSame( $base_args['type'], $args['type'] );
						$this->assertArrayHasKey( 'category', $args );
						$this->assertSame( array( 'shirts' ), $args['category'] );
						return true;
					}
				)
			)
			->willReturn(
				(object) array(
					'total'    => 10,
					'products' => array(),
				)
			);

		$service = new ProductQueryService( $mock_mapper, $mock_validator, $mock_loader, $base_args );
		$result  = $service->get_total_count( array( 'category' => array( 'shirts' ) ) );

		$this->assertSame( 10, $result );
	}
}
