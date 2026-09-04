<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\ProductPageIntegration;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsQuantitySelectorMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationSelectorMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationSelectorAttributeMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationSelectorAttributeNameMock;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductPageIntegration class.
 */
class ProductPageIntegrationTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductPageIntegration
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// The blocks are not registered on `init` because `init` runs with a classic theme.
		// Other test classes may have registered them already.
		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/add-to-cart-with-options' ) ) {
			new AddToCartWithOptionsMock();
			new AddToCartWithOptionsQuantitySelectorMock();
			new AddToCartWithOptionsVariationSelectorMock();
			new AddToCartWithOptionsVariationSelectorAttributeMock();
			new AddToCartWithOptionsVariationSelectorAttributeNameMock();
		}

		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );

		$this->sut = wc_get_container()->get( ProductPageIntegration::class );
	}

	/**
	 * Create a variable product with an out of stock variation and load its single product page.
	 *
	 * @return int The product ID.
	 */
	private function create_and_visit_variable_product(): int {
		$fixtures         = new FixtureData();
		$variable_product = $fixtures->get_variable_product(
			array(),
			array( $fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ) )
		);
		$product_id       = $variable_product->get_id();

		// Keep one variation purchasable so the block fires its template hooks.
		$fixtures->get_variation_product(
			$product_id,
			array( 'pa_color' => 'red-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$fixtures->get_variation_product(
			$product_id,
			array( 'pa_color' => 'blue-slug' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
			)
		);

		// Sync the parent stock status from its variations so the block treats it as purchasable.
		\WC_Product_Variable::sync( $product_id );

		$this->go_to( get_permalink( $product_id ) );

		// `go_to()` overwrites the global with the query var, so set it again for the hook callbacks.
		$GLOBALS['product'] = wc_get_product( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return $product_id;
	}

	/**
	 * @testdox Should not render the form inside the Add to Cart + Options block so it keeps its Interactivity API form.
	 */
	public function test_form_is_not_rendered_inside_add_to_cart_with_options_block(): void {
		$product_id = $this->create_and_visit_variable_product();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/add-to-cart-with-options /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'data-wp-on--submit', $markup, 'The Add to Cart + Options block should keep its Interactivity API form when sign-ups are enabled.' );
		$this->assertStringNotContainsString( 'wc_bis_form', $markup, 'The Back in Stock form should not render inside the Add to Cart + Options block.' );
	}

	/**
	 * @testdox Should render the form when the classic template hook fires outside the Add to Cart + Options block.
	 */
	public function test_form_is_rendered_by_classic_template_hook(): void {
		$this->create_and_visit_variable_product();

		ob_start();
		do_action( 'woocommerce_after_add_to_cart_form' );
		$markup = ob_get_clean();

		$this->assertStringContainsString( 'wc_bis_form', $markup, 'The Back in Stock form should render when the hook fires from a classic template.' );
	}
}
