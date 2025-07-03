<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Mocks\MiniCartMock;

/**
 * Tests for the Checkout block type
 *
 * @since $VID:$
 */
class MiniCart extends \WP_UnitTestCase {

	/**
	 * Mock instance of the MiniCart block.
	 *
	 * @var MiniCartMock
	 */
	protected $mock;

	/**
	 * The original block type registry entry for the MiniCart block.
	 *
	 * @var \WP_Block_Type
	 */
	protected $original_block_type;

	/**
	 * Setup test product data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$registry = \WP_Block_Type_Registry::get_instance();

		$this->original_block_type = null;
		if ( $registry->is_registered( 'woocommerce/mini-cart' ) ) {
			$this->original_block_type = $registry->get_registered( 'woocommerce/mini-cart' );
			$registry->unregister( 'woocommerce/mini-cart' );
		}

		$this->mock = new MiniCartMock();

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

		$registry = \WP_Block_Type_Registry::get_instance();
		$registry->unregister( 'woocommerce/mini-cart' );
		if ( $this->original_block_type ) {
			$registry->register( $this->original_block_type );
		}
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

	/**
	 * Checks that process_template_contents returns the same string if there is nothing to replace.
	 *
	 * @return void
	 */
	public function test_process_template_contents_simple_string() {
		$this->assertEquals( 'Hello World', $this->mock->call_process_template_contents( 'Hello World' ) );
	}
}
