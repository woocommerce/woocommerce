<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Feed;

use WC_Helper_Product;
use WC_Product;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Utils\MemoryManager;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductLoader;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductMapperInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedValidatorInterface;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\WalkerProgress;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductWalker;

/**
 * ProductWalkerTest class.
 */
class ProductWalkerTest extends \WC_Unit_Test_Case {
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
		remove_all_filters( 'woocommerce_product_feed_args' );
		$this->test_container->reset_all_replacements();
	}

	/**
	 * Data provider for walker tests.
	 *
	 * @return array Test scenarios.
	 */
	public function provider_walker(): array {
		return array(
			'No Results'                        => array(
				'number_of_products' => 0,
				'batch_size'         => 10,
				'add_args_filter'    => true,
			),
			'Single batch'                      => array(
				'number_of_products' => 10,
				'batch_size'         => 100,
				'add_args_filter'    => false,
			),
			'Multiple batches, half last batch' => array(
				'number_of_products' => 5 * 12 - 6,
				'batch_size'         => 12,
				'add_args_filter'    => true,
			),
			'Multiple batches, full last batch' => array(
				'number_of_products' => 5 * 13,
				'batch_size'         => 13,
				'add_args_filter'    => false,
			),
			'High number of batches, proper memory management' => array(
				'number_of_products' => 15 * 2,
				'batch_size'         => 2,
				'add_args_filter'    => false,
			),
		);
	}

	/**
	 * Test the product walker with varying input and results.
	 *
	 * @param int  $number_of_products The number of products to generate.
	 * @param int  $batch_size         The batch size to use.
	 * @param bool $add_args_filter    Whether the args filter is present.
	 *
	 * @dataProvider provider_walker
	 */
	public function test_walker( int $number_of_products, int $batch_size, bool $add_args_filter ) {
		/**
		 * Prepare all mocked data.
		 */

		// There should be at least one iteration, even with zero products.
		$expected_iterations = max( 1, (int) ceil( $number_of_products / $batch_size ) );

		// Generate products, group them into resulting batches.
		$loader_results     = array();
		$generated_products = 0;
		for ( $i = 0; $i < $expected_iterations; $i++ ) {
			$page = array();
			for ( $j = 1; $j <= $batch_size && $generated_products++ < $number_of_products; $j++ ) {
				$page[] = WC_Helper_Product::create_simple_product();
			}

			$loader_results[] = (object) array(
				'products'      => $page,
				'total'         => $number_of_products,
				'max_num_pages' => $expected_iterations,
			);
		}

		// Additional parameters for the query.
		$parent_exclude        = -156;
		$additional_query_args = array(
			'parent_exclude' => $parent_exclude,
			'category'       => array( 'shirts' ),
		);

		// The 11th product will always be rejected due to a validation error.
		$validation_compensation = ( $number_of_products > 10 ? 1 : 0 );

		/**
		 * Set up all dependencies, including mocks.
		 */
		$mock_loader = $this->createMock( ProductLoader::class );
		$this->test_container->replace( ProductLoader::class, $mock_loader );

		$mock_memory_manager = $this->createMock( MemoryManager::class );
		$this->test_container->replace( MemoryManager::class, $mock_memory_manager );

		$mock_feed = $this->createMock( FeedInterface::class );

		// Setup everything that comes from the integration, and the integration itself.
		$mock_mapper      = $this->createMock( ProductMapperInterface::class );
		$mock_validator   = $this->createMock( FeedValidatorInterface::class );
		$mock_integration = $this->createMock( IntegrationInterface::class );
		$mock_integration->expects( $this->once() )->method( 'get_product_mapper' )->willReturn( $mock_mapper );
		$mock_integration->expects( $this->once() )->method( 'get_feed_validator' )->willReturn( $mock_validator );

		/**
		 * Set up data & expectations.
		 */
		$mock_integration->expects( $this->once() )
			->method( 'get_product_feed_query_args' )
			->willReturn( $additional_query_args );

		// Set up the expectationf or each batch.
		$loaded_page = 0;
		$mock_loader->expects( $this->exactly( $expected_iterations ) )
			->method( 'get_products' )
			->with(
				$this->callback(
					function ( $args ) use ( &$loaded_page, $batch_size, $add_args_filter, $parent_exclude ) {
						// Check pagination.
						$this->assertEquals( ++$loaded_page, $args['page'] );
						$this->assertEquals( $batch_size, $args['limit'] );

						// The argument coming from the factory method should be here.
						$this->assertEquals( $parent_exclude, $args['parent_exclude'] );

						// There would be a category, unless the filter removed it..
						if ( $add_args_filter ) {
							$this->assertArrayNotHasKey( 'category', $args );
						} else {
							$this->assertArrayHasKey( 'category', $args );
							$this->assertEquals( array( 'shirts' ), $args['category'] );
						}
						return true;
					}
				)
			)
			->willReturnCallback(
				function () use ( &$loader_results ) {
					return array_shift( $loader_results );
				}
			);

		// Set up the mapper.
		$mock_mapper->expects( $this->exactly( $number_of_products ) )
			->method( 'map_product' )
			->with( $this->isInstanceOf( WC_Product::class ) )
			->willReturnCallback(
				function ( WC_Product $product ) {
					return array(
						'id' => $product->get_id(),
					);
				}
			);

		// Set up the validator.
		$validated_products = 0;
		$mock_validator->expects( $this->exactly( $number_of_products ) )
			->method( 'validate_entry' )
			->with( $this->isType( 'array' ), $this->isInstanceOf( WC_Product::class ) )
			->willReturnCallback(
				function ( array $mapped_data, WC_Product $product ) use ( &$validated_products ) {
					$this->assertEquals( $product->get_id(), $mapped_data['id'] );

					// Pick a "random" product to invalidate.
					$validated_products++;
					if ( 11 === $validated_products ) {
						return array( 'error' => 'Some validation error' );
					}
					return array();
				}
			);

		// Make sure that the field is initiated, added to, and ended.
		$mock_feed->expects( $this->once() )->method( 'start' );
		$mock_feed->expects( $this->once() )->method( 'end' );
		$mock_feed->expects( $this->exactly( $number_of_products - $validation_compensation ) )
			->method( 'add_entry' )
			->with( $this->isType( 'array' ) );

		// Make sure that progress is indicated correctly.
		$processed_iterations = 0;
		$walker_callback      = function ( WalkerProgress $progress ) use (
			$number_of_products,
			$expected_iterations,
			&$processed_iterations,
			$batch_size
		) {
			$this->assertEquals( $number_of_products, $progress->total_count );
			$this->assertEquals( $expected_iterations, $progress->total_batch_count );
			$this->assertEquals( ++$processed_iterations, $progress->processed_batches );
			$this->assertEquals( min( $processed_iterations * $batch_size, $number_of_products ), $progress->processed_items );
		};

		if ( $add_args_filter ) {
			// Add a filter that unsets the category query arg.
			add_filter(
				'woocommerce_product_feed_args',
				function ( $args, $integration ) use ( $mock_integration ) {
					$this->assertSame( $mock_integration, $integration );
					unset( $args['category'] );
					return $args;
				},
				10,
				2
			);
		}

		// Memory management: Always start with 90%. Eatch batch takes up 20%.
		$available_memory = 90;
		$mock_memory_manager->expects( $this->exactly( $expected_iterations + 1 ) )
			->method( 'get_available_memory' )
			->willReturnCallback(
				function () use ( &$available_memory ) {
					$available_memory -= 20;
					return $available_memory;
				}
			);
		// Flushing cashes frees up memory up to 46% (just a bit over half).
		// So once memory gets low, it remains just above the threshold (half of 90% or 45%).
		$flushes = max( 0, $expected_iterations - 1 );
		$mock_memory_manager->expects( $this->exactly( $flushes ) )
			->method( 'flush_caches' )
			->willReturnCallback(
				function () use ( &$available_memory ) {
					$available_memory = 46;
				}
			);

		/**
		 * Finally, get the walker and go!
		 */
		$walker = ProductWalker::from_integration(
			$mock_integration,
			$mock_feed
		);

		$walker->set_batch_size( $batch_size );
		$walker->walk( $walker_callback );
	}
}
