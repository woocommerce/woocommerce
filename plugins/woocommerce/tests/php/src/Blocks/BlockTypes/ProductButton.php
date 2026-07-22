<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;

/**
 * Tests for the Product Button block type.
 */
class ProductButton extends \WP_UnitTestCase {

	/**
	 * Previous WooCommerce options to restore after each test.
	 *
	 * @var array<string, mixed>
	 */
	private $previous_options = array();

	/**
	 * Set up test options.
	 */
	protected function setUp(): void {
		parent::setUp();

		foreach ( array( 'woocommerce_cart_redirect_after_add', 'woocommerce_enable_ajax_add_to_cart' ) as $option_name ) {
			$this->previous_options[ $option_name ] = get_option( $option_name, null );
			update_option( $option_name, 'no' );
		}
	}

	/**
	 * Restore test options.
	 */
	protected function tearDown(): void {
		foreach ( $this->previous_options as $option_name => $value ) {
			if ( null === $value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $value );
			}
		}

		parent::tearDown();
	}

	/**
	 * Render the Product Button block for a product.
	 *
	 * @param \WC_Product $product Product to render.
	 * @return string Rendered block markup.
	 */
	private function render_product_button( \WC_Product $product ): string {
		return do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><!-- wp:woocommerce/product-button /--><!-- /wp:woocommerce/single-product -->'
		);
	}

	/**
	 * @testdox Product permalink links do not include nofollow.
	 */
	public function test_product_permalink_does_not_include_nofollow(): void {
		$product = WC_Helper_Product::create_variation_product();
		$markup  = $this->render_product_button( $product );

		$this->assertStringContainsString( 'href="' . esc_url( $product->get_permalink() ) . '"', $markup );
		$this->assertStringNotContainsString( 'rel="nofollow"', $markup );
	}

	/**
	 * @testdox Direct add-to-cart links retain nofollow.
	 */
	public function test_direct_add_to_cart_link_retains_nofollow(): void {
		$product = WC_Helper_Product::create_simple_product();
		$markup  = $this->render_product_button( $product );

		$this->assertStringContainsString( 'rel="nofollow"', $markup );
	}

	/**
	 * @testdox External product links retain nofollow and security attributes.
	 */
	public function test_external_product_link_retains_nofollow_and_security_attributes(): void {
		$product = WC_Helper_Product::create_external_product();
		$markup  = $this->render_product_button( $product );

		$this->assertStringContainsString( 'rel="nofollow noopener noreferrer"', $markup );
	}
}
