<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

/**
 * Tests for the ProductSKU block type.
 */
class ProductSKU extends \WP_UnitTestCase {

	/**
	 * Tests that the SKU block renders the product SKU for a simple product.
	 */
	public function test_renders_simple_product_sku() {
		$product = new \WC_Product_Simple();
		$product->set_sku( 'SIMPLE-SKU-001' );
		$product_id = $product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-sku /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wp-block-woocommerce-product-sku', $markup, 'The Single Product Block contains the Product SKU block.' );
		$this->assertStringContainsString( 'SIMPLE-SKU-001', $markup, 'The Product SKU block contains the simple product SKU.' );

		$product->delete( true );
	}

	/**
	 * Tests that the SKU block does not render anything for a simple product with no SKU.
	 */
	public function test_does_not_render_for_simple_product_without_sku() {
		$product = new \WC_Product_Simple();
		$product->set_sku( '' );
		$product_id = $product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-sku /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringNotContainsString( 'wp-block-woocommerce-product-sku', $markup, 'The Product SKU block is not rendered when a simple product has no SKU.' );

		$product->delete( true );
	}

	/**
	 * Tests that the SKU block renders the parent SKU for a variable product when one is set.
	 */
	public function test_renders_variable_product_parent_sku() {
		$product = new \WC_Product_Variable();
		$product->set_sku( 'VAR-PARENT-001' );
		$product_id = $product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-sku /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wp-block-woocommerce-product-sku', $markup, 'The Product SKU block is rendered for a variable product with a parent SKU.' );
		$this->assertStringContainsString( 'VAR-PARENT-001', $markup, 'The Product SKU block contains the variable product parent SKU.' );

		$product->delete( true );
	}

	/**
	 * Tests that the SKU block renders the "N/A" placeholder for a variable product without a parent SKU.
	 *
	 * Regression test for woocommerce/woocommerce#46323: the SKU block was returning an empty string
	 * when a variable product had no parent SKU, even when variation SKUs existed. Expected behavior
	 * (matching the classic theme's single-product/meta.php template) is to render "N/A" so the
	 * interactive layer can swap in the variation SKU once a variation is selected.
	 */
	public function test_renders_na_for_variable_product_without_parent_sku() {
		$product = new \WC_Product_Variable();
		$product->set_sku( '' );
		$product_id = $product->save();

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $product_id );
		$variation->set_sku( 'VAR-CHILD-001' );
		$variation->set_regular_price( '10.00' );
		$variation->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-sku /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wp-block-woocommerce-product-sku', $markup, 'The Product SKU block is rendered for a variable product even without a parent SKU.' );
		$this->assertStringContainsString( 'N/A', $markup, 'The Product SKU block renders the "N/A" placeholder when the variable product has no parent SKU.' );
		// The interactive directive should be present so the client can swap in the variation SKU.
		$this->assertStringContainsString( 'data-wp-interactive="woocommerce/products"', $markup, 'The Product SKU block is interactive for variable products.' );
		$this->assertStringContainsString( 'state.productInContext.sku', $markup, 'The Product SKU block binds to the productInContext SKU state.' );

		$variation->delete( true );
		$product->delete( true );
	}
}
