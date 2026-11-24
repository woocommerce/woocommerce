<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Button;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product_Button email block renderer.
 */
class ProductButtonTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Product_Button
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Product_Button();
	}

	/**
	 * @testdox Links a simple product's button to the product page with its add-to-cart text.
	 */
	public function test_simple_product_links_to_product_page(): void {
		$product = WC_Helper_Product::create_simple_product();

		$html = $this->render( $product->get_id() );

		$this->assertStringContainsString( 'href="' . esc_url( $product->get_permalink() ) . '"', $html );
		$this->assertStringContainsString( '>Add to cart</a>', $html );
	}

	/**
	 * @testdox Falls back to "Add to cart" when the product has no add-to-cart text.
	 */
	public function test_empty_add_to_cart_text_falls_back(): void {
		$product = WC_Helper_Product::create_simple_product();
		add_filter( 'woocommerce_product_add_to_cart_text', '__return_empty_string' );

		$this->assertStringContainsString( '>Add to cart</a>', $this->render( $product->get_id() ) );
	}

	/**
	 * @testdox Links to the cart with "Finish checkout" in a cart contents collection.
	 */
	public function test_cart_contents_collection_links_to_cart(): void {
		$product = WC_Helper_Product::create_simple_product();

		$html = $this->render( $product->get_id(), 'woocommerce/product-collection/cart-contents' );

		$this->assertStringContainsString( 'href="' . esc_url( wc_get_cart_url() ) . '"', $html );
		$this->assertStringContainsString( '>Finish checkout</a>', $html );
	}

	/**
	 * @testdox Links an external product to its external URL, escaped.
	 */
	public function test_external_product_links_to_external_url(): void {
		$product = WC_Helper_Product::create_external_product();
		$product->set_product_url( 'https://example.com/buy?a=1&b=2' );
		$product->save();

		$this->assertStringContainsString( 'href="https://example.com/buy?a=1&#038;b=2"', $this->render( $product->get_id() ) );
	}

	/**
	 * @testdox Links an external product without a URL to its product page.
	 */
	public function test_external_product_without_url_links_to_product_page(): void {
		$product = WC_Helper_Product::create_external_product();
		$product->set_product_url( '' );
		$product->save();

		$this->assertStringContainsString( 'href="' . esc_url( $product->get_permalink() ) . '"', $this->render( $product->get_id() ) );
	}

	/**
	 * @testdox Renders no button when there is no product in the block context.
	 */
	public function test_without_product_renders_no_button(): void {
		$this->assertStringNotContainsString( '<a ', $this->render( 0 ) );
	}

	/**
	 * Renders the block for a product.
	 *
	 * @param int    $product_id Product ID in the block context.
	 * @param string $collection Product collection the block is in.
	 */
	private function render( int $product_id, string $collection = '' ): string {
		$parsed_block = array(
			'blockName' => 'woocommerce/product-button',
			'attrs'     => array(),
			'context'   => array(
				'postId'     => $product_id,
				'collection' => $collection,
			),
		);
		return $this->sut->render( '', $parsed_block, new Rendering_Context( new \WP_Theme_JSON() ) );
	}
}
