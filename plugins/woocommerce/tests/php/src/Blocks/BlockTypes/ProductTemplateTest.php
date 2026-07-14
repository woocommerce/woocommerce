<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\SharedStores\CartStore;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Template block type.
 */
class ProductTemplateTest extends WC_Unit_Test_Case {

	/**
	 * Consent string required by the CartStore API.
	 *
	 * @var string
	 */
	protected $consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * Reset the CartStore's static state between tests so scope-stack state
	 * does not bleed from one test into the next.
	 */
	public function tearDown(): void {
		$this->reset_cart_store_static_state();
		parent::tearDown();
	}

	/**
	 * Renders a Product Collection inside a Query Loop post content block.
	 *
	 * @param int $product_id Product ID.
	 * @param int $author_id  Outer post author ID.
	 * @return string Rendered block markup.
	 */
	private function render_product_collection_inside_query_loop( int $product_id, int $author_id ): string {
		$product_collection = $this->get_product_collection_markup( $product_id );
		$post_id            = self::factory()->post->create(
			array(
				'post_author'  => $author_id,
				'post_title'   => 'Post containing Product Collection',
				'post_content' => $product_collection,
			)
		);

		$query_loop = sprintf(
			'<!-- wp:query {"query":{"perPage":1,"postType":"post","order":"desc","orderBy":"date","author":"%1$d","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-content /--><!-- /wp:post-template --></div>
<!-- /wp:query -->',
			$author_id
		);

		try {
			return do_blocks( $query_loop );
		} finally {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Gets Product Collection block markup for a hand-picked product.
	 *
	 * @param int $product_id Product ID.
	 * @return string Product Collection block markup.
	 */
	private function get_product_collection_markup( int $product_id ): string {
		$attributes = array(
			'queryId'    => 0,
			'query'      => array(
				'perPage'                       => 1,
				'pages'                         => 1,
				'offset'                        => 0,
				'postType'                      => 'product',
				'order'                         => 'asc',
				'orderBy'                       => 'post__in',
				'search'                        => '',
				'exclude'                       => array(),
				'inherit'                       => false,
				'taxQuery'                      => array(),
				'isProductCollectionBlock'      => true,
				'featured'                      => false,
				'woocommerceOnSale'             => false,
				'woocommerceStockStatus'        => array( 'instock' ),
				'woocommerceAttributes'         => array(),
				'woocommerceHandPickedProducts' => array( $product_id ),
				'filterable'                    => false,
			),
			'collection' => 'woocommerce/product-collection/hand-picked',
		);

		return sprintf(
			'<!-- wp:woocommerce/product-collection %1$s -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image /-->
<!-- wp:woocommerce/product-price /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->',
			wp_json_encode( $attributes )
		);
	}

	/**
	 * @testdox Should preserve product context when rendered inside a Query Loop post content block.
	 */
	public function test_preserves_product_context_inside_query_loop_post_content(): void {
		$product   = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 25,
				'price'         => 25,
			)
		);
		$author_id = self::factory()->user->create();

		try {
			$markup = $this->render_product_collection_inside_query_loop( $product->get_id(), $author_id );

			$this->assertStringContainsString( 'wc-block-components-product-image', $markup, 'Product image should render using product context.' );
			$this->assertStringContainsString( 'wc-block-components-product-price', $markup, 'Product price should render using product context.' );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox A loop item's <li> carries both the default woocommerce/products context and a hand-rolled woocommerce:: scope context, and the scope stack reflects the item's scope only while its inner blocks render.
	 */
	public function test_loop_item_emits_scope_context_and_pushes_scope_during_inner_block_render(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 25,
				'price'         => 25,
			)
		);

		$page_scope_before   = CartStore::get_current_scope( $this->consent );
		$inner_block_scope   = null;
		$capture_inner_scope = function ( $block_content, $parsed_block ) use ( &$inner_block_scope ) {
			if ( 'woocommerce/product-price' === ( $parsed_block['blockName'] ?? null ) ) {
				$inner_block_scope = CartStore::get_current_scope( $this->consent );
			}
			return $block_content;
		};
		add_filter( 'render_block', $capture_inner_scope, 10, 2 );

		try {
			$markup = do_blocks( $this->get_product_collection_markup( $product->get_id() ) );
		} finally {
			remove_filter( 'render_block', $capture_inner_scope, 10 );
		}

		$page_scope_after = CartStore::get_current_scope( $this->consent );
		$expected_scope   = 'collection/0/' . $product->get_id();

		$expected_products_context = 'data-wp-context=\'woocommerce/products::' . wp_json_encode(
			array(
				'productId'   => $product->get_id(),
				'variationId' => null,
			),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		) . '\'';
		$expected_scope_context    = 'data-wp-context---scope=\'woocommerce::' . wp_json_encode(
			array( 'scope' => $expected_scope ),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		) . '\'';

		$this->assertStringContainsString( $expected_products_context, $markup, 'The default woocommerce/products context should still be present, unaffected by the second context bag.' );
		$this->assertStringContainsString( $expected_scope_context, $markup, 'A second, hand-rolled woocommerce:: scope context should be present.' );
		$this->assertStringContainsString( 'data-wp-key="product-item-' . $product->get_id() . '"', $markup, 'The existing data-wp-key directive should still be present.' );
		$this->assertSame( $expected_scope, $inner_block_scope, 'get_current_scope() should return the loop item scope while its inner blocks render.' );
		$this->assertSame( $page_scope_before, $page_scope_after, 'The page scope should be restored once the loop item has finished rendering.' );

		$product->delete( true );
	}

	/**
	 * @testdox Rendering content with no Product Collection or Single Product block leaves the page scope in effect, with no scope override pushed.
	 */
	public function test_rendering_without_a_scope_establisher_leaves_page_scope_in_effect(): void {
		$page_scope_before = CartStore::get_current_scope( $this->consent );

		do_blocks( '<!-- wp:paragraph --><p>No scope establisher here.</p><!-- /wp:paragraph -->' );

		$page_scope_after = CartStore::get_current_scope( $this->consent );

		$this->assertSame( $page_scope_before, $page_scope_after, 'The page scope should be unaffected when no scope-establishing container renders.' );
	}

	/**
	 * Reset the CartStore's private static properties between tests.
	 */
	private function reset_cart_store_static_state(): void {
		$reflection = new \ReflectionClass( CartStore::class );

		$page_scope = $reflection->getProperty( 'page_scope' );
		$page_scope->setAccessible( true );
		$page_scope->setValue( null, null );

		$scope_stack = $reflection->getProperty( 'scope_stack' );
		$scope_stack->setAccessible( true );
		$scope_stack->setValue( null, array() );
	}
}
