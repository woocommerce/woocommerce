<?php

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;
use WC_Helper_Product;
use WP_Query;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Tests for the ProductCollection block collection handlers
 *
 * @group handlers
 */
class HandlerRegistry extends \WP_UnitTestCase {
	/**
	 * This variable holds our Product Query object.
	 *
	 * @var ProductCollectionMock
	 */
	private $block_instance;

	/**
	 * Return starting point for parsed block test data.
	 * Using a method instead of property to avoid sharing data between tests.
	 */
	private function get_base_parsed_block() {
		return array(
			'blockName' => 'woocommerce/product-collection',
			'attrs'     => array(
				'query' => array(
					'perPage'                  => 9,
					'pages'                    => 0,
					'offset'                   => 0,
					'postType'                 => 'product',
					'order'                    => 'desc',
					'orderBy'                  => 'date',
					'search'                   => '',
					'exclude'                  => array(),
					'sticky'                   => '',
					'inherit'                  => true,
					'isProductCollectionBlock' => true,
					'woocommerceAttributes'    => array(),
					'woocommerceStockStatus'   => array(
						ProductStockStatus::IN_STOCK,
						ProductStockStatus::OUT_OF_STOCK,
						ProductStockStatus::ON_BACKORDER,
					),
				),
			),
		);
	}

	/**
	 * Build a simplified request for testing.
	 *
	 * @param array $params The parameters to set on the request.
	 * @return WP_REST_Request
	 */
	private function build_request( $params = array() ) {
		$params = wp_parse_args(
			$params,
			array(
				'featured'               => false,
				'woocommerceOnSale'      => false,
				'woocommerceAttributes'  => array(),
				'woocommerceStockStatus' => array(),
				'timeFrame'              => array(),
				'priceRange'             => array(),
			)
		);

		$params['isProductCollectionBlock'] = true;

		$request = new \WP_REST_Request( 'GET', '/wp/v2/product' );
		foreach ( $params as $param => $value ) {
			$request->set_param( $param, $value );
		}

		return $request;
	}

	/**
	 * Initiate the mock object.
	 */
	protected function setUp(): void {
		$this->block_instance = new ProductCollectionMock();
	}

	/**
	 * Build the merged_query for testing
	 *
	 * @param array $parsed_block Parsed block data.
	 * @param array $query        Query data.
	 */
	private function initialize_merged_query( $parsed_block = array(), $query = array() ) {
		if ( empty( $parsed_block ) ) {
			$parsed_block = $this->get_base_parsed_block();
		}

		$this->block_instance->set_parsed_block( $parsed_block );

		$block          = new \stdClass();
		$block->context = $parsed_block['attrs'];

		return $this->block_instance->build_frontend_query( $query, $block, 1 );
	}

	/**
	 * Test for frontend collection handlers.
	 */
	public function test_frontend_collection_handlers() {
		$build_query   = $this->getMockBuilder( \stdClass::class )
			->setMethods( [ '__invoke' ] )
			->getMock();
		$frontend_args = $this->getMockBuilder( \stdClass::class )
			->setMethods( [ '__invoke' ] )
			->getMock();
		$this->block_instance->register_collection_handlers( 'test-collection', $build_query, $frontend_args );

		$frontend_args->expects( $this->once() )
			->method( '__invoke' )
			->willReturnCallback(
				function ( $collection_args ) {
					$collection_args['test'] = 'test-arg';
					return $collection_args;
				}
			);
		$build_query->expects( $this->once() )
			->method( '__invoke' )
			->willReturnCallback(
				function ( $collection_args ) {
					$this->assertArrayHasKey( 'test', $collection_args );
					$this->assertEquals( 'test-arg', $collection_args['test'] );
					return array(
						'post__in' => array( 111 ),
					);
				}
			);

		$parsed_block                        = $this->get_base_parsed_block();
		$parsed_block['attrs']['collection'] = 'test-collection';

		$merged_query = $this->initialize_merged_query( $parsed_block );

		$this->block_instance->unregister_collection_handlers( 'test-collection' );

		$this->assertContains( 111, $merged_query['post__in'] );
	}

