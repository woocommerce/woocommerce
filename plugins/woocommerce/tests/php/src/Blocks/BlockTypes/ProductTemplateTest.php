<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Template block type.
 */
class ProductTemplateTest extends WC_Unit_Test_Case {

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
	 * @testdox A loop item's <li> carries both the default woocommerce/products context and an empty, hand-rolled woocommerce/cart draft-items context, a data-wp-init directive registering it with the cart store, and no scope context.
	 */
	public function test_loop_item_emits_draft_items_context_and_register_or_restore_directive(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 25,
				'price'         => 25,
			)
		);

		try {
			$markup = do_blocks( $this->get_product_collection_markup( $product->get_id() ) );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}

		$expected_products_context = 'data-wp-context=\'woocommerce/products::' . wp_json_encode(
			array(
				'productId'   => $product->get_id(),
				'variationId' => null,
			),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		) . '\'';
		$expected_draft_items_context = 'data-wp-context---draft-items=\'woocommerce/cart::' . wp_json_encode(
			array( 'draftItems' => array() ),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		) . '\'';

		$this->assertStringContainsString( $expected_products_context, $markup, 'The default woocommerce/products context should still be present, unaffected by the second context bag.' );
		$this->assertStringContainsString( $expected_draft_items_context, $markup, 'A second, hand-rolled woocommerce/cart empty draft-items context should be present.' );
		$this->assertStringContainsString( 'data-wp-init="woocommerce/cart::actions.registerOrRestoreDraftCollection"', $markup, 'The loop item should carry the directive registering (or restoring) it with the cart store.' );
		$this->assertStringContainsString( 'data-wp-key="product-item-' . $product->get_id() . '"', $markup, 'The existing data-wp-key directive should still be present.' );
		$this->assertStringNotContainsString( 'data-wp-context---scope', $markup, 'No scope context bag should be emitted.' );
	}
}
