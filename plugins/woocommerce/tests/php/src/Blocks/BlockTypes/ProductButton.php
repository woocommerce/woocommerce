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
	 * Restore test options and remove the published `woocommerce` state, which the base class does not reset.
	 */
	protected function tearDown(): void {
		try {
			foreach ( $this->previous_options as $option_name => $value ) {
				if ( null === $value ) {
					delete_option( $option_name );
				} else {
					update_option( $option_name, $value );
				}
			}

			$interactivity = wp_interactivity();
			$property      = new \ReflectionProperty( $interactivity, 'state_data' );
			$property->setAccessible( true );
			$state_data = $property->getValue( $interactivity );
			unset( $state_data['woocommerce'] );
			$property->setValue( $interactivity, $state_data );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Render the Product Button block for a product.
	 *
	 * @param \WC_Product $product Product to render.
	 * @return string Rendered block markup.
	 */
	private function render_product_button( \WC_Product $product ): string {
		return do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><div class="wp-block-woocommerce-single-product"><!-- wp:woocommerce/product-button /--></div><!-- /wp:woocommerce/single-product -->'
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
	 * @testdox AJAX button aria-label starts with the visible button text when $quantity are in the cart.
	 * @testWith [0, "Add to cart: “Beanie”"]
	 *           [2, "2 in cart, Add to cart: “Beanie”"]
	 *
	 * @param int    $quantity Quantity of the product in the cart.
	 * @param string $expected Expected aria-label.
	 */
	public function test_ajax_button_aria_label_starts_with_button_text( int $quantity, string $expected ): void {
		update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
		$product = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Beanie' ) );
		wp_interactivity_state(
			'woocommerce',
			array(
				'cart' => array(
					'items' => array(
						array(
							'id'       => $product->get_id(),
							'quantity' => $quantity,
						),
					),
				),
			)
		);

		$aria_label = $this->get_button_aria_label( $this->render_product_button( $product ) );

		$this->assertSame( $expected, $aria_label, 'The aria-label should start with the text shown on the button' );
	}

	/**
	 * @testdox AJAX button aria-label replaces only the first placeholder when product name contains placeholder characters.
	 */
	public function test_ajax_button_aria_label_replaces_only_first_placeholder(): void {
		update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
		$product = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Box ### Special' ) );
		wp_interactivity_state(
			'woocommerce',
			array(
				'cart' => array(
					'items' => array(
						array(
							'id'       => $product->get_id(),
							'quantity' => 1,
						),
					),
				),
			)
		);

		$aria_label = $this->get_button_aria_label( $this->render_product_button( $product ) );

		$this->assertSame( '1 in cart, Add to cart: “Box ### Special”', $aria_label );
	}

	/**
	 * @testdox Labels that become empty after sanitization are omitted.
	 */
	public function test_product_button_omits_aria_label_when_sanitization_results_in_empty(): void {
		update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
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

		$processor = new \WP_HTML_Tag_Processor( $markup );
		$this->assertTrue( $processor->next_tag( 'button' ), 'Button element should exist in rendered markup.' );
		$this->assertNull( $processor->get_attribute( 'aria-label' ), 'Empty aria-label attribute should be omitted.' );
	}

	/**
	 * Get the aria-label of the rendered button.
	 *
	 * @param string $markup Rendered block markup.
	 * @return string|null The aria-label, decoded.
	 */
	private function get_button_aria_label( string $markup ): ?string {
		$processor = new \WP_HTML_Tag_Processor( $markup );
		$processor->next_tag( 'button' );

		return $processor->get_attribute( 'aria-label' );
	}
}
