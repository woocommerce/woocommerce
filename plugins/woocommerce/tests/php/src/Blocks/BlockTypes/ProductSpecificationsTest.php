<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductSpecifications block type.
 */
class ProductSpecificationsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should bind the weight row directive to the unified store's productScope.
	 */
	public function test_render_binds_weight_to_the_unified_product_scope(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_weight( '10' );
		$product->save();

		$markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} -->' .
			'<!-- wp:woocommerce/product-specifications /-->' .
			'<!-- /wp:woocommerce/single-product -->'
		);

		$this->assertStringContainsString( 'data-wp-interactive="woocommerce" data-wp-text="state.productScope.product.formatted_weight"', $markup, 'The weight row should read from the unified productScope.' );
		$this->assertStringNotContainsString( 'woocommerce/products', $markup, 'The old woocommerce/products namespace should not be referenced.' );
		$this->assertStringNotContainsString( 'productInContext', $markup, 'The old productInContext getter should not be referenced.' );
	}
}
