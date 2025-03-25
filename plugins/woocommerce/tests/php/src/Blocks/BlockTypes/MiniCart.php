<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Tests for the Checkout block type
 *
 * @since $VID:$
 */
class MiniCart extends \WP_UnitTestCase {

	/**
	 * Setup test product data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$fixtures       = new FixtureData();
		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
		);
		WC()->cart->empty_cart();
		add_filter( 'woocommerce_is_rest_api_request', '__return_false', 1 );
	}

	/**
	 * Tear down test. Called after every test.
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
		remove_filter( 'woocommerce_is_rest_api_request', '__return_false', 1 );
	}

	/**
	 * Checks the output of the MiniCart block is correct based on the productCountVisibility attribute when cart is empty.
	 * @return void
	 */
	public function test_product_count_visibility_with_empty_cart() {

		// Test badge is shown when "always" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"always"} /-->' );
		$output = render_block( $block[0] );
		$this->assertStringContainsString( '<span class="wc-block-mini-cart__badge"', $output );

		// Tests badge is not shown, because product count is not greater than zero when "greater_than_zero" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"greater_than_zero"} /-->' );
		$output = render_block( $block[0] );
		$this->assertStringContainsString( '<span class="wc-block-mini-cart__badge"', $output );

		// Tests badge is not shown when "never" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"never"} /-->' );
		$output = render_block( $block[0] );
		$this->assertStringNotContainsString( '<span class="wc-block-mini-cart__badge"', $output );
	}

	/**
	 * Checks the output of the MiniCart block is correct based on the productCountVisibility attribute when cart has products.
	 * @return void
	 */
	public function test_product_count_visibility_with_products_in_cart() {
		WC()->cart->add_to_cart( $this->products[0]->get_id(), 2 );

		// Tests badge is shown with items in cart when "always" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"always"} /-->' );
		$output = render_block( $block[0] );
		$this->assertStringContainsString( '<span class="wc-block-mini-cart__badge"', $output );

		// Tests badge *is* shown, because product count is greater than zero when "greater_than_zero" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"greater_than_zero"} /-->' );
		$output = render_block( $block[0] );
		$this->assertStringContainsString( '<span class="wc-block-mini-cart__badge"', $output );

		// Tests badge is not shown with items in cart when "never" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"never"} /-->' );
		$output = render_block( $block[0] );
		$this->assertStringNotContainsString( '<span class="wc-block-mini-cart__badge"', $output );
	}
}
