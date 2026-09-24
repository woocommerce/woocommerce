<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Description block type.
 */
class ProductDescriptionTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should not render a password-protected product description.
	 */
	public function test_does_not_render_password_protected_product_description(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_description( 'Protected product description' );
		$product->save();

		$block = new \WP_Block(
			array(
				'blockName'    => 'woocommerce/product-description',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			),
			array( 'postId' => $product->get_id() )
		);

		$this->assertStringContainsString(
			'Protected product description',
			$block->render(),
			'The test product description should render before password protection is enabled.'
		);

		$product->set_post_password( 'secret' );
		$product->save();

		$this->assertStringNotContainsString(
			'Protected product description',
			$block->render(),
			'Password-protected product descriptions should not render before password entry.'
		);
	}
}
