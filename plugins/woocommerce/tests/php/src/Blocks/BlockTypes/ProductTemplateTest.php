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
}
