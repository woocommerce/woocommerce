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
		parent::setUp();
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

		// Reference 1 has no categories or tags; keep force-display off so
		// wc_get_related_products() short-circuits to an empty set instead of querying the
		// data store. The mocked woocommerce_related_products filter then drives the result,
		// independent of any products in the database.
		add_filter( 'woocommerce_product_related_posts_force_display', '__return_false', 0 );
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

		remove_filter( 'woocommerce_product_related_posts_force_display', '__return_false', 0 );
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
	 * @testdox Upsells collection returns a no-results query when the product reference is missing instead of erroring.
	 */
	public function test_collection_upsells_missing_reference_returns_no_results() {
		// The editor renders the preview before a product reference is resolved,
		// so the reference arrives as array( null ) and wc_get_product( null ) is false.
		$request = Utils::build_request();
		$request->set_param(
			'productCollectionQueryContext',
			array(
				'collection' => 'woocommerce/product-collection/upsells',
			)
		);

		$result = $this->block_instance->update_rest_query_in_editor( array(), $request );

		$this->assertSame( array( -1 ), $result['post__in'], 'A missing upsells reference should yield a no-results query, not a fatal.' );
	}

	/**
	 * Cart-based collections keyed by name, each with a callback assigning related ids to a product.
	 *
	 * @return array<string, array{string, callable}>
	 */
	public function provider_cart_collections_with_related_ids(): array {
		return array(
			'upsells'     => array(
				'woocommerce/product-collection/upsells',
				function ( $product, $ids ) {
					$product->set_upsell_ids( $ids );
				},
			),
			'cross-sells' => array(
				'woocommerce/product-collection/cross-sells',
				function ( $product, $ids ) {
					$product->set_cross_sell_ids( $ids );
				},
			),
		);
	}

	/**
	 * @testdox Cart collection does not leak a non-resolving reference id into the results.
	 *
	 * @dataProvider provider_cart_collections_with_related_ids
	 *
	 * @param string   $collection      Collection name, e.g. woocommerce/product-collection/upsells.
	 * @param callable $set_related_ids Receives the cart product and the related ids to assign to it.
	 */
	public function test_cart_collection_excludes_unresolvable_reference_from_results( string $collection, callable $set_related_ids ) {
		// A cart reference can stop resolving mid-session (e.g. the product is deleted). It must not
		// surface in the results even if a resolved cart product still lists it among its related ids.
		$cart_product = WC_Helper_Product::create_simple_product( false );
		$real_related = WC_Helper_Product::create_simple_product();

		// An id guaranteed not to resolve to a product.
		$ghost      = WC_Helper_Product::create_simple_product();
		$missing_id = $ghost->get_id();
		$ghost->delete( true );

		$real_related_id = $real_related->get_id();
		$set_related_ids( $cart_product, array( $real_related_id, $missing_id ) );
		$cart_product->save();

		$parsed_block                        = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection'] = $collection;
		$this->block_instance->set_parsed_block( $parsed_block );

		$block                                       = new \stdClass();
		$block->context                              = $parsed_block['attrs'];
		$block->context['productCollectionLocation'] = array(
			'type'       => 'cart',
			'sourceData' => array(
				'productIds' => array( $cart_product->get_id(), $missing_id ),
			),
		);

		$query_args = $this->block_instance->build_frontend_query( array(), $block, 1 );

		// Delete the products now. If the assertion fails the products would be left over.
		$cart_product->delete( true );
		$real_related->delete( true );

		$this->assertSame(
			array( $real_related_id ),
			array_values( $query_args['post__in'] ),
			'Only the resolvable reference should remain; the non-resolving reference id must not leak.'
		);
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
