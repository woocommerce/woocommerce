<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Renderer;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests cart references supplied by a Product Collection ancestor.
 */
class CartReferenceTest extends WC_Unit_Test_Case {
	/**
	 * Script modules already enqueued before each test.
	 *
	 * @var string[]
	 */
	private $enqueued_modules;

	/**
	 * Record the script module queue, which WordPress does not reset between tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enqueued_modules = wp_script_modules()->get_queue();
	}

	/**
	 * Restore modules added by the renderer.
	 */
	public function tearDown(): void {
		try {
			foreach ( array_diff( wp_script_modules()->get_queue(), $this->enqueued_modules ) as $module_id ) {
				wp_dequeue_script_module( $module_id );
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should use the cart context on any page and retain explicit references and hand-picked filters.
	 * @testWith ["cross-sells", "set_cross_sell_ids"]
	 *           ["upsells", "set_upsell_ids"]
	 *
	 * @param string $collection Collection name.
	 * @param string $setter     Product recommendation setter.
	 */
	public function test_cart_context_and_explicit_references( string $collection, string $setter ): void {
		$cart_product     = WC_Helper_Product::create_simple_product();
		$explicit_product = WC_Helper_Product::create_simple_product();
		$cart_product->$setter( array( 101, 102 ) );
		$cart_product->save();
		$explicit_product->$setter( array( 103 ) );
		$explicit_product->save();
		WC()->cart->add_to_cart( $cart_product->get_id() );

		$parsed_block                        = Utils::get_base_parsed_block();
		$parsed_block['attrs']['collection'] = 'woocommerce/product-collection/' . $collection;
		$renderer                            = new Renderer();
		$context                             = $renderer->extend_context_for_inner_blocks(
			array( Renderer::PRODUCT_REFERENCE_CONTEXT => Renderer::REFERENCE_TYPE_CART ),
			$parsed_block
		);
		$this->assertSame(
			array(
				'type'       => 'cart',
				'sourceData' => array( 'productIds' => array( $cart_product->get_id() ) ),
			),
			$context['productCollectionLocation']
		);
		$parsed_block['attrs']['productCollectionLocation'] = $context['productCollectionLocation'];
		$sut   = new ProductCollectionMock();
		$query = Utils::initialize_merged_query( $sut, $parsed_block );
		$this->assertSame( array( 101, 102 ), array_values( $query['post__in'] ) );

		$parsed_block['attrs']['query']['woocommerceHandPickedProducts'] = array( 102, 103 );
		$query = Utils::initialize_merged_query( $sut, $parsed_block );
		$this->assertSame( array( 102 ), array_values( $query['post__in'] ), 'Hand-picked products must still intersect with recommendations.' );

		unset( $parsed_block['attrs']['query']['woocommerceHandPickedProducts'] );
		$parsed_block['attrs']['query']['productReferenceType'] = 'cart';
		$parsed_block['attrs']['query']['productReference']     = $explicit_product->get_id();
		$query = Utils::initialize_merged_query( $sut, $parsed_block );
		$this->assertSame( array( 103 ), array_values( $query['post__in'] ), 'An explicit product takes precedence over both cart references.' );

		$request = Utils::build_request(
			array(
				'productCollectionQueryContext' => array( 'collection' => $parsed_block['attrs']['collection'] ),
				'productReferenceType'          => 'cart',
				'productReference'              => $explicit_product->get_id(),
			)
		);
		$query   = $sut->update_rest_query_in_editor( array(), $request );
		$this->assertSame( array( 103 ), array_values( $query['post__in'] ), 'Editor previews must honor the same explicit product.' );
	}

	/**
	 * @testdox Should preserve an empty cart collection with query ID zero and reset context before the next collection.
	 */
	public function test_empty_cart_placeholder_and_context_isolation(): void {
		$sut                       = new Renderer();
		$block                     = Utils::get_base_parsed_block();
		$block['attrs']['queryId'] = 0;
		$sut->set_parsed_block( $block );
		$sut->extend_context_for_inner_blocks( array( Renderer::PRODUCT_REFERENCE_CONTEXT => 'cart' ), $block );
		$html = $sut->handle_rendering( '<div></div>', $block );
		$this->assertStringContainsString( 'data-wp-router-region="wc-product-collection-0"', $html );
		$this->assertStringContainsString( 'data-wp-watch---cart-reference="callbacks.refreshCartReference"', $html );
		$this->assertSame( '', $sut->handle_rendering( '<div></div>', $block ), 'Cart context must not leak to a later collection.' );
	}

	/**
	 * @testdox Should not attach cart refresh behavior to an explicitly selected product.
	 */
	public function test_explicit_product_does_not_watch_cart(): void {
		$sut                       = new Renderer();
		$block                     = Utils::get_base_parsed_block();
		$block['attrs']['queryId'] = 0;
		$block['attrs']['query']['productReference']     = 123;
		$block['attrs']['query']['productReferenceType'] = 'cart';
		$sut->set_parsed_block( $block );
		$sut->extend_context_for_inner_blocks( array( Renderer::PRODUCT_REFERENCE_CONTEXT => 'cart' ), $block );
		$html = $sut->enhance_product_collection_with_interactivity( '<div class="wp-block-woocommerce-product-collection">Products</div>', $block );
		$this->assertStringNotContainsString( 'callbacks.refreshCartReference', $html );
		$this->assertSame( '', $sut->handle_rendering( '<div></div>', $block ), 'An empty explicit collection does not need a cart placeholder.' );
	}
}
