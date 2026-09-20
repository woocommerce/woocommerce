<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\SharedStores\ProductScopes;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Utils\WC_Product_Custom;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsQuantitySelectorMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationDescriptionMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsGroupedProductSelectorMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsGroupedProductItemMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsGroupedProductItemSelectorMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationSelectorMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationSelectorAttributeMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationSelectorAttributeNameMock;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

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

		ProductScopes::reset();

		if ( ! self::$are_blocks_registered ) {
			// We need to register the blocks after set up. They are no registered
			// on `init` because `init` is called with a classic theme.
			new AddToCartWithOptionsMock();
			new AddToCartWithOptionsQuantitySelectorMock();
			new AddToCartWithOptionsVariationDescriptionMock();
			new AddToCartWithOptionsGroupedProductSelectorMock();
			new AddToCartWithOptionsGroupedProductItemMock();
			new AddToCartWithOptionsGroupedProductItemSelectorMock();
			new AddToCartWithOptionsVariationSelectorMock();
			new AddToCartWithOptionsVariationSelectorAttributeMock();
			new AddToCartWithOptionsVariationSelectorAttributeNameMock();

			self::$are_blocks_registered = true;
		}
	}

	/**
	 * Tear down test. Called after every test.
	 *
	 * Clears the two namespaces this class is the only caller of
	 * `wp_interactivity_process_directives()` for: neither
	 * `wp_interactivity_state()` nor `wp_interactivity_config()` reset
	 * between tests, so the `isFormValid` closure and the config this class
	 * seeds would otherwise remain registered on the singleton
	 * `WP_Interactivity_API` instance for the rest of the process.
	 *
	 * Also resets `Utils`'s "getter already registered" flag, which guards
	 * the `inputQuantity` closure the same way. Left true, it would skip
	 * re-registering that closure into the state just cleared above, so the
	 * next test's markup would resolve no value at all.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$interactivity = wp_interactivity();
		$reflection    = new \ReflectionClass( $interactivity );

		foreach ( array( 'state_data', 'config_data' ) as $property_name ) {
			$property = $reflection->getProperty( $property_name );
			$property->setAccessible( true );
			$value = $property->getValue( $interactivity );
			unset( $value['woocommerce/add-to-cart-with-options'], $value['woocommerce/add-to-cart-with-options-quantity-selector'] );
			$property->setValue( $interactivity, $value );
		}

		$utils_reflection = new \ReflectionClass( Utils::class );
		$flag             = $utils_reflection->getProperty( 'input_quantity_getter_registered' );
		$flag->setAccessible( true );
		$flag->setValue( null, false );
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
	 * Hook into the add to cart action.
	 *
	 * Outputs a test message when the add to cart action is triggered.
	 * Used for testing that hooks are properly called during add to cart.
	 */
	public function hook_into_add_to_cart_action() {
		echo 'Hook into add to cart action';
	}

	/**
	 * Hook into the add to cart button action with a <select> element.
	 *
	 * Outputs a select element with an option.
	 * Used for testing that hooks are properly called during add to cart and
	 * fall back to a regular HTML form.
	 */
	public function hook_into_add_to_cart_button_action() {
		echo '<select><option>Hook into add to cart button action</option></select>';
	}

	/**
	 * Hook into the add to cart button action with a text element.
	 *
	 * Outputs a text element.
	 * Used for testing that text output doesn't trigger a fall back to a
	 * regular HTML form.
	 */
	public function hook_into_add_to_cart_button_action_text() {
		echo '<p>Hook into add to cart button action</p>';
	}

	/**
	 * Hook into the woocommerce_add_to_cart_form_action filter.
	 *
	 * Outputs an example URL to test the form action.
	 */
	public function hook_into_woocommerce_add_to_cart_form_action_filter() {
		return 'https://example.com';
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
		$this->assertStringContainsString( 'wp-block-woocommerce-product-button', $markup, 'The Simple Product Add to Cart + Options contains the product button block.' );
		$this->assertStringContainsString( 'Add to cart', $markup, 'The Simple Product Add to Cart Button reads "Add to cart".' );
		$this->assertStringContainsString( 'woocommerce/add-to-cart-with-options-quantity-selector', $markup, 'The Simple Product Add to Cart + Options contains the quantity selector block.' );

		$product    = new \WC_Product_External();
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		// External Products contain the Add to Cart button block but do not contain the quantity selector block.
		$this->assertStringContainsString( 'wp-block-woocommerce-product-button', $markup, 'The External Product Add to Cart + Options contains the product button block.' );
		$this->assertStringContainsString( 'Buy product', $markup, 'The External Product Add to Cart Button reads "Buy product".' );
		$this->assertStringNotContainsString( 'woocommerce/add-to-cart-with-options-quantity-selector', $markup, 'The External Product Add to Cart + Options does not contain the quantity selector block.' );

		$product    = new WC_Product_Custom();
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		// Third-party product types use their own template.
		$this->assertStringContainsString( 'Custom Product Type Add to Cart Form', $markup, 'The Custom Product Type Add to Cart + Options contains the custom product type add to cart form.' );

		remove_action( 'woocommerce_custom_add_to_cart', array( $this, 'print_custom_product_type_add_to_cart_markup' ) );
	}

	/**
	 * Tests that no Add to Cart button is displayed for out of stock products and not purchasable products.
	 *
	 * Verifies that:
	 * 1. Add to Cart button is hidden for not purchasable simple products
	 * 2. Add to Cart button is visible for in-stock purchasable products
	 * 3. Add to Cart button is hidden and stock indicator shows for out of stock products
	 *
	 * @covers AddToCartWithOptions::render
	 */
	public function test_out_of_stock_product() {
		global $product;
		$product    = new \WC_Product_Simple();
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringNotContainsString( 'Add to cart', $markup, 'The Simple Product Add to Cart Button is not visible for not purchasable simple products.' );

		$product->set_regular_price( 10 );
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'Add to cart', $markup, 'The Simple Product Add to Cart Button is visible for purchasable in stock products.' );

		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringNotContainsString( 'Add to cart', $markup, 'The Simple Product Add to Cart Button is not visible for out of stock products.' );
		$this->assertStringContainsString( 'Out of stock', $markup, 'The stock indicator is visible for out of stock products.' );
	}

	/**
	 * Tests that the  woocommerce_<product_type>_add_to_cart hooks are rendered when rendering the block.
	 */
	public function test_product_type_add_to_cart_hooks_are_rendered() {
		add_action( 'woocommerce_simple_add_to_cart', array( $this, 'hook_into_add_to_cart_action' ) );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'hook_into_add_to_cart_button_action' ) );

		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'Hook into add to cart action', $markup, 'The Add to Cart + Options correctly renders the contents from the wrapper hook.' );
		$this->assertStringContainsString( 'Hook into add to cart button action', $markup, 'The Add to Cart + Options doesn\'t render the contents from the inner hooks.' );

		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product_id = $product->save();
		$markup     = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'Hook into add to cart action', $markup, 'The Add to Cart + Options correctly renders the contents from the wrapper hook if the product is out of stock.' );
		$this->assertStringNotContainsString( 'Hook into add to cart button action', $markup, 'The Add to Cart + Options doesn\'t render the contents from the inner hooks if the product is out of stock.' );

		remove_action( 'woocommerce_simple_add_to_cart', array( $this, 'hook_into_add_to_cart_action' ) );
		remove_action( 'woocommerce_before_add_to_cart_button', array( $this, 'hook_into_add_to_cart_button_action' ) );
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
		$this->assertStringContainsString( 'type="number"', $markup, 'The Grouped Product Add to Cart + Options form contains a numeric input.' );

		$simple_product->set_sold_individually( true );
		$simple_product->save();
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $grouped_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringContainsString( 'type="checkbox"', $markup, 'The Grouped Product Add to Cart + Options form contains a checkbox.' );

		$simple_product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$simple_product->save();
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $grouped_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringContainsString( 'Read more', $markup, 'The Grouped Product Add to Cart + Options form contains a button.' );
	}

	/**
	 * Tests that the quantity selector block is not visible for sold individually products and manage stock products with stock quantity <= 1.
	 */
	public function test_stepper_not_visible_for_sold_individually_products_and_manage_stock() {
		$simple_product = new \WC_Product_Simple();
		$simple_product->set_regular_price( 10 );
		$simple_product->set_sold_individually( true );
		$simple_product_id = $simple_product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $simple_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringNotContainsString( 'data-block-name="woocommerce/add-to-cart-with-options-quantity-selector"', $markup, 'The Add to Cart + Options form does not contain a quantity selector block for sold individually products.' );

		$simple_product->set_sold_individually( false );
		$simple_product->set_manage_stock( true );
		$simple_product->set_stock_quantity( 1 );
		$simple_product->save();
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $simple_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringNotContainsString( 'data-block-name="woocommerce/add-to-cart-with-options-quantity-selector"', $markup, 'The Add to Cart + Options form does not contain a quantity selector block for products with manage stock set to true and stock quantity set to 1.' );

		$simple_product->set_stock_quantity( 10 );
		$simple_product->save();
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $simple_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );
		$this->assertStringContainsString( 'data-block-name="woocommerce/add-to-cart-with-options-quantity-selector"', $markup, 'The Add to Cart + Options form contains a quantity selector block for products with manage stock set to true and stock quantity > 1.' );
	}

	/**
	 * Tests that we render a regular HTML form when an extension hooks into the form or when cart redirect is enabled.
	 *
	 * @covers AddToCartWithOptions::render
	 */
	public function test_form_fallback() {
		add_filter( 'woocommerce_add_to_cart_form_action', array( $this, 'hook_into_woocommerce_add_to_cart_form_action_filter' ) );
		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		// Test when cart redirect is enabled.
		update_option( 'woocommerce_cart_redirect_after_add', 'yes' );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'action="https://example.com"', $markup, 'The form has an action that redirects to the page defined by the woocommerce_add_to_cart_form_action filter.' );
		$this->assertStringNotContainsString( 'data-wp-on--submit', $markup, 'The form doesn\'t have an on submit event when redirect after add is enabled.' );

		// Test when cart redirect is disabled.
		update_option( 'woocommerce_cart_redirect_after_add', 'no' );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringNotContainsString( 'action="https://example.com"', $markup, 'The form doesn\'t have an action that redirects to the page defined by the woocommerce_add_to_cart_form_action filter when redirect after add is disabled.' );
		$this->assertStringContainsString( 'data-wp-on--submit', $markup, 'The form has an on submit event when redirect after add is disabled.' );

		// Test when an extension hooks into the form.
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'hook_into_add_to_cart_button_action' ) );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'action="https://example.com"', $markup, 'The form has an action that redirects to the page defined by the woocommerce_add_to_cart_form_action filter when an extension hooks into the form.' );
		$this->assertStringNotContainsString( 'data-wp-on--submit', $markup, 'The form doesn\'t have an on submit event when an extension hooks into the form.' );

		remove_action( 'woocommerce_before_add_to_cart_button', array( $this, 'hook_into_add_to_cart_button_action' ) );

		// Test when an extension hooks into the form but not adding a form element.
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'hook_into_add_to_cart_button_action_text' ) );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringNotContainsString( 'action="https://example.com"', $markup, 'The form doesn\'t have an action that redirects to the page defined by the woocommerce_add_to_cart_form_action filter when an extension hooks into the form but not adding a form element.' );
		$this->assertStringContainsString( 'data-wp-on--submit', $markup, 'The form has an on submit event when an extension hooks into the form but not adding a form element.' );

		remove_action( 'woocommerce_before_add_to_cart_button', array( $this, 'hook_into_add_to_cart_button_action_text' ) );

		remove_filter( 'woocommerce_add_to_cart_form_action', array( $this, 'hook_into_woocommerce_add_to_cart_form_action_filter' ) );
	}

	/**
	 * @testdox Legacy add-to-cart forms expose the request fields required by each product type.
	 *
	 * @dataProvider provider_legacy_form_fields
	 *
	 * @param string $product_type Product type under test.
	 */
	public function test_legacy_form_fields_for_product_types( string $product_type ): void {
		global $product;

		$previous_product  = $product;
		$products          = array();
		$original_redirect = get_option( 'woocommerce_cart_redirect_after_add' );

		try {
			if ( 'simple' === $product_type ) {
				$product = new \WC_Product_Simple();
				$product->set_name( 'Legacy Simple' );
				$product->set_regular_price( '10' );
				$product->save();
				$products[]      = $product;
				$expected_fields = array( 'name="add-to-cart"', 'name="quantity"' );
			} elseif ( 'variable' === $product_type ) {
				$product = new \WC_Product_Variable();
				$product->set_name( 'Legacy Variable' );
				$product->set_attributes(
					array( \WC_Helper_Product::create_product_attribute_object( 'color', array( 'blue' ) ) )
				);
				$product->save();

				$variation = new \WC_Product_Variation();
				$variation->set_parent_id( $product->get_id() );
				$variation->set_attributes( array( 'pa_color' => 'blue' ) );
				$variation->set_regular_price( '10' );
				$variation->save();
				\WC_Product_Variable::sync( $product->get_id() );

				$products[]      = $variation;
				$products[]      = $product;
				$expected_fields = array( 'name="add-to-cart"', 'name="product_id"', 'name="variation_id"', 'name="attribute_pa_color"', 'name="quantity"' );
			} else {
				$child = new \WC_Product_Simple();
				$child->set_name( 'Legacy Grouped Child' );
				$child->set_regular_price( '10' );
				$child->save();

				$product = new \WC_Product_Grouped();
				$product->set_name( 'Legacy Grouped' );
				$product->set_children( array( $child->get_id() ) );
				$product->save();

				$products[]      = $product;
				$products[]      = $child;
				$expected_fields = array( 'name="add-to-cart"', 'name="quantity[' . $child->get_id() . ']"' );
			}

			update_option( 'woocommerce_cart_redirect_after_add', 'yes' );
			$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

			$this->assertStringContainsString( '<form ', $markup, 'A legacy HTML form should be rendered.' );
			$this->assertStringContainsString( 'method="post"', $markup, 'The legacy form should submit with POST.' );
			$this->assertStringContainsString( 'enctype="multipart/form-data"', $markup, 'The legacy form should retain multipart compatibility.' );
			$this->assertStringNotContainsString( 'data-wp-on--submit="actions.addToCart"', $markup, 'The Interactivity API submit binding should be absent in legacy mode.' );
			$this->assertStringContainsString( 'name="add-to-cart" value="' . $product->get_id() . '"', $markup, 'The parent product ID should be submitted.' );

			foreach ( $expected_fields as $expected_field ) {
				$this->assertStringContainsString( $expected_field, $markup, "The {$product_type} form should contain {$expected_field}." );
			}
		} finally {
			update_option( 'woocommerce_cart_redirect_after_add', $original_redirect );
			$product = $previous_product;
			foreach ( $products as $created_product ) {
				$created_product->delete( true );
			}
		}
	}

	/**
	 * Data provider for legacy form field coverage.
	 *
	 * @return array<string, array{string}>
	 */
	public static function provider_legacy_form_fields(): array {
		return array(
			'simple product'   => array( 'simple' ),
			'variable product' => array( 'variable' ),
			'grouped product'  => array( 'grouped' ),
		);
	}

	/**
	 * @testdox Disabling archive AJAX add-to-cart leaves the block's Interactivity API submit binding enabled.
	 */
	public function test_ajax_archive_setting_does_not_disable_interactive_form(): void {
		global $product;

		$previous_product  = $product;
		$original_ajax     = get_option( 'woocommerce_enable_ajax_add_to_cart' );
		$original_redirect = get_option( 'woocommerce_cart_redirect_after_add' );
		$product           = new \WC_Product_Simple();
		$product->set_regular_price( '10' );
		$product->save();

		try {
			update_option( 'woocommerce_enable_ajax_add_to_cart', 'no' );
			update_option( 'woocommerce_cart_redirect_after_add', 'no' );

			$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

			$this->assertStringContainsString( 'data-wp-on--submit="actions.addToCart"', $markup, 'The block should keep its Interactivity API submit binding.' );
			$this->assertStringNotContainsString( 'method="post"', $markup, 'The archive AJAX option should not force the block into legacy mode.' );
		} finally {
			update_option( 'woocommerce_enable_ajax_add_to_cart', $original_ajax );
			update_option( 'woocommerce_cart_redirect_after_add', $original_redirect );
			$product->delete( true );
			$product = $previous_product;
		}
	}

	/**
	 * Tests that the default attributes are selected when defined in product
	 * data or in the URL parameters.
	 */
	public function test_variable_product_default_option_render() {
		global $product;

		$fixtures = new FixtureData();

		$product = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'green', 'blue' ) ),
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);

		$product_id = $product->get_id();

		$fixtures->get_variation_product(
			$product_id,
			array(
				'pa_color' => 'red-slug',
				'pa_size'  => 'small-slug',
			),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		$fixtures->get_variation_product(
			$product_id,
			array(
				'pa_color' => 'red-slug',
				'pa_size'  => 'medium-slug',
			),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		// Sync the variable product to update its children list.
		\WC_Product_Variable::sync( $product_id );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertDoesNotMatchRegularExpression(
			'/<button[^>]*aria-checked="true"[^>]*>/',
			$markup,
			'No options should be checked by default.'
		);

		$product->set_default_attributes( array( 'pa_size' => 'small-slug' ) );

		$product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertMatchesRegularExpression(
			'/<button[^>]*value="small-slug"[^>]*aria-checked="true"[^>]*>/',
			$markup,
			'The "small" size option should be checked when set as the default attribute.'
		);

		$_GET['attribute_pa_size'] = 'medium-slug';

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertMatchesRegularExpression(
			'/<button[^>]*value="medium-slug"[^>]*aria-checked="true"[^>]*>/',
			$markup,
			'The "medium" size option should be checked when set in the URL parameters.'
		);

		unset( $_GET['attribute_pa_size'] );
	}

	/**
	 * Tests that the Product Price block is only interactive when some variations have different prices.
	 */
	public function test_variable_product_price_interactivity() {
		global $product;

		$fixtures = new FixtureData();

		$product = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'green', 'blue' ) ),
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);

		$product_id = $product->get_id();

		$fixtures->get_variation_product(
			$product_id,
			array(
				'pa_color' => 'red-slug',
				'pa_size'  => 'small-slug',
			),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		// Sync the variable product to update its children list.
		\WC_Product_Variable::sync( $product_id );

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-price /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		// Assert that Product Price block doesn't have a `data-wp-watch` attribute.
		$this->assertDoesNotMatchRegularExpression(
			'/<div[^>]*class="wc-block-components-product-price[^>]*data-wp-watch=[^>]*>/',
			$markup,
			'The Product Price block should not be interactive when all variations have the same price.'
		);

		$fixtures->get_variation_product(
			$product_id,
			array(
				'pa_color' => 'red-slug',
				'pa_size'  => 'medium-slug',
			),
			array(
				'regular_price' => 15,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		// Sync again so the variable product reflects the new variation.
		\WC_Product_Variable::sync( $product_id );

		// Assert that Product Price block has a `data-wp-watch` attribute.
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-price /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertMatchesRegularExpression(
			'/<div[^>]*class="wc-block-components-product-price[^>]*data-wp-watch=[^>]*>/',
			$markup,
			'The Product Price block should be interactive when some variations have different prices.'
		);
	}

	/**
	 * Tests that the stepper buttons render with correct aria labels when the product name contains a dollar sign.
	 */
	public function test_stepper_renders_correctly_with_dollar_sign_in_product_name() {
		$simple_product = new \WC_Product_Simple();
		$simple_product->set_regular_price( 10 );
		$simple_product->set_name( 'CANADA, $1' );
		$simple_product->set_manage_stock( true );
		$simple_product->set_stock_quantity( 10 );
		$simple_product_id = $simple_product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $simple_product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wc-block-components-quantity-selector__button--minus', $markup, 'The minus stepper button is rendered.' );
		$this->assertStringContainsString( 'wc-block-components-quantity-selector__button--plus', $markup, 'The plus stepper button is rendered.' );
		$this->assertStringContainsString( 'Reduce quantity of CANADA, $1', $markup, 'The minus button aria-label contains the full product name with dollar sign.' );
		$this->assertStringContainsString( 'Increase quantity of CANADA, $1', $markup, 'The plus button aria-label contains the full product name with dollar sign.' );

		// Verify $1 was not interpreted as a backreference (which would inject the captured <input> HTML into the aria-label).
		$this->assertDoesNotMatchRegularExpression(
			'/aria-label="[^"]*<input[^"]*"/',
			$markup,
			'The aria-label should not contain HTML from backreference expansion.'
		);
	}

	/**
	 * Tests that the stepper buttons render in visual DOM order (− input +),
	 * so keyboard focus and screen-reader reading order are logical.
	 */
	public function test_stepper_buttons_render_in_visual_dom_order() {
		$simple_product = new \WC_Product_Simple();
		$simple_product->set_regular_price( 10 );
		$simple_product->set_manage_stock( true );
		$simple_product->set_stock_quantity( 10 );
		$product_id = $simple_product->save();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		// The minus button must precede the quantity input, which must precede the plus button.
		$this->assertMatchesRegularExpression(
			'/quantity-selector__button--minus.*id="quantity_.*quantity-selector__button--plus/s',
			$markup,
			'Stepper buttons should render in − input + DOM order.'
		);
	}

	/**
	 * Tests that the quantity selector and its steppers are hidden when
	 * a filter sets min and max quantity to the same value for a product.
	 */
	public function test_quantity_selector_hidden_when_min_equals_max() {
		$simple_product = new \WC_Product_Simple();
		$simple_product->set_regular_price( 10 );
		$product_id = $simple_product->save();

		// Force min and max quantity to be the same via filter for this product only.
		$filter = function ( $args, $product ) use ( $product_id ) {
			if ( $product instanceof \WC_Product && $product->get_id() === $product_id ) {
				$args['min_value'] = 3;
				$args['max_value'] = 3;
			}
			return $args;
		};

		add_filter( 'woocommerce_quantity_input_args', $filter, 10, 2 );

		try {
			$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

			// Quantity selector block should not render at all.
			$this->assertStringContainsString( 'wc-block-add-to-cart-with-options__quantity-selector--hidden', $markup, 'The Quantity Selector block is hidden when min equals max.' );

			// Plus and minus stepper buttons should not be present.
			$this->assertStringNotContainsString( 'wc-block-components-quantity-selector__button--plus', $markup, 'The plus stepper is not rendered when min equals max.' );
			$this->assertStringNotContainsString( 'wc-block-components-quantity-selector__button--minus', $markup, 'The minus stepper is not rendered when min equals max.' );
		} finally {
			remove_filter( 'woocommerce_quantity_input_args', $filter, 10 );
		}
	}

	/**
	 * Tests that the Quantity Selector block outputs configured border radius styles.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\QuantitySelector::render
	 */
	public function test_quantity_selector_renders_border_radius_styles() {
		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		$linked_markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options-quantity-selector {"style":{"border":{"radius":"12px"}}} /--><!-- /wp:woocommerce/single-product -->'
		);

		$this->assertStringContainsString(
			'border-radius:12px',
			$linked_markup,
			'The quantity selector wrapper includes the configured border radius.'
		);

		$unlinked_markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options-quantity-selector {"style":{"border":{"radius":{"topLeft":"4px","topRight":"8px","bottomRight":"16px","bottomLeft":"2px"}}}} /--><!-- /wp:woocommerce/single-product -->'
		);

		foreach (
			array(
				'border-top-left-radius:4px',
				'border-top-right-radius:8px',
				'border-bottom-right-radius:16px',
				'border-bottom-left-radius:2px',
			) as $border_radius
		) {
			$this->assertStringContainsString(
				$border_radius,
				$unlinked_markup,
				"The quantity selector wrapper includes the configured {$border_radius}."
			);
		}
	}

	/**
	 * @testdox The Quantity Selector block binds its min/max/step attributes to the unified store's productScope.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\QuantitySelector::render
	 */
	public function test_quantity_selector_binds_min_max_step_to_the_unified_product_scope(): void {
		$product = \WC_Helper_Product::create_variation_product();
		\WC_Product_Variable::sync( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

		$markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><!-- wp:woocommerce/add-to-cart-with-options-quantity-selector /--><!-- /wp:woocommerce/single-product -->'
		);

		$this->assertStringContainsString( 'data-wp-bind--min="woocommerce::state.productScope.product.add_to_cart.minimum"', $markup, 'The min attribute should bind to the unified productScope.' );
		$this->assertStringContainsString( 'data-wp-bind--max="woocommerce::state.productScope.product.add_to_cart.maximum"', $markup, 'The max attribute should bind to the unified productScope.' );
		$this->assertStringContainsString( 'data-wp-bind--step="woocommerce::state.productScope.product.add_to_cart.multiple_of"', $markup, 'The step attribute should bind to the unified productScope.' );
		$this->assertStringNotContainsString( 'woocommerce/products', $markup, 'The old woocommerce/products namespace should not be referenced.' );
		$this->assertStringNotContainsString( 'productInContext', $markup, 'The old productInContext getter should not be referenced.' );
	}

	/**
	 * @testdox The Variation Description block binds its hidden attribute to the unified store's productScope.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationDescription::render
	 */
	public function test_variation_description_binds_to_the_unified_product_scope(): void {
		global $product;
		$previous_product = $product;
		$product          = \WC_Helper_Product::create_variation_product();

		$markup = do_blocks( '<!-- wp:woocommerce/add-to-cart-with-options-variation-description /-->' );

		$product = $previous_product;

		$this->assertStringContainsString( 'data-wp-bind--hidden="woocommerce::!state.productScope.productVariation.description"', $markup, 'The hidden attribute should bind to the unified productScope.' );
		$this->assertStringNotContainsString( 'woocommerce/products', $markup, 'The old woocommerce/products namespace should not be referenced.' );
		$this->assertStringNotContainsString( 'productVariationInContext', $markup, 'The old productVariationInContext getter should not be referenced.' );
	}

	/**
	 * @testdox The hidden variation_id input binds to the resolved variation's id through the unified store's productScope.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\AddToCartWithOptions::render
	 */
	public function test_hidden_variation_id_input_binds_to_the_unified_product_scope(): void {
		global $product;
		$previous_product = $product;
		$product          = \WC_Helper_Product::create_variation_product();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$product = $previous_product;

		$this->assertStringContainsString( 'data-wp-bind--value="woocommerce::state.productScope.productVariation.id"', $markup, 'The hidden variation_id input should bind to the resolved variation through the unified productScope.' );
		$this->assertStringNotContainsString( 'woocommerce/products', $markup, 'The old woocommerce/products namespace should not be referenced.' );
		$this->assertStringNotContainsString( 'productVariationInContext', $markup, 'The old productVariationInContext getter should not be referenced.' );
	}

	/**
	 * Tests that add_quantity_stepper_classes adds wrapper and input classes to inputs.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils::add_quantity_stepper_classes
	 */
	public function test_add_quantity_stepper_classes() {
		$quantity_html = '<div class="quantity"><input type="number" class="input-text qty text" name="custom_name" value="1" /></div>';

		$result = Utils::add_quantity_stepper_classes( $quantity_html );

		$this->assertStringContainsString( 'wc-block-components-quantity-selector', $result, 'The quantity wrapper should receive the stepper wrapper class.' );
		$this->assertStringContainsString( 'wc-block-components-quantity-selector__input', $result, 'The input should receive the stepper input class.' );
		$this->assertStringContainsString( 'custom_name', $result, 'The original input name value should be preserved.' );
	}

	/**
	 * Tests that the Add to Wishlist Button is injected as the last child only
	 * when the `product_wishlist` feature flag is enabled.
	 *
	 * A lightweight stub stands in for the real `add-to-wishlist-button` block so
	 * the test isolates the ATCWO injection/gating logic (the button's own
	 * rendering is covered by AddToWishlistButtonTests).
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\AddToCartWithOptions::render
	 */
	public function test_add_to_wishlist_button_injection() {
		$marker   = 'wc-block-add-to-wishlist-button-stub';
		$registry = \WP_Block_Type_Registry::get_instance();
		$features = wc_get_container()->get( FeaturesController::class );
		$original = $features->feature_is_enabled( 'product_wishlist' );

		if ( $registry->is_registered( 'woocommerce/add-to-wishlist-button' ) ) {
			$registry->unregister( 'woocommerce/add-to-wishlist-button' );
		}
		register_block_type(
			'woocommerce/add-to-wishlist-button',
			array(
				'render_callback' => function () use ( $marker ) {
					return '<div class="' . $marker . '"></div>';
				},
			)
		);

		try {
			global $product;
			$product = new \WC_Product_Simple();
			$product->set_regular_price( 10 );
			$product_id = $product->save();
			$block      = '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->';

			// Feature on: the button is injected as the last child.
			$features->change_feature_enable( 'product_wishlist', true );
			$markup = do_blocks( $block );

			$this->assertStringContainsString( $marker, $markup, 'The Add to Wishlist Button is injected when the wishlist feature is enabled.' );
			// Confirm the product button is present first, so both strpos() calls
			// below return integers and the position comparison is meaningful.
			$this->assertStringContainsString( 'wp-block-woocommerce-product-button', $markup, 'The product button is rendered.' );
			$this->assertGreaterThan(
				strpos( $markup, 'wp-block-woocommerce-product-button' ),
				strpos( $markup, $marker ),
				'The Add to Wishlist Button is injected after the product button (as the last child).'
			);

			// Feature off: the button is not injected.
			$features->change_feature_enable( 'product_wishlist', false );
			$markup = do_blocks( $block );
			$this->assertStringNotContainsString( $marker, $markup, 'The Add to Wishlist Button is not injected when the wishlist feature is disabled.' );
		} finally {
			$registry->unregister( 'woocommerce/add-to-wishlist-button' );
			$features->change_feature_enable( 'product_wishlist', $original );
		}
	}

	/**
	 * Extract, for every `<form>` in document order, whether it declares its
	 * own `woocommerce` scope context and, when it does, that context decoded.
	 *
	 * @param string $markup Rendered markup.
	 * @return array<int, array{declares_scope: bool, context: ?array}>
	 */
	private function get_form_context_declarations( string $markup ): array {
		$processor = new \WP_HTML_Tag_Processor( $markup );
		$forms     = array();

		while ( $processor->next_tag( 'form' ) ) {
			$context        = $processor->get_attribute( 'data-wp-context' );
			$declares_scope = false;
			$decoded        = null;

			if ( is_string( $context ) ) {
				if ( 0 === strpos( $context, 'woocommerce::' ) ) {
					$declares_scope = true;
					$decoded        = json_decode( substr( $context, strlen( 'woocommerce::' ) ), true );
				} else {
					$decoded = json_decode( $context, true );
				}
			}

			$forms[] = array(
				'declares_scope' => $declares_scope,
				'context'        => $decoded,
			);
		}

		return $forms;
	}

	/**
	 * Parse markup into a DOMXPath, so a test can query ancestor/descendant
	 * relationships between elements that `WP_HTML_Tag_Processor` — a flat,
	 * one-tag-at-a-time scanner — cannot answer.
	 *
	 * @param string $markup Rendered markup.
	 * @return \DOMXPath The parsed document, ready to query.
	 */
	private function get_xpath( string $markup ): \DOMXPath {
		$document                = new \DOMDocument();
		$previous_libxml_setting = libxml_use_internal_errors( true );
		$document->loadHTML( '<!DOCTYPE html><html><body>' . $markup . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_setting );

		return new \DOMXPath( $document );
	}

	/**
	 * Build hand-picked Product Collection markup showing a single product, embedding the given inner block markup
	 * inside its Product Template.
	 *
	 * @param int    $product_id  Product ID to hand-pick.
	 * @param string $inner_block Inner block markup rendered for the tile.
	 * @param int    $query_id    The collection's queryId, so two collections on one page are distinguishable.
	 * @return string Product Collection block markup.
	 */
	private function get_product_collection_markup_with_inner_block( int $product_id, string $inner_block, int $query_id = 0 ): string {
		$attributes = array(
			'queryId'    => $query_id,
			'query'      => array(
				'perPage'                       => 1,
				'pages'                         => 1,
				'offset'                        => 0,
				'postType'                      => 'product',
				'order'                         => 'asc',
				'orderBy'                       => 'post__in',
				'search'                        => '',
				'exclude'                       => array(),
				'inherit'                       => false,
				'taxQuery'                      => array(),
				'isProductCollectionBlock'      => true,
				'featured'                      => false,
				'woocommerceOnSale'             => false,
				'woocommerceStockStatus'        => array( 'instock' ),
				'woocommerceAttributes'         => array(),
				'woocommerceHandPickedProducts' => array( $product_id ),
				'filterable'                    => false,
			),
			'collection' => 'woocommerce/product-collection/hand-picked',
		);

		return sprintf(
			'<!-- wp:woocommerce/product-collection %1$s -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
%2$s
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->',
			wp_json_encode( $attributes ),
			$inner_block
		);
	}

	/**
	 * @testdox The first form for a product in a place declares no scope; a second form for the same product in the same place declares its own.
	 */
	public function test_first_form_declares_nothing_second_declares_own_scope(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$product_id
				)
			);

			$forms = $this->get_form_context_declarations( $markup );

			$this->assertCount( 2, $forms );
			$this->assertFalse( $forms[0]['declares_scope'], 'The first form in a place should declare no scope of its own.' );
			$this->assertTrue( $forms[1]['declares_scope'], 'A second form for the same product in the same place should declare its own scope.' );
			$this->assertSame( $product_id, $forms[1]['context']['productId'] );
			$this->assertIsString( $forms[1]['context']['scopeName'] );
		} finally {
			$product->delete( true );
		}
	}

	/**
	 * @testdox A declaring form's own context wraps the <form> as an ancestor, declaring its own data-wp-interactive; a non-declaring form gets no such wrapping element.
	 */
	public function test_declaring_forms_own_context_wraps_the_form_not_nested_inside_it(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$product_id
				)
			);

			$xpath = $this->get_xpath( $markup );
			$forms = $xpath->query( '//form' );
			$this->assertSame( 2, $forms->length );

			$first_form  = $forms->item( 0 );
			$second_form = $forms->item( 1 );

			// The first form declares nothing of its own: its own context
			// stays on the <form> element itself, and no element wraps it.
			$this->assertSame(
				0,
				$xpath->query( 'ancestor::*[@data-wp-interactive="woocommerce/add-to-cart-with-options"]', $first_form )->length,
				'A form that declares no scope of its own should render no wrapping element around it.'
			);
			$first_form_context = json_decode( $first_form->getAttribute( 'data-wp-context' ), true );
			$this->assertArrayHasKey( 'initialQuantity', $first_form_context, "The first form's own context should stay directly on its <form> element." );

			// The second form declares its own scope directly on the <form>,
			// unchanged, while its own context (initialQuantity, etc.) moves
			// to a wrapping element that is an ancestor of the <form>.
			$this->assertStringStartsWith(
				'woocommerce::',
				$second_form->getAttribute( 'data-wp-context' ),
				"The declaring form's own <form> element should still carry its woocommerce:: scope declaration."
			);

			$wrapping_elements = $xpath->query( 'ancestor::*[@data-wp-interactive="woocommerce/add-to-cart-with-options"]', $second_form );
			$this->assertSame(
				1,
				$wrapping_elements->length,
				"The declaring form's own context element should be an ancestor of its <form>, declaring its own data-wp-interactive."
			);

			$wrapper  = $wrapping_elements->item( 0 );
			$tag_name = $wrapper->tagName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMElement defines this public property name.
			$this->assertNotSame( 'form', strtolower( $tag_name ), 'The wrapping element should not itself be the <form>.' );

			$wrapper_context = json_decode( $wrapper->getAttribute( 'data-wp-context' ), true );
			$this->assertIsArray( $wrapper_context );
			$this->assertArrayHasKey( 'initialQuantity', $wrapper_context );
			$this->assertArrayHasKey( 'validationErrors', $wrapper_context );
			$this->assertArrayHasKey( 'noticeIds', $wrapper_context );
			$this->assertArrayHasKey( 'outOfStockMessage', $wrapper_context );
			$this->assertArrayNotHasKey( 'productId', $wrapper_context, "The wrapping element's own context should not itself carry the scope declaration." );
		} finally {
			$product->delete( true );
		}
	}

	/**
	 * @testdox In legacy mode, where no notices region wraps the form and no interactive ancestor exists, a declaring form still renders the same wrapping element, with the same data-wp-interactive and context.
	 */
	public function test_declaring_forms_own_context_wraps_the_form_in_legacy_mode(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		$original_redirect = get_option( 'woocommerce_cart_redirect_after_add' );

		try {
			update_option( 'woocommerce_cart_redirect_after_add', 'yes' );

			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$product_id
				)
			);

			$this->assertStringNotContainsString( 'woocommerce/store-notices', $markup, 'Legacy mode renders no notices region.' );

			$xpath = $this->get_xpath( $markup );
			$forms = $xpath->query( '//form' );
			$this->assertSame( 2, $forms->length );

			$second_form = $forms->item( 1 );
			$this->assertStringStartsWith( 'woocommerce::', $second_form->getAttribute( 'data-wp-context' ) );

			$wrapping_elements = $xpath->query( 'ancestor::*[@data-wp-interactive="woocommerce/add-to-cart-with-options"]', $second_form );
			$this->assertSame(
				1,
				$wrapping_elements->length,
				'A legacy-mode declaring form should still render its own wrapping element: the Single Product Template framing gives it no interactive ancestor to inherit hydration or namespace from.'
			);

			$wrapper_context = json_decode( $wrapping_elements->item( 0 )->getAttribute( 'data-wp-context' ), true );
			$this->assertArrayHasKey( 'initialQuantity', $wrapper_context );
		} finally {
			update_option( 'woocommerce_cart_redirect_after_add', $original_redirect );
			$product->delete( true );
		}
	}

	/**
	 * @testdox A form in each of two Single Product blocks for the same product renders no scopeName on either.
	 */
	public function test_forms_in_two_single_product_blocks_for_same_product_declare_nothing(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		try {
			$block  = sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
				$product_id
			);
			$markup = do_blocks( $block . $block );

			$forms = $this->get_form_context_declarations( $markup );

			$this->assertCount( 2, $forms );
			$this->assertFalse( $forms[0]['declares_scope'] );
			$this->assertFalse( $forms[1]['declares_scope'] );
		} finally {
			$product->delete( true );
		}
	}

	/**
	 * @testdox A form in each of two collection tiles for the same product renders no scopeName on either.
	 */
	public function test_forms_in_two_collection_tiles_for_same_product_declare_nothing(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		try {
			$inner_block = '<!-- wp:woocommerce/single-product --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->';

			$markup = do_blocks(
				$this->get_product_collection_markup_with_inner_block( $product_id, $inner_block, 0 )
				. $this->get_product_collection_markup_with_inner_block( $product_id, $inner_block, 1 )
			);

			$forms = $this->get_form_context_declarations( $markup );

			$this->assertCount( 2, $forms );
			$this->assertFalse( $forms[0]['declares_scope'] );
			$this->assertFalse( $forms[1]['declares_scope'] );
		} finally {
			$product->delete( true );
		}
	}

	/**
	 * @testdox A template form rendered beside a Single Product block for the page's product declares no scope on either.
	 */
	public function test_template_form_beside_single_product_block_declares_nothing_on_either(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		try {
			$this->go_to( get_permalink( $product_id ) );

			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$product_id
				)
			);

			$forms = $this->get_form_context_declarations( $markup );

			$this->assertCount( 2, $forms );
			$this->assertFalse( $forms[0]['declares_scope'], 'The template-level form should declare no scope of its own.' );
			$this->assertFalse( $forms[1]['declares_scope'], 'The form inside the Single Product block should declare no scope of its own.' );
		} finally {
			$product->delete( true );
		}
	}

	/**
	 * @testdox No rendered form carries a selectedAttributes context key, and a variation product's attributes appear as the declared scope's variation.
	 */
	public function test_no_selected_attributes_key_and_variation_appears_in_declared_scope(): void {
		$product = new \WC_Product_Variable();
		$product->set_name( 'Variation Scope Product' );
		$product->set_attributes(
			array( \WC_Helper_Product::create_product_attribute_object( 'color', array( 'blue' ) ) )
		);
		$product->save();

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'pa_color' => 'blue' ) );
		$variation->set_regular_price( '10' );
		$variation->save();
		\WC_Product_Variable::sync( $product->get_id() );

		try {
			// Two forms directly for the variation in the same place, so the second declares its own scope.
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$variation->get_id()
				)
			);

			$this->assertStringNotContainsString( 'selectedAttributes', $markup, 'No rendered form should carry a selectedAttributes context key.' );

			$forms = $this->get_form_context_declarations( $markup );

			$this->assertCount( 2, $forms );
			$this->assertTrue( $forms[1]['declares_scope'] );
			$this->assertSame(
				array(
					array(
						'attribute' => 'attribute_pa_color',
						'value'     => 'blue',
					),
				),
				$forms[1]['context']['variation']
			);
		} finally {
			$variation->delete( true );
			$product->delete( true );
		}
	}

	/**
	 * @testdox A Single Product block wrapper declares the same variation attributes a second form for the same product declares on its own form element.
	 */
	public function test_wrapper_variation_matches_a_second_forms_declared_variation(): void {
		$product = new \WC_Product_Variable();
		$product->set_name( 'Wrapper Variation Scope Product' );
		$product->set_attributes(
			array( \WC_Helper_Product::create_product_attribute_object( 'color', array( 'blue' ) ) )
		);
		$product->save();

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'pa_color' => 'blue' ) );
		$variation->set_regular_price( '10' );
		$variation->save();
		\WC_Product_Variable::sync( $product->get_id() );

		try {
			// Two forms directly for the variation in the same place, so the second declares its own scope.
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><div class="wp-block-woocommerce-single-product"><!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/add-to-cart-with-options /--></div><!-- /wp:woocommerce/single-product -->',
					$variation->get_id()
				)
			);

			$forms = $this->get_form_context_declarations( $markup );
			$this->assertCount( 2, $forms );
			$this->assertTrue( $forms[1]['declares_scope'] );

			$processor = new \WP_HTML_Tag_Processor( $markup );
			$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-single-product' ) ) );
			list( , $wrapper_json ) = explode( '::', $processor->get_attribute( 'data-wp-context' ), 2 );
			$wrapper_context        = json_decode( $wrapper_json, true );

			$this->assertNotSame( array(), $wrapper_context['variation'], 'A variation product should declare a non-empty variation.' );
			$this->assertSame(
				$forms[1]['context']['variation'],
				$wrapper_context['variation'],
				"The wrapper's variation should equal the one a second form for the same product declares on its own form element."
			);
		} finally {
			$variation->delete( true );
			$product->delete( true );
		}
	}

	/**
	 * @testdox A single form for a variation inside a Single Product block still declares no scope of its own.
	 */
	public function test_single_form_for_a_variation_declares_nothing(): void {
		$product       = \WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					current( $variation_ids )
				)
			);

			$forms = $this->get_form_context_declarations( $markup );

			$this->assertCount( 1, $forms );
			$this->assertFalse( $forms[0]['declares_scope'], 'The only form for a variation in a place should declare no scope of its own.' );
		} finally {
			\WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox A grouped form's own context carries groupedScopeNames listing its children's scope names, in groupedProductIds order, matching each row's own scopeName.
	 */
	public function test_grouped_form_context_carries_grouped_scope_names_matching_child_rows(): void {
		$child_a = new \WC_Product_Simple();
		$child_a->set_regular_price( 10 );
		$child_a_id = $child_a->save();

		$child_b = new \WC_Product_Simple();
		$child_b->set_regular_price( 20 );
		$child_b_id = $child_b->save();

		$grouped = new \WC_Product_Grouped();
		$grouped->set_children( array( $child_a_id, $child_b_id ) );
		$grouped_id = $grouped->save();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$grouped_id
				)
			);

			$forms = $this->get_form_context_declarations( $markup );
			$this->assertCount( 1, $forms );
			$this->assertArrayHasKey( 'groupedScopeNames', $forms[0]['context'] );
			$this->assertArrayNotHasKey( 'selectedAttributes', $forms[0]['context'] );

			// Collect each child row's own scopeName from the `woocommerce` context on its row wrapper.
			$processor              = new \WP_HTML_Tag_Processor( $markup );
			$scope_name_by_child_id = array();
			while ( $processor->next_tag() ) {
				$context = $processor->get_attribute( 'data-wp-context' );
				if ( ! is_string( $context ) || 0 !== strpos( $context, 'woocommerce::' ) ) {
					continue;
				}
				$decoded = json_decode( substr( $context, strlen( 'woocommerce::' ) ), true );
				if ( in_array( $decoded['productId'], array( $child_a_id, $child_b_id ), true ) ) {
					$scope_name_by_child_id[ $decoded['productId'] ] = $decoded['scopeName'];
				}
			}

			$this->assertCount( 2, $scope_name_by_child_id );
			$this->assertNotSame( $scope_name_by_child_id[ $child_a_id ], $scope_name_by_child_id[ $child_b_id ] );

			$expected_scope_names = array_map(
				function ( $child_id ) use ( $scope_name_by_child_id ) {
					return $scope_name_by_child_id[ $child_id ];
				},
				$forms[0]['context']['groupedProductIds']
			);

			$this->assertSame( $expected_scope_names, $forms[0]['context']['groupedScopeNames'] );
		} finally {
			$grouped->delete( true );
			$child_a->delete( true );
			$child_b->delete( true );
		}
	}

	/**
	 * @testdox A declaring grouped form's own context, now on its wrapping element, still carries groupedProductIds and groupedScopeNames matching each of its own child rows' scopeName.
	 */
	public function test_declaring_grouped_form_wrapper_carries_grouped_scope_names_matching_its_own_child_rows(): void {
		$child_a = new \WC_Product_Simple();
		$child_a->set_regular_price( 10 );
		$child_a_id = $child_a->save();

		$child_b = new \WC_Product_Simple();
		$child_b->set_regular_price( 20 );
		$child_b_id = $child_b->save();

		$grouped = new \WC_Product_Grouped();
		$grouped->set_children( array( $child_a_id, $child_b_id ) );
		$grouped_id = $grouped->save();

		try {
			// Two forms for the grouped product in the same place, so the second declares its own scope.
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$grouped_id
				)
			);

			$xpath = $this->get_xpath( $markup );
			$forms = $xpath->query( '//form' );
			$this->assertSame( 2, $forms->length );
			$second_form = $forms->item( 1 );

			$wrapping_elements = $xpath->query( 'ancestor::*[@data-wp-interactive="woocommerce/add-to-cart-with-options"]', $second_form );
			$this->assertSame( 1, $wrapping_elements->length );

			$wrapper_context = json_decode( $wrapping_elements->item( 0 )->getAttribute( 'data-wp-context' ), true );
			$this->assertArrayHasKey( 'groupedScopeNames', $wrapper_context );
			$this->assertArrayNotHasKey( 'selectedAttributes', $wrapper_context );

			// Collect the scopeName each child row inside this second form declares its own.
			$child_rows             = $xpath->query( './/*[starts-with(@data-wp-context, "woocommerce::")]', $second_form );
			$scope_name_by_child_id = array();
			foreach ( $child_rows as $child_row ) {
				$decoded = json_decode( substr( $child_row->getAttribute( 'data-wp-context' ), strlen( 'woocommerce::' ) ), true );
				if ( in_array( $decoded['productId'], array( $child_a_id, $child_b_id ), true ) ) {
					$scope_name_by_child_id[ $decoded['productId'] ] = $decoded['scopeName'];
				}
			}

			$this->assertCount( 2, $scope_name_by_child_id, "This form's own two child rows should each declare a scopeName." );

			$expected_scope_names = array_map(
				function ( $child_id ) use ( $scope_name_by_child_id ) {
					return $scope_name_by_child_id[ $child_id ];
				},
				$wrapper_context['groupedProductIds']
			);

			$this->assertSame( $expected_scope_names, $wrapper_context['groupedScopeNames'] );
		} finally {
			$grouped->delete( true );
			$child_a->delete( true );
			$child_b->delete( true );
		}
	}

	/**
	 * @testdox Two grouped products sharing one child render two different scopeName values for that child.
	 */
	public function test_two_grouped_products_sharing_one_child_get_different_scope_names(): void {
		$shared_child = new \WC_Product_Simple();
		$shared_child->set_regular_price( 10 );
		$shared_child_id = $shared_child->save();

		$grouped_a = new \WC_Product_Grouped();
		$grouped_a->set_children( array( $shared_child_id ) );
		$grouped_a_id = $grouped_a->save();

		$grouped_b = new \WC_Product_Grouped();
		$grouped_b->set_children( array( $shared_child_id ) );
		$grouped_b_id = $grouped_b->save();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
					<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$grouped_a_id,
					$grouped_b_id
				)
			);

			$processor   = new \WP_HTML_Tag_Processor( $markup );
			$scope_names = array();
			while ( $processor->next_tag() ) {
				$context = $processor->get_attribute( 'data-wp-context' );
				if ( ! is_string( $context ) || 0 !== strpos( $context, 'woocommerce::' ) ) {
					continue;
				}
				$decoded = json_decode( substr( $context, strlen( 'woocommerce::' ) ), true );
				if ( $shared_child_id === $decoded['productId'] ) {
					$scope_names[] = $decoded['scopeName'];
				}
			}

			$this->assertCount( 2, $scope_names, 'The shared child should render one row per grouped product.' );
			$this->assertNotSame( $scope_names[0], $scope_names[1], 'The shared child should get a different scopeName in each grouped product.' );
		} finally {
			$grouped_a->delete( true );
			$grouped_b->delete( true );
			$shared_child->delete( true );
		}
	}

	/**
	 * @testdox Rendering the same page twice, resetting the scope helper between passes, yields the same scopeName values in the same places.
	 */
	public function test_rendering_same_page_twice_yields_same_scope_names(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		try {
			$block = sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
				$product_id
			);

			$first_pass = $this->get_form_context_declarations( do_blocks( $block ) );
			ProductScopes::reset();
			$second_pass = $this->get_form_context_declarations( do_blocks( $block ) );

			$this->assertSame( $first_pass[1]['context']['scopeName'], $second_pass[1]['context']['scopeName'] );
		} finally {
			$product->delete( true );
		}
	}

	/**
	 * Collect every `id` attribute value in document order, duplicates included.
	 *
	 * @param string $markup Rendered markup.
	 * @return string[] Every `id` attribute value found.
	 */
	private function get_all_ids( string $markup ): array {
		$processor = new \WP_HTML_Tag_Processor( $markup );
		$ids       = array();

		while ( $processor->next_tag() ) {
			$id = $processor->get_attribute( 'id' );
			if ( is_string( $id ) && '' !== $id ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Get the resolved `value` attribute of the first quantity input (class
	 * `qty`) found in a fragment, after `wp_interactivity_process_directives()`
	 * has resolved its `data-wp-bind--value` directive.
	 *
	 * @param string $fragment A processed HTML fragment.
	 * @return string|null The input's `value` attribute, or null when no quantity input is found.
	 */
	private function get_quantity_input_value( string $fragment ): ?string {
		$processor = new \WP_HTML_Tag_Processor( $fragment );

		while ( $processor->next_tag( 'input' ) ) {
			if ( $processor->has_class( 'qty' ) ) {
				return $processor->get_attribute( 'value' );
			}
		}

		return null;
	}

	/**
	 * Split markup into one fragment per top-level `<form>...</form>`, since
	 * Add to Cart with Options forms never nest one inside another.
	 *
	 * @param string $markup Rendered markup.
	 * @return string[] One HTML fragment per form, in document order.
	 */
	private function extract_form_fragments( string $markup ): array {
		preg_match_all( '/<form\b.*?<\/form>/s', $markup, $matches );

		return $matches[0];
	}

	/**
	 * Whether a form fragment's own `<form>` tag carries the given class,
	 * as a class token rather than a raw substring: the fragment's
	 * `data-wp-class--is-invalid` directive attribute always contains the
	 * text "is-invalid", whether or not the class itself was ever added.
	 *
	 * @param string $form_fragment A single `<form>...</form>` fragment.
	 * @param string $class_name    The class to look for.
	 * @return bool Whether the form's own class attribute carries it.
	 */
	private function form_has_class( string $form_fragment, string $class_name ): bool {
		$processor = new \WP_HTML_Tag_Processor( $form_fragment );

		return $processor->next_tag( 'form' ) && $processor->has_class( $class_name );
	}

	/**
	 * Decode every `data-wp-context` JSON payload that carries an
	 * `initialQuantity` map, keyed by the product id that map names, so a
	 * test can look up the context belonging to a specific product's form
	 * regardless of whether that form declares its own `woocommerce` scope.
	 *
	 * @param string $markup Rendered markup.
	 * @return array<int, array> Decoded context, keyed by product id.
	 */
	private function get_add_to_cart_with_options_contexts( string $markup ): array {
		$processor = new \WP_HTML_Tag_Processor( $markup );
		$contexts  = array();

		while ( $processor->next_tag() ) {
			$context = $processor->get_attribute( 'data-wp-context' );
			if ( ! is_string( $context ) ) {
				continue;
			}

			$json    = 0 === strpos( $context, 'woocommerce::' ) ? substr( $context, strlen( 'woocommerce::' ) ) : $context;
			$decoded = json_decode( $json, true );

			if ( is_array( $decoded ) && isset( $decoded['initialQuantity'] ) && is_array( $decoded['initialQuantity'] ) ) {
				$product_id              = (int) array_key_first( $decoded['initialQuantity'] );
				$contexts[ $product_id ] = $decoded;
			}
		}

		return $contexts;
	}

	/**
	 * @testdox No `id` attribute value appears twice on a page with two forms, including two grouped products sharing one child.
	 */
	public function test_no_duplicate_ids_across_two_forms_including_shared_grouped_child(): void {
		$shared_child = new \WC_Product_Simple();
		$shared_child->set_regular_price( 10 );
		$shared_child_id = $shared_child->save();

		$grouped_a = new \WC_Product_Grouped();
		$grouped_a->set_children( array( $shared_child_id ) );
		$grouped_a_id = $grouped_a->save();

		$grouped_b = new \WC_Product_Grouped();
		$grouped_b->set_children( array( $shared_child_id ) );
		$grouped_b_id = $grouped_b->save();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
					<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$grouped_a_id,
					$grouped_b_id
				)
			);

			$ids = $this->get_all_ids( $markup );

			$this->assertSame( array_unique( $ids ), array_values( $ids ), 'No id attribute value should appear twice.' );
			$this->assertStringContainsString( 'name="quantity[' . $shared_child_id . ']"', $markup, 'The shared child keeps its product-id-based name attribute.' );
		} finally {
			$grouped_a->delete( true );
			$grouped_b->delete( true );
			$shared_child->delete( true );
		}
	}

	/**
	 * @testdox Every label for and every aria-labelledby inside a form resolves to an element inside that same form.
	 */
	public function test_labels_and_aria_labelledby_resolve_within_their_own_form(): void {
		$fixtures = new FixtureData();

		$product_a = $fixtures->get_variable_product(
			array( 'name' => 'Form Fragment A' ),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$fixtures->get_variation_product(
			$product_a->get_id(),
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		\WC_Product_Variable::sync( $product_a->get_id() );

		$product_b = $fixtures->get_variable_product(
			array( 'name' => 'Form Fragment B' ),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$fixtures->get_variation_product(
			$product_b->get_id(),
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		\WC_Product_Variable::sync( $product_b->get_id() );

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
					<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					wc_get_product( $product_a->get_id() )->get_id(),
					wc_get_product( $product_b->get_id() )->get_id()
				)
			);

			$forms = $this->extract_form_fragments( $markup );
			$this->assertCount( 2, $forms );

			foreach ( $forms as $index => $form_fragment ) {
				$processor   = new \WP_HTML_Tag_Processor( $form_fragment );
				$ids_in_form = array();
				while ( $processor->next_tag() ) {
					$id = $processor->get_attribute( 'id' );
					if ( is_string( $id ) && '' !== $id ) {
						$ids_in_form[] = $id;
					}
				}

				$processor = new \WP_HTML_Tag_Processor( $form_fragment );
				while ( $processor->next_tag() ) {
					$for = $processor->get_attribute( 'for' );
					if ( is_string( $for ) && '' !== $for ) {
						$this->assertContains( $for, $ids_in_form, "Form {$index}'s label for=\"{$for}\" should resolve inside the same form." );
					}

					$labelledby = $processor->get_attribute( 'aria-labelledby' );
					if ( is_string( $labelledby ) && '' !== $labelledby ) {
						$this->assertContains( $labelledby, $ids_in_form, "Form {$index}'s aria-labelledby=\"{$labelledby}\" should resolve inside the same form." );
					}
				}
			}
		} finally {
			$product_a->delete( true );
			$product_b->delete( true );
		}
	}

	/**
	 * @testdox Rendering a page with two forms twice, resetting the scope helper between passes, yields identical ids in the same order.
	 */
	public function test_rendering_two_forms_twice_yields_identical_ids(): void {
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		try {
			$block = sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
				$product_id
			);

			$first_pass_ids = $this->get_all_ids( do_blocks( $block ) );
			ProductScopes::reset();
			$second_pass_ids = $this->get_all_ids( do_blocks( $block ) );

			$this->assertSame( $first_pass_ids, $second_pass_ids );
		} finally {
			$product->delete( true );
		}
	}

	/**
	 * @testdox A simple and a variable product's initialQuantity map holds exactly what their quantity map held on the base branch, and no rendered form carries a quantity context key.
	 */
	public function test_initial_quantity_matches_base_branch_quantity_values_for_simple_and_variable_products(): void {
		$simple_product = new \WC_Product_Simple();
		$simple_product->set_regular_price( 10 );
		$simple_product_id = $simple_product->save();

		$filter = function ( $min, $product ) use ( $simple_product_id ) {
			return $product->get_id() === $simple_product_id ? 3 : $min;
		};
		add_filter( 'woocommerce_quantity_input_min', $filter, 10, 2 );

		$fixtures            = new FixtureData();
		$variable_product    = $fixtures->get_variable_product(
			array( 'name' => 'Initial Quantity Variable' ),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$variable_product_id = $variable_product->get_id();
		$variation_a         = $fixtures->get_variation_product(
			$variable_product_id,
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$variation_b         = $fixtures->get_variation_product(
			$variable_product_id,
			array( 'pa_color' => 'blue-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		\WC_Product_Variable::sync( $variable_product_id );
		$variable_product = wc_get_product( $variable_product_id );

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
					<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$simple_product_id,
					$variable_product_id
				)
			);

			$this->assertStringNotContainsString( '"quantity":', $markup, 'No rendered form should carry a quantity context key.' );

			$contexts = $this->get_add_to_cart_with_options_contexts( $markup );

			$this->assertSame( array( $simple_product_id => 3 ), $contexts[ $simple_product_id ]['initialQuantity'] );

			$variable_initial_quantity = $contexts[ $variable_product_id ]['initialQuantity'];
			$this->assertSame( $variable_product->get_min_purchase_quantity(), $variable_initial_quantity[ $variable_product_id ] );
			$this->assertSame( $variable_product->get_min_purchase_quantity(), $variable_initial_quantity[ $variation_a->get_id() ] );
			$this->assertSame( $variable_product->get_min_purchase_quantity(), $variable_initial_quantity[ $variation_b->get_id() ] );
		} finally {
			remove_filter( 'woocommerce_quantity_input_min', $filter, 10 );
			$simple_product->delete( true );
			$variable_product->delete( true );
		}
	}

	/**
	 * @testdox A grouped product's initialQuantity map holds, per child, 0, the resubmitted $_POST value, or 0 when sold individually, exactly as its quantity map held on the base branch.
	 */
	public function test_initial_quantity_matches_base_branch_quantity_values_for_grouped_product(): void {
		$child_untouched = new \WC_Product_Simple();
		$child_untouched->set_regular_price( 5 );
		$child_untouched_id = $child_untouched->save();

		$child_resubmitted = new \WC_Product_Simple();
		$child_resubmitted->set_regular_price( 5 );
		$child_resubmitted_id = $child_resubmitted->save();

		$child_sold_individually = new \WC_Product_Simple();
		$child_sold_individually->set_regular_price( 5 );
		$child_sold_individually->set_sold_individually( true );
		$child_sold_individually_id = $child_sold_individually->save();

		$grouped_product = new \WC_Product_Grouped();
		$grouped_product->set_children( array( $child_untouched_id, $child_resubmitted_id, $child_sold_individually_id ) );
		$grouped_product_id = $grouped_product->save();

		$_POST['quantity']                                = array();
		$_POST['quantity'][ $child_resubmitted_id ]       = '4';
		$_POST['quantity'][ $child_sold_individually_id ] = '2';

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$grouped_product_id
				)
			);

			$this->assertStringNotContainsString( '"quantity":', $markup, 'No rendered form should carry a quantity context key.' );

			$contexts                 = $this->get_add_to_cart_with_options_contexts( $markup );
			$grouped_initial_quantity = $contexts[ $child_untouched_id ]['initialQuantity'];

			$this->assertSame(
				array(
					$child_untouched_id         => 0,
					$child_resubmitted_id       => 4,
					$child_sold_individually_id => 0,
				),
				$grouped_initial_quantity
			);
		} finally {
			unset( $_POST['quantity'] );
			$grouped_product->delete( true );
			$child_untouched->delete( true );
			$child_resubmitted->delete( true );
			$child_sold_individually->delete( true );
		}
	}

	/**
	 * @testdox With two forms for two different products, one out of stock, the initial HTML shows each form's own quantity, its own validity, and the out-of-stock message only in the out-of-stock product's form, naming that product; none of these values are written into page-wide interactivity state or config.
	 */
	public function test_per_form_values_resolve_independently_and_are_not_seeded_page_wide(): void {
		$simple_product = new \WC_Product_Simple();
		$simple_product->set_name( 'Independent Simple Product' );
		$simple_product->set_regular_price( 10 );
		$simple_product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$simple_product_id = $simple_product->save();

		$fixtures            = new FixtureData();
		$variable_product    = $fixtures->get_variable_product(
			array( 'name' => 'Independent Variable Product' ),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$variable_product_id = $variable_product->get_id();
		$fixtures->get_variation_product(
			$variable_product_id,
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		\WC_Product_Variable::sync( $variable_product_id );
		$variable_product = wc_get_product( $variable_product_id );

		$min_quantities = array(
			$simple_product_id   => 2,
			$variable_product_id => 5,
		);
		$filter         = function ( $min, $product ) use ( $min_quantities ) {
			return $min_quantities[ $product->get_id() ] ?? $min;
		};
		add_filter( 'woocommerce_quantity_input_min', $filter, 10, 2 );

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
					<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$simple_product_id,
					$variable_product_id
				)
			);

			$contexts = $this->get_add_to_cart_with_options_contexts( $markup );

			$this->assertSame( array( $simple_product_id => 2 ), $contexts[ $simple_product_id ]['initialQuantity'] );
			$this->assertStringContainsString( 'Independent Simple Product', $contexts[ $simple_product_id ]['outOfStockMessage'] );
			$this->assertStringNotContainsString( 'Independent Variable Product', $contexts[ $simple_product_id ]['outOfStockMessage'] );

			$this->assertSame( 5, $contexts[ $variable_product_id ]['initialQuantity'][ $variable_product_id ] );
			$this->assertStringContainsString( 'Independent Variable Product', $contexts[ $variable_product_id ]['outOfStockMessage'] );
			$this->assertStringNotContainsString( 'Independent Simple Product', $contexts[ $variable_product_id ]['outOfStockMessage'] );

			$processed = wp_interactivity_process_directives( $markup );
			$forms     = $this->extract_form_fragments( $processed );

			$this->assertCount( 2, $forms );
			$this->assertFalse( $this->form_has_class( $forms[0], 'is-invalid' ), 'The simple product form should be valid.' );
			$this->assertTrue( $this->form_has_class( $forms[1], 'is-invalid' ), 'The variable product form should start invalid until a variation is selected.' );

			$config = wp_interactivity_config( 'woocommerce/add-to-cart-with-options' );
			$this->assertArrayNotHasKey( 'variableProductOutOfStock', $config['errorMessages'] ?? array(), 'The out-of-stock message should not be written into page-wide config.' );
			$this->assertArrayHasKey( 'invalidQuantities', $config['errorMessages'] ?? array(), 'The other error messages stay in page-wide config.' );

			$quantity_selector_state = wp_interactivity_state( 'woocommerce/add-to-cart-with-options-quantity-selector' );
			$this->assertInstanceOf( \Closure::class, $quantity_selector_state['inputQuantity'] ?? null, 'inputQuantity is a per-element derived getter, not a page-wide value.' );

			$this->assertStringNotContainsString( 'productScopes', $markup, 'No productScopes record should be seeded.' );
		} finally {
			remove_filter( 'woocommerce_quantity_input_min', $filter, 10 );
			$simple_product->delete( true );
			$variable_product->delete( true );
		}
	}

	/**
	 * @testdox Each of two forms' quantity inputs carries its own product's minimum purchase quantity as its resolved `value`, before any script runs.
	 */
	public function test_quantity_input_value_resolves_to_each_forms_own_minimum_purchase_quantity(): void {
		$product_a = new \WC_Product_Simple();
		$product_a->set_regular_price( 10 );
		$product_a_id = $product_a->save();

		$product_b = new \WC_Product_Simple();
		$product_b->set_regular_price( 10 );
		$product_b_id = $product_b->save();

		$min_quantities = array(
			$product_a_id => 1,
			$product_b_id => 4,
		);
		$filter         = function ( $min, $product ) use ( $min_quantities ) {
			return $min_quantities[ $product->get_id() ] ?? $min;
		};
		add_filter( 'woocommerce_quantity_input_min', $filter, 10, 2 );

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
					<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$product_a_id,
					$product_b_id
				)
			);

			$processed = wp_interactivity_process_directives( $markup );
			$forms     = $this->extract_form_fragments( $processed );

			$this->assertCount( 2, $forms );
			$this->assertSame( '1', $this->get_quantity_input_value( $forms[0] ), "The first form's quantity input should carry its own product's minimum." );
			$this->assertSame( '4', $this->get_quantity_input_value( $forms[1] ), "The second form's quantity input should carry its own product's minimum, not the first form's." );
		} finally {
			remove_filter( 'woocommerce_quantity_input_min', $filter, 10 );
			$product_a->delete( true );
			$product_b->delete( true );
		}
	}

	/**
	 * @testdox Rendering the same two-form page twice resolves the same quantity input values both times.
	 */
	public function test_quantity_input_value_is_stable_across_repeated_renders(): void {
		$product_a = new \WC_Product_Simple();
		$product_a->set_regular_price( 10 );
		$product_a_id = $product_a->save();

		$product_b = new \WC_Product_Simple();
		$product_b->set_regular_price( 10 );
		$product_b_id = $product_b->save();

		$min_quantities = array(
			$product_a_id => 2,
			$product_b_id => 5,
		);
		$filter         = function ( $min, $product ) use ( $min_quantities ) {
			return $min_quantities[ $product->get_id() ] ?? $min;
		};
		add_filter( 'woocommerce_quantity_input_min', $filter, 10, 2 );

		try {
			$block = sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
				<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
				$product_a_id,
				$product_b_id
			);

			$first_pass_forms = $this->extract_form_fragments( wp_interactivity_process_directives( do_blocks( $block ) ) );
			ProductScopes::reset();
			$second_pass_forms = $this->extract_form_fragments( wp_interactivity_process_directives( do_blocks( $block ) ) );

			$this->assertSame(
				array_map( array( $this, 'get_quantity_input_value' ), $first_pass_forms ),
				array_map( array( $this, 'get_quantity_input_value' ), $second_pass_forms )
			);
		} finally {
			remove_filter( 'woocommerce_quantity_input_min', $filter, 10 );
			$product_a->delete( true );
			$product_b->delete( true );
		}
	}

	/**
	 * @testdox A grouped child's quantity input resolves the value its own quantity-selector context holds — 0 for a child the shopper has not touched — never the sibling form's own product's minimum.
	 */
	public function test_grouped_child_quantity_input_value_resolves_from_its_own_context(): void {
		$sibling_product = new \WC_Product_Simple();
		$sibling_product->set_regular_price( 10 );
		$sibling_product_id = $sibling_product->save();

		add_filter(
			'woocommerce_quantity_input_min',
			$sibling_min_filter = function ( $min, $product ) use ( $sibling_product_id ) {
				return $product->get_id() === $sibling_product_id ? 6 : $min;
			},
			10,
			2
		);

		$child = new \WC_Product_Simple();
		$child->set_regular_price( 5 );
		$child_id = $child->save();

		$grouped_product = new \WC_Product_Grouped();
		$grouped_product->set_children( array( $child_id ) );
		$grouped_product_id = $grouped_product->save();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
					<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					$sibling_product_id,
					$grouped_product_id
				)
			);

			$processed = wp_interactivity_process_directives( $markup );
			$forms     = $this->extract_form_fragments( $processed );

			$this->assertCount( 2, $forms );
			$this->assertSame( '6', $this->get_quantity_input_value( $forms[0] ), "The sibling form's own quantity input keeps its own minimum." );
			$this->assertSame( '0', $this->get_quantity_input_value( $forms[1] ), 'The untouched grouped child resolves 0 from its own context, not the sibling form\'s minimum.' );
		} finally {
			remove_filter( 'woocommerce_quantity_input_min', $sibling_min_filter, 10 );
			$grouped_product->delete( true );
			$child->delete( true );
			$sibling_product->delete( true );
		}
	}

	/**
	 * @testdox Two forms for two different variable products sharing an attribute name render different attribute group ids, each with its own chip ids.
	 */
	public function test_two_variable_product_forms_render_different_attribute_and_chip_ids(): void {
		$fixtures = new FixtureData();

		$product_a = $fixtures->get_variable_product(
			array( 'name' => 'Attribute Scope A' ),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$fixtures->get_variation_product(
			$product_a->get_id(),
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		\WC_Product_Variable::sync( $product_a->get_id() );

		$product_b = $fixtures->get_variable_product(
			array( 'name' => 'Attribute Scope B' ),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$fixtures->get_variation_product(
			$product_b->get_id(),
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		\WC_Product_Variable::sync( $product_b->get_id() );

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%1$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->
					<!-- wp:woocommerce/single-product {"productId":%2$d} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->',
					wc_get_product( $product_a->get_id() )->get_id(),
					wc_get_product( $product_b->get_id() )->get_id()
				)
			);

			preg_match_all( '/id="(wc_product_attribute_[^"]*)_label"/', $markup, $attribute_id_matches );

			$this->assertCount( 2, $attribute_id_matches[1], 'Each form should render its own attribute group id.' );
			$this->assertNotSame( $attribute_id_matches[1][0], $attribute_id_matches[1][1], 'The two forms should render different attribute ids for the same attribute.' );

			foreach ( $attribute_id_matches[1] as $attribute_id ) {
				$this->assertStringContainsString( 'id="' . $attribute_id . '-', $markup, "Chips should be prefixed with the attribute id \"{$attribute_id}\"." );
			}

			$ids = $this->get_all_ids( $markup );
			$this->assertSame( array_unique( $ids ), array_values( $ids ), 'No id attribute value should appear twice.' );
		} finally {
			$product_a->delete( true );
			$product_b->delete( true );
		}
	}
}
