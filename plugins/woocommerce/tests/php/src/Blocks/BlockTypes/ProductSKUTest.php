<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductSKU block type.
 */
class ProductSKUTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should bind the SKU directive to the unified store's productScope.
	 */
	public function test_render_binds_sku_to_the_unified_product_scope(): void {
		$product = WC_Helper_Product::create_variation_product();

		$markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} -->' .
			'<!-- wp:woocommerce/product-sku /-->' .
			'<!-- /wp:woocommerce/single-product -->'
		);

		$this->assertStringContainsString( 'data-wp-interactive="woocommerce" data-wp-text="state.productScope.product.sku"', $markup, 'The SKU directive should read from the unified productScope.' );
		$this->assertStringNotContainsString( 'woocommerce/products', $markup, 'The markup should not reference the woocommerce/products namespace.' );
		$this->assertStringNotContainsString( 'productInContext', $markup, 'The markup should not reference the productInContext getter.' );
	}
}
