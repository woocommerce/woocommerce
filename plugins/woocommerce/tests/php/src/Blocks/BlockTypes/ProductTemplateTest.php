<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\SharedStores\ProductScopes;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Template block type.
 */
class ProductTemplateTest extends WC_Unit_Test_Case {

	/**
	 * Reset the scope helper's per-request state so occurrence counts and
	 * open places do not bleed between tests.
	 */
	public function setUp(): void {
		parent::setUp();
		ProductScopes::reset();
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
	 * Extract the `woocommerce`-namespaced `data-wp-context` declared on each
	 * `<li>` item of a rendered Product Template.
	 *
	 * @param string $markup Rendered markup.
	 * @return array<int, array{namespace: string, context: array}> One entry per `<li>` found.
	 */
	private function get_item_scope_contexts( string $markup ): array {
		$processor = new \WP_HTML_Tag_Processor( $markup );
		$items     = array();

		while ( $processor->next_tag( array( 'tag_name' => 'LI' ) ) ) {
			$context = $processor->get_attribute( 'data-wp-context' );
			if ( ! is_string( $context ) ) {
				continue;
			}

			list( $namespace, $json_context ) = explode( '::', $context, 2 );
			$items[]                          = array(
				'namespace' => $namespace,
				'context'   => json_decode( $json_context, true ),
			);
		}

		return $items;
	}

	/**
	 * @testdox Each product tile declares productId, variation and scopeName together in the woocommerce namespace.
	 */
	public function test_product_template_item_declares_woocommerce_scope_context(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$markup = do_blocks( $this->get_product_collection_markup( $product->get_id() ) );
			$items  = $this->get_item_scope_contexts( $markup );

			$this->assertCount( 1, $items );
			$this->assertSame( 'woocommerce', $items[0]['namespace'] );
			$this->assertSame( $product->get_id(), $items[0]['context']['productId'] );
			$this->assertSame( array(), $items[0]['context']['variation'] );
			$this->assertIsString( $items[0]['context']['scopeName'] );
			$this->assertNotSame( '', $items[0]['context']['scopeName'] );
			$this->assertStringNotContainsString( 'woocommerce/products', $markup, 'No context should be declared in the retired woocommerce/products namespace.' );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox Two Product Collections showing the same product receive different scopeName values.
	 */
	public function test_two_collections_showing_same_product_get_different_scope_names(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$markup = do_blocks(
				$this->get_product_collection_markup( $product->get_id() ) . $this->get_product_collection_markup( $product->get_id() )
			);
			$items  = $this->get_item_scope_contexts( $markup );

			$this->assertCount( 2, $items );
			$this->assertNotSame(
				$items[0]['context']['scopeName'],
				$items[1]['context']['scopeName'],
				'Two collections showing the same product should get different scopeName values.'
			);
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox A Single Product block rendered inside a tile gets a different scopeName from the same block rendered at page level.
	 */
	public function test_single_product_inside_tile_gets_different_name_from_page_level(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$page_level_markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%d} --><div class="wp-block-woocommerce-single-product"></div><!-- /wp:woocommerce/single-product -->',
					$product->get_id()
				)
			);

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
					'woocommerceHandPickedProducts' => array( $product->get_id() ),
					'filterable'                    => false,
				),
				'collection' => 'woocommerce/product-collection/hand-picked',
			);

			$tile_markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/product-collection %1$s -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/single-product --><div class="wp-block-woocommerce-single-product"></div><!-- /wp:woocommerce/single-product -->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->',
					wp_json_encode( $attributes )
				)
			);

			$page_level_processor = new \WP_HTML_Tag_Processor( $page_level_markup );
			$this->assertTrue( $page_level_processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-single-product' ) ) );
			list( , $page_level_json ) = explode( '::', $page_level_processor->get_attribute( 'data-wp-context' ), 2 );
			$page_level_scope_name     = json_decode( $page_level_json, true )['scopeName'];

			$tile_processor = new \WP_HTML_Tag_Processor( $tile_markup );
			$this->assertTrue( $tile_processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-single-product' ) ) );
			list( , $tile_json ) = explode( '::', $tile_processor->get_attribute( 'data-wp-context' ), 2 );
			$tile_scope_name     = json_decode( $tile_json, true )['scopeName'];

			$this->assertNotSame( $page_level_scope_name, $tile_scope_name );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}
}
