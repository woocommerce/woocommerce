<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductStockIndicator block type.
 */
class ProductStockIndicatorTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should bind the stock availability directive to the unified store's productScope.
	 */
	public function test_render_binds_stock_availability_to_the_unified_product_scope(): void {
		global $product;
		$previous_product = $product;
		$product          = WC_Helper_Product::create_variation_product();
		\WC_Product_Variable::sync( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

		$markup = do_blocks( '<!-- wp:woocommerce/product-stock-indicator /-->' );

		$product = $previous_product;

		$this->assertStringContainsString( 'data-wp-interactive="woocommerce"', $markup, 'The stock indicator should declare the unified woocommerce namespace.' );
		$this->assertStringContainsString( 'data-wp-text="state.productScope.product.stock_availability.text"', $markup, 'The stock availability text should read from the unified productScope.' );
		$this->assertStringNotContainsString( 'woocommerce/products', $markup, 'The markup should not reference the woocommerce/products namespace.' );
		$this->assertStringNotContainsString( 'productInContext', $markup, 'The markup should not reference the productInContext getter.' );
	}
}
