<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Utils\WC_Product_Custom;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsQuantitySelectorMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsGroupedProductSelectorMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsGroupedProductSelectorItemCTAMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsGroupedProductSelectorItemTemplateMock;

/**
 * Tests for the AddToCartWithOptions block type
 */
class AddToCartWithOptions extends \WP_UnitTestCase {

	/**
	 * Tracks whether blocks have been registered.
	 *
	 * @var bool
	 */
	protected static $are_blocks_registered = false;

	/**
	 * Initiate the mock object.
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! self::$are_blocks_registered ) {
			// We need to register the blocks after set up. They are no registered
			// on `init` because `init` is called with a classic theme.
			new AddToCartWithOptionsMock();
			new AddToCartWithOptionsQuantitySelectorMock();
			new AddToCartWithOptionsGroupedProductSelectorMock();
			new AddToCartWithOptionsGroupedProductSelectorItemTemplateMock();
			new AddToCartWithOptionsGroupedProductSelectorItemCTAMock();

			self::$are_blocks_registered = true;
		}
	}

	/**
	 * Print custom product type add to cart markup.
	 *
	 * Outputs the HTML markup for the custom product type add to cart form.
	 */
	public function print_custom_product_type_add_to_cart_markup() {
		echo 'Custom Product Type Add to Cart Form';
	}

	/**
	 * Tests that the correct content is rendered for each product type.
	 */
	public function test_product_type_add_to_cart_render() {
		add_action( 'woocommerce_custom_add_to_cart', array( $this, 'print_custom_product_type_add_to_cart_markup' ) );

		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		// Single Products contain the Add to Cart button and the quantity selector blocks.
		$this->assertStringContainsString( 'wp-block-woocommerce-product-button', $markup, 'The Simple Product Add to Cart with Options contains the product button block.' );
		$this->assertStringContainsString( 'woocommerce/add-to-cart-with-options-quantity-selector', $markup, 'The Simple Product Add to Cart with Options contains the quantity selector block.' );

		$product    = new \WC_Product_External();
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		// External Products contain the Add to Cart button block but do not contain the quantity selector block.
		$this->assertStringContainsString( 'wp-block-woocommerce-product-button', $markup, 'The External Product Add to Cart with Options contains the product button block.' );
		$this->assertStringNotContainsString( 'woocommerce/add-to-cart-with-options-quantity-selector', $markup, 'The External Product Add to Cart with Options does not contain the quantity selector block.' );

		$product    = new WC_Product_Custom();
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		// Third-party product types use their own template.
		$this->assertStringContainsString( 'Custom Product Type Add to Cart Form', $markup, 'The Custom Product Type Add to Cart with Options contains the custom product type add to cart form.' );

		remove_action( 'woocommerce_custom_add_to_cart', array( $this, 'print_custom_product_type_add_to_cart_markup' ) );
	}

	/**
	 * Tests that the correct CTA is rendered in the Grouped Product Selector.
	 */
	public function test_grouped_product_selector_cta() {
		$simple_product = new \WC_Product_Simple();
		$simple_product->set_regular_price( 10 );
		$simple_product_id = $simple_product->save();
		$grouped_product   = new \WC_Product_Grouped();
		$grouped_product->set_children( array( $simple_product_id ) );
		$grouped_product_id = $grouped_product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $grouped_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringContainsString( 'type="number"', $markup, 'The Grouped Product Add to Cart with Options form contains a numeric input.' );

		$simple_product->set_sold_individually( true );
		$simple_product->save();
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $grouped_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringContainsString( 'type="checkbox"', $markup, 'The Grouped Product Add to Cart with Options form contains a checkbox.' );

		$simple_product->set_stock_status( 'outofstock' );
		$simple_product->save();
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $grouped_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringContainsString( 'Read more', $markup, 'The Grouped Product Add to Cart with Options form contains a button.' );
	}
}
