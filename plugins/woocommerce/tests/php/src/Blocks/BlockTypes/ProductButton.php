<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsMocks;
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

		AddToCartWithOptionsMocks::register_all();

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
		$this->assertStringContainsString( 'rel=""', $markup );
	}

	/**
	 * @testdox Product Button filter arguments retain an empty rel attribute for product permalink links.
	 */
	public function test_product_permalink_filter_args_include_empty_rel(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$filtered_args = null;
		$filter        = static function ( array $args ) use ( &$filtered_args ): array {
			$filtered_args = $args;
			return $args;
		};

		add_filter( 'woocommerce_loop_add_to_cart_args', $filter );
		try {
			$this->render_product_button( $product );
		} finally {
			remove_filter( 'woocommerce_loop_add_to_cart_args', $filter );
		}

		$this->assertIsArray( $filtered_args );
		$this->assertArrayHasKey( 'rel', $filtered_args['attributes'] );
		$this->assertSame( '', $filtered_args['attributes']['rel'] );
	}

	/**
	 * Render the Product Button block inside an Add to Cart with Options block.
	 *
	 * @param \WC_Product $product Product to render.
	 * @return string Rendered block markup.
	 */
	private function render_product_button_in_add_to_cart_form( \WC_Product $product ): string {
		return do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><!-- wp:woocommerce/add-to-cart-with-options --><!-- wp:woocommerce/product-button /--><!-- /wp:woocommerce/add-to-cart-with-options --><!-- /wp:woocommerce/single-product -->'
		);
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

	/**
	 * @testdox Standalone / Loop Product Button includes aria-label with product description.
	 */
	public function test_standalone_product_button_includes_aria_label(): void {
		$product = WC_Helper_Product::create_simple_product();
		$markup  = $this->render_product_button( $product );

		$this->assertStringContainsString( 'aria-label="' . esc_attr( $product->add_to_cart_description() ) . '"', $markup );
	}

	/**
	 * @testdox Product Button inside Add to Cart with Options block does not include aria-label for simple products.
	 */
	public function test_product_button_in_add_to_cart_form_omits_aria_label_for_simple_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$markup  = $this->render_product_button_in_add_to_cart_form( $product );

		preg_match( '/<button[^>]*class="[^"]*wc-block-components-product-button__button[^"]*"[^>]*>/', $markup, $matches );
		$this->assertNotEmpty( $matches, 'Product button was not rendered.' );
		$this->assertStringNotContainsString( 'aria-label', $matches[0] );
	}

	/**
	 * @testdox Labels that become empty after sanitization are omitted.
	 */
	public function test_product_button_omits_aria_label_when_sanitization_results_in_empty(): void {
		$product = WC_Helper_Product::create_simple_product();
		$filter  = static function ( array $args ): array {
			$args['attributes']['aria-label'] = '<em></em>';
			return $args;
		};

		add_filter( 'woocommerce_loop_add_to_cart_args', $filter );
		try {
			$markup = $this->render_product_button( $product );
		} finally {
			remove_filter( 'woocommerce_loop_add_to_cart_args', $filter );
		}

		$this->assertStringNotContainsString( 'aria-label', $markup );
	}
}