	/**
	 * Test for editor collection handlers.
	 */
	public function test_editor_collection_handlers() {
		$build_query = $this->getMockBuilder( \stdClass::class )
			->setMethods( [ '__invoke' ] )
			->getMock();
		$editor_args = $this->getMockBuilder( \stdClass::class )
			->setMethods( [ '__invoke' ] )
			->getMock();
		$this->block_instance->register_collection_handlers( 'test-collection', $build_query, null, $editor_args );

		$editor_args->expects( $this->once() )
			->method( '__invoke' )
			->willReturnCallback(
				function ( $collection_args ) {
					$collection_args['test'] = 'test-arg';
					return $collection_args;
				}
			);
		$build_query->expects( $this->once() )
			->method( '__invoke' )
			->willReturnCallback(
				function ( $collection_args ) {
					$this->assertArrayHasKey( 'test', $collection_args );
					$this->assertEquals( 'test-arg', $collection_args['test'] );
					return array(
						'post__in' => array( 111 ),
					);
				}
			);

		$args    = array();
		$request = $this->build_request();
		$request->set_param(
			'productCollectionQueryContext',
			array(
				'collection' => 'test-collection',
			)
		);

		$updated_query = $this->block_instance->update_rest_query_in_editor( $args, $request );

		$this->block_instance->unregister_collection_handlers( 'test-collection' );

		$this->assertContains( 111, $updated_query['post__in'] );
	}

	/**
	 * Test for the editor preview collection handler.
	 */
	public function test_editor_preview_collection_handler() {
		$preview_query = $this->getMockBuilder( \stdClass::class )
			->setMethods( [ '__invoke' ] )
			->getMock();
		$this->block_instance->register_collection_handlers(
			'test-collection',
			function () {
				return array();
			},
			null,
			null,
			$preview_query
		);

		$preview_query->expects( $this->once() )
			->method( '__invoke' )
			->willReturn(
				array(
					'post__in' => array( 123 ),
				)
			);

		$args    = array();
		$request = $this->build_request();
		$request->set_param(
			'productCollectionQueryContext',
			array(
				'collection' => 'test-collection',
			)
		);
		$request->set_param(
			'previewState',
			array(
				'isPreview' => 'true',
			)
		);

		$updated_query = $this->block_instance->update_rest_query_in_editor( $args, $request );

		$this->block_instance->unregister_collection_handlers( 'test-collection' );

		$this->assertContains( 123, $updated_query['post__in'] );
	}

	/**
	 * Tests that the related products collection handler works as expected.
	 */
	public function test_collection_related_products() {
		$related_filter = $this->getMockBuilder( \stdClass::class )
		->setMethods( [ '__invoke' ] )
		->getMock();

		$expected_product_ids = array( 2, 3, 4 );

		// This filter will turn off the data store so we don't need dummy products.
		add_filter( 'woocommerce_product_related_posts_force_display', '__return_true', 0 );
		$related_filter->expects( $this->exactly( 2 ) )
			->method( '__invoke' )
			->with( array(), 1 )
			->willReturn( $expected_product_ids );
		add_filter( 'woocommerce_related_products', array( $related_filter, '__invoke' ), 10, 2 );

		// Frontend.
		$parsed_block                                       = $this->get_base_parsed_block();
		$parsed_block['attrs']['collection']                = 'woocommerce/product-collection/related';
		$parsed_block['attrs']['query']['productReference'] = 1;
		$result_frontend                                    = $this->initialize_merged_query( $parsed_block );

		// Editor.
		$request = $this->build_request(
			array( 'productReference' => 1 )
		);
		$request->set_param(
			'productCollectionQueryContext',
			array(
				'collection' => 'woocommerce/product-collection/related',
			)
		);
		$result_editor = $this->block_instance->update_rest_query_in_editor( array(), $request );

		remove_filter( 'woocommerce_product_related_posts_force_display', '__return_true', 0 );
		remove_filter( 'woocommerce_related_products', array( $related_filter, '__invoke' ) );

		$this->assertEqualsCanonicalizing( $expected_product_ids, $result_frontend['post__in'] );
		$this->assertEqualsCanonicalizing( $expected_product_ids, $result_editor['post__in'] );
	}

