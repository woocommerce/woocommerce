<?php
declare( strict_types = 1 );

/**
 * Unit tests for the variation add-to-cart button template and disabled state.
 *
 * @package WooCommerce\Tests\Includes
 */

/**
 * Class WC_Variation_Add_To_Cart_Button_Test
 */
class WC_Variation_Add_To_Cart_Button_Test extends \WC_Unit_Test_Case {

	/**
	 * Variable product for template tests.
	 *
	 * @var WC_Product_Variable
	 */
	private $product;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product = WC_Helper_Product::create_variation_product();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		global $product;
		$product = null;
		wp_dequeue_script( 'wc-add-to-cart-variation' );
		parent::tearDown();
	}

	/**
	 * Renders the variation add-to-cart button template and returns HTML.
	 *
	 * @return string
	 */
	private function render_template(): string {
		global $product;
		$product = $this->product;
		return wc_get_template_html( 'single-product/add-to-cart/variation-add-to-cart-button.php' );
	}

	/**
	 * Asserts the rendered button has the disabled attribute.
	 *
	 * @param string $html Rendered template HTML.
	 */
	private function assert_button_is_disabled( string $html ): void {
		$this->assertStringContainsString( 'single_add_to_cart_button', $html );
		$this->assertStringContainsString( ' disabled>', $html, 'Add to cart button should be disabled' );
	}

	/**
	 * Asserts the rendered button does not have the disabled attribute.
	 *
	 * @param string $html Rendered template HTML.
	 */
	private function assert_button_is_enabled( string $html ): void {
		$this->assertStringContainsString( 'single_add_to_cart_button', $html );
		$this->assertStringNotContainsString( ' disabled>', $html, 'Add to cart button should not be disabled' );
	}

	/**
	 * Registers and enqueues the wc-add-to-cart-variation script so wp_script_is( 'wc-add-to-cart-variation', 'enqueued' ) is true.
	 */
	private function register_and_enqueue_variation_script(): void {
		if ( ! wp_script_is( 'wc-add-to-cart-variation', 'registered' ) ) {
			wp_register_script(
				'wc-add-to-cart-variation',
				'https://example.com/wc-add-to-cart-variation.js',
				array( 'jquery' ),
				WC_VERSION,
				true
			);
		}
		wp_enqueue_script( 'wc-add-to-cart-variation' );
	}

	/**
	 * @testdox When wc-add-to-cart-variation is not enqueued, the button is enabled.
	 */
	public function test_when_script_not_enqueued_renders_enabled_button(): void {
		$html = $this->render_template();
		$this->assert_button_is_enabled( $html );
	}

	/**
	 * @testdox When wc-add-to-cart-variation is enqueued, the button is disabled.
	 */
	public function test_when_script_enqueued_renders_disabled_button(): void {
		$this->register_and_enqueue_variation_script();
		$html = $this->render_template();
		$this->assert_button_is_disabled( $html );
	}
}
