<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

/**
 * Tests for the ProductSaleBadge block type
 */
class ProductSaleBadge extends \WP_UnitTestCase {

	/**
	 * Tests that the Product Sale Badge block is rendered correctly on the Single Product Block
	 */
	public function test_product_sale_badge_render_single_product_block() {
		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-sale-badge /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wp-block-woocommerce-product-sale-badge', $markup, 'The Single Product Block contains the Product Sale Badge block.' );
		$this->assertStringContainsString( 'Sale', $markup, 'The Product Sale Badge block contains the sale text.' );

		$product->delete();
	}

	/**
	 * Tests that the woocommerce_sale_badge_text filter works correctly in Single Product block.
	 */
	public function test_product_sale_badge_render_single_product_block_with_custom_text() {
		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product_id = $product->save();

		$default_sale_text = null;
		/** @var \WC_Product|null */
		$received_product = null;

		add_filter(
			'woocommerce_sale_badge_text',
			function ( $sale_text, $product_obj ) use ( &$default_sale_text, &$received_product ) {
				$default_sale_text = $sale_text;
				$received_product  = $product_obj;
				return 'Special Offer!';
			},
			10,
			2
		);

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-sale-badge /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wp-block-woocommerce-product-sale-badge', $markup, 'The Single Product Block contains the Product Sale Badge block.' );
		$this->assertStringContainsString( 'Special Offer!', $markup, 'The Product Sale Badge block contains the custom sale text.' );
		$this->assertStringNotContainsString( 'Sale', $markup, 'The Product Sale Badge block does not contain the default sale text.' );

		$this->assertInstanceOf( \WC_Product::class, $received_product, 'The filter received a WC_Product object.' );
		/**
		 * Check that the filter received the correct parameters.
		 */
		$this->assertEquals( $product_id, $received_product->get_id(), 'The filter received the correct product object.' );
		$this->assertEquals( 'Sale', $default_sale_text, 'The default sale text is not modified.' );

		remove_all_filters( 'woocommerce_sale_badge_text' );
		$product->delete();
	}

	/**
	 * Tests that the woocommerce_sale_badge_text filter works correctly in Product Collection block.
	 */
	public function test_product_sale_badge_render_product_collection_block_with_custom_text() {
		$product1 = \WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 20 );
		$product1->set_sale_price( 15 );
		$product1->save();

		$product2 = \WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 30 );
		$product2->set_sale_price( 25 );
		$product2->save();

		$product_ids = array( $product1->get_id(), $product2->get_id() );

		$default_sale_text = null;
		/** @var \WC_Product|null */
		$received_product = null;

		add_filter(
			'woocommerce_sale_badge_text',
			function ( $sale_text, $product_obj ) use ( &$default_sale_text, &$received_product ) {
				$default_sale_text = $sale_text;
				$received_product  = $product_obj;
				$sale_price        = (float) $product_obj->get_sale_price();
				if ( $sale_price < 20 ) {
					return 'Special Deal!';
				}
				return 'Limited Time!';
			},
			10,
			2
		);

		$collection_block  = '<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"isProductCollectionBlock":true,"woocommerceHandPickedProducts":[' . implode( ',', $product_ids ) . ']}} -->';
		$collection_block .= '<!-- wp:woocommerce/product-template -->';
		$collection_block .= '<!-- wp:woocommerce/product-sale-badge /-->';
		$collection_block .= '<!-- /wp:woocommerce/product-template -->';
		$collection_block .= '<!-- /wp:woocommerce/product-collection -->';

		$markup = do_blocks( $collection_block );

		$this->assertStringContainsString( 'Special Deal!', $markup, 'Product with sale price < 20 should show "Special Deal!"' );
		$this->assertStringContainsString( 'Limited Time!', $markup, 'Product with sale price >= 20 should show "Limited Time!"' );
		$this->assertStringNotContainsString( 'Sale', $markup, 'Default "Sale" text should not appear when filter is applied' );

		$this->assertInstanceOf( \WC_Product::class, $received_product, 'The filter received a WC_Product object.' );
		$this->assertEquals( 'Sale', $default_sale_text, 'The default sale text is not modified.' );

		remove_all_filters( 'woocommerce_sale_badge_text' );

		$product1->delete();
		$product2->delete();
	}
}