	/**
	 * Tests that the upsells collection handler works as expected.
	 */
	public function test_collection_upsells() {
		$expected_product_ids = array( 2, 3, 4 );
		$test_product         = WC_Helper_Product::create_simple_product( false );
		$test_product->set_upsell_ids( $expected_product_ids );
		$test_product->save();

		// Frontend.
		$parsed_block                                       = $this->get_base_parsed_block();
		$parsed_block['attrs']['collection']                = 'woocommerce/product-collection/upsells';
		$parsed_block['attrs']['query']['productReference'] = $test_product->get_id();
		$result_frontend                                    = $this->initialize_merged_query( $parsed_block );

		// Editor.
		$request = $this->build_request(
			array( 'productReference' => $test_product->get_id() )
		);
		$request->set_param(
			'productCollectionQueryContext',
			array(
				'collection' => 'woocommerce/product-collection/upsells',
			)
		);
		$result_editor = $this->block_instance->update_rest_query_in_editor( array(), $request );

		$this->assertEqualsCanonicalizing( $expected_product_ids, $result_frontend['post__in'] );
		$this->assertEqualsCanonicalizing( $expected_product_ids, $result_editor['post__in'] );
	}

	/**
	 * Tests that the cross-sells collection handler works as expected.
	 */
	public function test_collection_cross_sells() {
		$expected_product_ids = array( 2, 3, 4 );
		$test_product         = WC_Helper_Product::create_simple_product( false );
		$test_product->set_cross_sell_ids( $expected_product_ids );
		$test_product->save();

		// Frontend.
		$parsed_block                                       = $this->get_base_parsed_block();
		$parsed_block['attrs']['collection']                = 'woocommerce/product-collection/cross-sells';
		$parsed_block['attrs']['query']['productReference'] = $test_product->get_id();
		$result_frontend                                    = $this->initialize_merged_query( $parsed_block );

		// Editor.
		$request = $this->build_request(
			array( 'productReference' => $test_product->get_id() )
		);
		$request->set_param(
			'productCollectionQueryContext',
			array(
				'collection' => 'woocommerce/product-collection/cross-sells',
			)
		);
		$result_editor = $this->block_instance->update_rest_query_in_editor( array(), $request );

		$this->assertEqualsCanonicalizing( $expected_product_ids, $result_frontend['post__in'] );
		$this->assertEqualsCanonicalizing( $expected_product_ids, $result_editor['post__in'] );
	}

	/**
	 * Tests that the hand-picked collection handler works with empty product selection.
	 */
	public function test_collection_hand_picked_empty() {
		// Frontend.
		$parsed_block                        = $this->get_base_parsed_block();
		$parsed_block['attrs']['collection'] = 'woocommerce/product-collection/hand-picked';
		$parsed_block['attrs']['query']['woocommerceHandPickedProducts'] = array();
		$result_frontend = $this->initialize_merged_query( $parsed_block );

		// Editor.
		$request = $this->build_request(
			array( 'woocommerceHandPickedProducts' => array() )
		);
		$request->set_param(
			'productCollectionQueryContext',
			array(
				'collection' => 'woocommerce/product-collection/hand-picked',
			)
		);
		$result_editor = $this->block_instance->update_rest_query_in_editor( array(), $request );

		$this->assertEquals( array( -1 ), $result_frontend['post__in'] );
		$this->assertEquals( array( -1 ), $result_editor['post__in'] );
	}

	/**
	 * Tests that the hand-picked collection handler preserves product order.
	 */
	public function test_collection_hand_picked_order() {
		$product_ids = array( 4, 2, 7, 1 );

		// Frontend.
		$parsed_block                        = $this->get_base_parsed_block();
		$parsed_block['attrs']['collection'] = 'woocommerce/product-collection/hand-picked';
		$parsed_block['attrs']['query']['woocommerceHandPickedProducts'] = $product_ids;
		$result_frontend = $this->initialize_merged_query( $parsed_block );

		// Editor.
		$request = $this->build_request(
			array( 'woocommerceHandPickedProducts' => $product_ids )
		);
		$request->set_param(
			'productCollectionQueryContext',
			array(
				'collection' => 'woocommerce/product-collection/hand-picked',
			)
		);
		$result_editor = $this->block_instance->update_rest_query_in_editor( array(), $request );

		// Order should be preserved exactly as specified.
		$this->assertEquals( $product_ids, $result_frontend['post__in'] );
		$this->assertEquals( $product_ids, $result_editor['post__in'] );
	}
}
