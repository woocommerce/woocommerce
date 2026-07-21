<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductButton block type.
 */
class ProductButtonTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should escape an external product's button text.
	 */
	public function test_render_escapes_external_product_button_text(): void {
		$product = WC_Helper_Product::create_external_product();
		$product->set_button_text( '<img src=x onerror=alert(document.cookie)>' );
		$product->save();

		$markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} -->' .
			'<!-- wp:woocommerce/product-button /-->' .
			'<!-- /wp:woocommerce/single-product -->'
		);

		$this->assertStringContainsString( '&lt;img src=x onerror=alert(document.cookie)&gt;', $markup, 'The button text should be escaped.' );
		$this->assertStringNotContainsString( '<img src=x onerror=alert(document.cookie)>', $markup, 'The button text should not render as HTML.' );

		$product->delete( true );
	}
}
