<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Utils\WC_Product_Custom;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsQuantitySelectorMock;
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

		if ( ! self::$are_blocks_registered ) {
			// We need to register the blocks after set up. They are no registered
			// on `init` because `init` is called with a classic theme.
			new AddToCartWithOptionsMock();
			new AddToCartWithOptionsQuantitySelectorMock();
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
	 * Registers a `render_block_context` filter at priority 1 that supplies
	 * `postId` (and, when given, `draftKey`) directly on the block context,
	 * mirroring the priority-1 filter that `ProductTemplate::render()` and
	 * `SingleProduct::update_context()` register for their descendants —
	 * without needing to wrap the test markup in either container block.
	 *
	 * @param int         $product_id Product ID to place on the context.
	 * @param string|null $draft_key  Draft key to place on the context, or null to leave it unset.
	 * @return callable The registered filter callback, so it can be removed later.
	 */
	private function force_product_and_draft_key_context( int $product_id, ?string $draft_key = null ): callable {
		$filter = static function ( $context ) use ( $product_id, $draft_key ) {
			$context['postId']   = $product_id;
			$context['postType'] = 'product';

			if ( null !== $draft_key ) {
				$context['draftKey'] = $draft_key;
			}

			return $context;
		};

		add_filter( 'render_block_context', $filter, 1 );

		return $filter;
	}

	/**
	 * Tests that a simple product's Add to Cart + Options form files its
	 * initial `add-item` payload into the `woocommerce/cart` state's
	 * `draftSeeds` collection, under the reserved global collection key when
	 * rendered outside any keyed purchase-surface container, so the client
	 * can seed the correct values without them ever appearing in a context
	 * bag.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\AddToCartWithOptions::render
	 */
	public function test_draft_seed_context_for_simple_product() {
		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		$filter = $this->force_product_and_draft_key_context( $product_id );

		try {
			$markup = do_blocks( '<!-- wp:woocommerce/add-to-cart-with-options /-->' );
		} finally {
			remove_filter( 'render_block_context', $filter, 1 );
		}

		$state        = wp_interactivity_state( 'woocommerce/cart' );
		$global_seeds = $state['draftSeeds']['woocommerce/global'] ?? array();

		$this->assertArrayHasKey( $product_id, $global_seeds, 'A form rendered outside any keyed container files its seed under the reserved global collection key.' );
		$this->assertSame(
			array(
				'id'       => $product_id,
				'quantity' => 1,
			),
			$global_seeds[ $product_id ],
			'The filed seed carries the product id and minimum purchase quantity.'
		);

		$this->assertStringNotContainsString( 'data-wp-context---draft-seed', $markup, 'The form no longer emits a draft-seed context bag.' );
		$this->assertStringNotContainsString( 'data-wp-init--seed-draft', $markup, 'The form no longer emits the seedDraftIfAbsent init trigger.' );
	}

	/**
	 * Tests that a surface rendered inside a keyed purchase-surface container
	 * (e.g. a Product Collection card or a Single Product block instance)
	 * files its seed under that container's draft key rather than the
	 * reserved global collection key.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\AddToCartWithOptions::render
	 */
	public function test_draft_seed_files_under_container_draft_key() {
		global $product;
		$product = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();
		$draft_key  = 'collection/0/' . $product_id;

		$filter = $this->force_product_and_draft_key_context( $product_id, $draft_key );

		try {
			do_blocks( '<!-- wp:woocommerce/add-to-cart-with-options /-->' );
		} finally {
			remove_filter( 'render_block_context', $filter, 1 );
		}

		$state = wp_interactivity_state( 'woocommerce/cart' );

		$this->assertArrayHasKey( $draft_key, $state['draftSeeds'] ?? array(), 'A surface inside a keyed container files its seed under that container\'s draft key.' );
		$this->assertSame(
			array(
				'id'       => $product_id,
				'quantity' => 1,
			),
			$state['draftSeeds'][ $draft_key ][ $product_id ]
		);
	}

	/**
	 * Tests that a Grouped product's own form does not file a draft seed for
	 * the grouped (unpurchasable) parent id, while each purchasable child's
	 * own quantity selector files its own seed, with the allowZero-adjusted
	 * quantity (0) matching the value actually bound to its input.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\AddToCartWithOptions::render
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils::make_quantity_input_interactive
	 */
	public function test_draft_seed_context_for_grouped_product() {
		$simple_product = new \WC_Product_Simple();
		$simple_product->set_regular_price( 10 );
		$simple_product_id = $simple_product->save();
		$grouped_product   = new \WC_Product_Grouped();
		$grouped_product->set_children( array( $simple_product_id ) );
		$grouped_product_id = $grouped_product->save();

		$filter = $this->force_product_and_draft_key_context( $grouped_product_id );

		try {
			do_blocks( '<!-- wp:woocommerce/add-to-cart-with-options /-->' );
		} finally {
			remove_filter( 'render_block_context', $filter, 1 );
		}

		$state        = wp_interactivity_state( 'woocommerce/cart' );
		$global_seeds = $state['draftSeeds']['woocommerce/global'] ?? array();

		$this->assertArrayNotHasKey( $grouped_product_id, $global_seeds, 'The grouped product parent does not file its own draft seed; only its children do.' );

		$this->assertArrayHasKey( $simple_product_id, $global_seeds, 'The grouped child quantity selector files its own draft seed.' );
		$this->assertSame(
			array(
				'id'       => $simple_product_id,
				'quantity' => 0,
			),
			$global_seeds[ $simple_product_id ],
			'The grouped child seed carries quantity 0 (an optional item), matching the value bound to its input.'
		);
	}

	/**
	 * Tests that a Variable product's form files a draft seed carrying no
	 * `variation` key until a default attribute selection exists, matching
	 * the parent-level `selectedAttributes` context, which also starts empty.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\AddToCartWithOptions::render
	 */
	public function test_draft_seed_context_for_variable_product_has_no_variation_key() {
		global $product;

		$fixtures = new FixtureData();

		$product = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'green' ) ),
			)
		);

		$product_id = $product->get_id();

		$fixtures->get_variation_product(
			$product_id,
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		\WC_Product_Variable::sync( $product_id );

		$filter = $this->force_product_and_draft_key_context( $product_id );

		try {
			do_blocks( '<!-- wp:woocommerce/add-to-cart-with-options /-->' );
		} finally {
			remove_filter( 'render_block_context', $filter, 1 );
		}

		$state        = wp_interactivity_state( 'woocommerce/cart' );
		$global_seeds = $state['draftSeeds']['woocommerce/global'] ?? array();

		$this->assertSame(
			array(
				'id'       => $product_id,
				'quantity' => $product->get_min_purchase_quantity(),
			),
			$global_seeds[ $product_id ],
			'The variable product form files a seed with the parent id and default quantity, carrying no "variation" key until an attribute is selected.'
		);
	}

	/**
	 * Calls `Utils::make_quantity_input_interactive()` with
	 * `WP_Block_Supports::$block_to_render` set up front, so
	 * `get_block_wrapper_attributes()` (called internally for layout/style
	 * supports) has the context it expects when invoked outside the usual
	 * block-render pipeline (mirrors the pattern in
	 * AddToWishlistButtonTests::invoke_render() /
	 * SavedForLaterTests::test_render_seeds_hidden_empty_state_for_new_shopper()).
	 *
	 * @param string      $quantity_html Quantity input HTML.
	 * @param array       $context       Optional context for the quantity input.
	 * @param string|null $draft_key     Optional `woocommerce/cart` collection key to file the seed under.
	 * @return string The quantity HTML with interactive wrapper.
	 */
	private function invoke_make_quantity_input_interactive( $quantity_html, $context = array(), $draft_key = null ) {
		$previous_block_to_render            = \WP_Block_Supports::$block_to_render;
		\WP_Block_Supports::$block_to_render = array(
			'blockName' => 'woocommerce/add-to-cart-with-options-quantity-selector',
			'attrs'     => array(),
		);

		try {
			return null === $draft_key
				? Utils::make_quantity_input_interactive( $quantity_html, array(), array(), $context )
				: Utils::make_quantity_input_interactive( $quantity_html, array(), array(), $context, false, $draft_key );
		} finally {
			\WP_Block_Supports::$block_to_render = $previous_block_to_render;
		}
	}

	/**
	 * Tests that `make_quantity_input_interactive` files a `woocommerce/cart`
	 * draft seed for the global product in scope under the reserved global
	 * collection key when no draft key is given.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils::make_quantity_input_interactive
	 */
	public function test_make_quantity_input_interactive_emits_draft_seed() {
		global $product;
		$previous_product = $product;
		$product          = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		$quantity_html = '<div class="quantity"><input type="number" name="quantity" value="1" /></div>';

		$result = $this->invoke_make_quantity_input_interactive( $quantity_html );

		$state        = wp_interactivity_state( 'woocommerce/cart' );
		$global_seeds = $state['draftSeeds']['woocommerce/global'] ?? array();

		$this->assertArrayHasKey( $product_id, $global_seeds, 'The quantity selector files a draft seed for the product in scope under the global collection key.' );
		$this->assertSame(
			array(
				'id'       => $product_id,
				'quantity' => 1,
			),
			$global_seeds[ $product_id ],
			'The filed seed carries the product id and quantity.'
		);

		$this->assertStringNotContainsString( 'data-wp-context---draft-seed', $result, 'The quantity selector no longer emits a draft-seed context bag.' );
		$this->assertStringNotContainsString( 'data-wp-init--seed-draft', $result, 'The quantity selector no longer emits the seedDraftIfAbsent init trigger.' );

		$product = $previous_product;
	}

	/**
	 * Tests that `make_quantity_input_interactive` files its draft seed under
	 * an explicitly-given collection key rather than the reserved global key.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils::make_quantity_input_interactive
	 */
	public function test_make_quantity_input_interactive_emits_draft_seed_under_given_key() {
		global $product;
		$previous_product = $product;
		$product          = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();
		$draft_key  = 'collection/0/' . $product_id;

		$quantity_html = '<div class="quantity"><input type="number" name="quantity" value="1" /></div>';

		$this->invoke_make_quantity_input_interactive( $quantity_html, array(), $draft_key );

		$state = wp_interactivity_state( 'woocommerce/cart' );

		$this->assertArrayHasKey( $draft_key, $state['draftSeeds'] ?? array(), 'The quantity selector files its draft seed under the given collection key.' );
		$this->assertSame(
			array(
				'id'       => $product_id,
				'quantity' => 1,
			),
			$state['draftSeeds'][ $draft_key ][ $product_id ]
		);

		$product = $previous_product;
	}

	/**
	 * Tests that `make_quantity_input_interactive` includes the variation's
	 * selected attributes in its draft seed when the global product in
	 * scope is a variation directly (e.g. a Single Product block referencing a
	 * variation id). This mirrors the `selectedAttributes` carried by the
	 * form-level draft seed for variations, so the client-side cart-line
	 * pairing ladder can match the resulting line by variation attributes, not
	 * just product id.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils::make_quantity_input_interactive
	 */
	public function test_make_quantity_input_interactive_draft_seed_includes_variation_attributes() {
		global $product;
		$previous_product = $product;

		$fixtures = new FixtureData();

		$variable_product = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'green' ) ),
			)
		);

		$variation = $fixtures->get_variation_product(
			$variable_product->get_id(),
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		$product = $variation;

		$quantity_html = '<div class="quantity"><input type="number" name="quantity" value="1" /></div>';

		$this->invoke_make_quantity_input_interactive( $quantity_html );

		$state        = wp_interactivity_state( 'woocommerce/cart' );
		$global_seeds = $state['draftSeeds']['woocommerce/global'] ?? array();

		$this->assertSame(
			array(
				'id'        => $variation->get_id(),
				'quantity'  => 1,
				'variation' => array(
					array(
						'attribute' => 'attribute_pa_color',
						'value'     => 'red-slug',
					),
				),
			),
			$global_seeds[ $variation->get_id() ],
			'The draft seed for a directly-referenced variation carries its variation attributes, matching the form-level selectedAttributes context.'
		);

		$product = $previous_product;
	}

	/**
	 * Tests that the draft seed's `quantity` matches the `allowZero`-adjusted
	 * value actually bound to the rendered input (an optional grouped-product
	 * child), not the product's raw minimum purchase quantity.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils::make_quantity_input_interactive
	 */
	public function test_make_quantity_input_interactive_draft_seed_quantity_respects_allow_zero() {
		global $product;
		$previous_product = $product;
		$product          = new \WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product_id = $product->save();

		$quantity_html = '<div class="quantity"><input type="number" name="quantity" value="1" /></div>';

		$this->invoke_make_quantity_input_interactive( $quantity_html, array( 'allowZero' => true ) );

		$state        = wp_interactivity_state( 'woocommerce/cart' );
		$global_seeds = $state['draftSeeds']['woocommerce/global'] ?? array();

		$this->assertSame(
			array(
				'id'       => $product_id,
				'quantity' => 0,
			),
			$global_seeds[ $product_id ],
			'The draft seed quantity matches the allowZero-adjusted bound value (0), not the product minimum.'
		);

		$product = $previous_product;
	}

	/**
	 * Tests that no draft seed is filed when there is no product in scope,
	 * since there is nothing to seed.
	 *
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils::make_quantity_input_interactive
	 */
	public function test_make_quantity_input_interactive_no_draft_seed_without_product() {
		global $product;
		$previous_product = $product;
		$product          = null;

		$state_before = wp_interactivity_state( 'woocommerce/cart' );

		$quantity_html = '<div class="quantity"><input type="number" name="quantity" value="1" /></div>';
		$result        = $this->invoke_make_quantity_input_interactive( $quantity_html );

		$state_after = wp_interactivity_state( 'woocommerce/cart' );

		$this->assertSame( $state_before, $state_after, 'No draft seed is filed when there is no product in scope.' );
		$this->assertStringNotContainsString( 'draft-seed', $result, 'No draft-seed context bag is emitted when there is no product in scope.' );

		$product = $previous_product;
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
}
