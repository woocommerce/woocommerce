<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection\Utils;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;
use WC_Helper_Product;

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
	 * Initiate the mock object.
	 */
	protected function setUp(): void {
		$this->block_instance = new ProductCollectionMock();
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

		$parsed_block                        = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection'] = 'test-collection';

		$merged_query = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

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
		$request = Utils::build_request();
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
		$request = Utils::build_request();
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
		$parsed_block                                       = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection']                = 'woocommerce/product-collection/related';
		$parsed_block['attrs']['query']['productReference'] = 1;
		$result_frontend                                    = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		// Editor.
		$request = Utils::build_request(
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
		$parsed_block                                       = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection']                = 'woocommerce/product-collection/upsells';
		$parsed_block['attrs']['query']['productReference'] = $test_product->get_id();
		$result_frontend                                    = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		// Editor.
		$request = Utils::build_request(
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
		$parsed_block                                       = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection']                = 'woocommerce/product-collection/cross-sells';
		$parsed_block['attrs']['query']['productReference'] = $test_product->get_id();
		$result_frontend                                    = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		// Editor.
		$request = Utils::build_request(
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
		$parsed_block                        = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection'] = 'woocommerce/product-collection/hand-picked';
		$parsed_block['attrs']['query']['woocommerceHandPickedProducts'] = array();
		$result_frontend = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		// Editor.
		$request = Utils::build_request(
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
		$parsed_block                        = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection'] = 'woocommerce/product-collection/hand-picked';
		$parsed_block['attrs']['query']['woocommerceHandPickedProducts'] = $product_ids;
		$result_frontend = Utils::initialize_merged_query( $this->block_instance, $parsed_block );

		// Editor.
		$request = Utils::build_request(
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

	/**
	 * Tests that the cross-sells collection handler works with cart context.
	 */
	public function test_collection_cross_sells_cart_context() {
		// Create cart products with cross-sells.
		$cart_product_1 = WC_Helper_Product::create_simple_product( false );
		$cart_product_2 = WC_Helper_Product::create_simple_product( false );

		// Create cross-sell products.
		$cross_sell_1 = WC_Helper_Product::create_simple_product();
		$cross_sell_2 = WC_Helper_Product::create_simple_product();
		$cross_sell_3 = WC_Helper_Product::create_simple_product();

		// Set up cross-sells for cart products.
		$cart_product_1->set_cross_sell_ids( array( $cross_sell_1->get_id(), $cross_sell_2->get_id() ) );
		$cart_product_1->save();

		$cart_product_2->set_cross_sell_ids( array( $cross_sell_2->get_id(), $cross_sell_3->get_id() ) );
		$cart_product_2->save();

		$cart_product_ids = array( $cart_product_1->get_id(), $cart_product_2->get_id() );

		// Frontend - test using the standard block setup pattern.
		$parsed_block                        = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection'] = 'woocommerce/product-collection/cross-sells';

		// Set the product collection location context for cart.
		$this->block_instance->set_parsed_block( $parsed_block );

		// Create a mock block context with cart location.
		$block                                       = new \stdClass();
		$block->context                              = $parsed_block['attrs'];
		$block->context['productCollectionLocation'] = array(
			'type'       => 'cart',
			'sourceData' => array(
				'productIds' => $cart_product_ids,
			),
		);

		// Test the frontend query building process.
		$query_args = $this->block_instance->build_frontend_query( array(), $block, 1 );

		// Verify that cross-sells from both cart products are included.
		$this->assertArrayHasKey( 'post__in', $query_args );
		$this->assertContains( $cross_sell_1->get_id(), $query_args['post__in'] );
		$this->assertContains( $cross_sell_2->get_id(), $query_args['post__in'] );
		$this->assertContains( $cross_sell_3->get_id(), $query_args['post__in'] );

		// Verify cart products are NOT included in cross-sells.
		$this->assertNotContains( $cart_product_1->get_id(), $query_args['post__in'] );
		$this->assertNotContains( $cart_product_2->get_id(), $query_args['post__in'] );

		// Verify we have exactly 3 cross-sell products (no duplicates).
		$this->assertCount( 3, $query_args['post__in'] );

		// Clean up.
		$cart_product_1->delete( true );
		$cart_product_2->delete( true );
		$cross_sell_1->delete( true );
		$cross_sell_2->delete( true );
		$cross_sell_3->delete( true );
	}
}
