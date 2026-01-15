<?php
declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\MiniCart as MiniCartBlock;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
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
	 * The upcoming template for the Mini-Cart block.
	 *
	 * @var string
	 */
	private $upcoming_template = '
			<!-- wp:woocommerce/mini-cart-contents -->
				<!-- wp:woocommerce/filled-mini-cart-contents-block -->
					<!-- wp:woocommerce/mini-cart-title-block -->
						<!-- wp:woocommerce/mini-cart-title-label-block -->
					<!-- /wp:woocommerce/mini-cart-title-label-block -->
					<!-- wp:woocommerce/mini-cart-title-items-counter-block -->
					<!-- /wp:woocommerce/mini-cart-title-items-counter-block -->
				<!-- /wp:woocommerce/mini-cart-title-block -->
				<!-- wp:woocommerce/mini-cart-items-block -->
					<!-- wp:woocommerce/mini-cart-products-table-block -->
					<!-- /wp:woocommerce/mini-cart-products-table-block -->
				<!-- /wp:woocommerce/mini-cart-items-block -->
				<!-- wp:woocommerce/mini-cart-footer-block -->
					<!-- wp:woocommerce/mini-cart-cart-button-block -->
					<!-- /wp:woocommerce/mini-cart-cart-button-block -->
					<!-- wp:woocommerce/mini-cart-checkout-button-block -->
					<!-- /wp:woocommerce/mini-cart-checkout-button-block -->
				<!-- /wp:woocommerce/mini-cart-footer-block -->
			<!-- /wp:woocommerce/filled-mini-cart-contents-block -->
			<!-- wp:woocommerce/empty-mini-cart-contents-block -->
				<!-- wp:pattern {"slug":"woocommerce/mini-cart-empty-cart-message"} /-->
				<!-- wp:woocommerce/mini-cart-shopping-button-block -->
				<!-- /wp:woocommerce/mini-cart-shopping-button-block -->
			<!-- /wp:woocommerce/empty-mini-cart-contents-block -->
		<!-- /wp:woocommerce/mini-cart-contents -->';

	/**
	 * The current template for the Mini-Cart block.
	 *
	 * @var string
	 */
	private $current_template_with_user_edits = '
	<!-- wp:woocommerce/mini-cart-contents -->
		<div class="wp-block-woocommerce-mini-cart-contents">
			<!-- wp:woocommerce/filled-mini-cart-contents-block -->
			<div class="wp-block-woocommerce-filled-mini-cart-contents-block">
				<!-- wp:woocommerce/mini-cart-title-block -->
				<div class="wp-block-woocommerce-mini-cart-title-block">
					<!-- wp:woocommerce/mini-cart-title-label-block -->
					<div class="wp-block-woocommerce-mini-cart-title-label-block">
					</div>
					<!-- /wp:woocommerce/mini-cart-title-label-block -->

					<!-- wp:group -->
						<div class="wp-block-group">
							<!-- wp:woocommerce/mini-cart-title-items-counter-block -->
							<div class="wp-block-woocommerce-mini-cart-title-items-counter-block">
							</div>
							<!-- /wp:woocommerce/mini-cart-title-items-counter-block -->
						</div>
					<!-- /wp:group -->

					<!-- wp:image {"id":123} -->
						<img class="wp-image-block" src="https://example.com/image.jpg" alt="Example Image" />
					<!-- /wp:image -->
				</div>
			</div>
			<!-- /wp:woocommerce/filled-mini-cart-contents-block -->
		</div>
		<!-- /wp:woocommerce/mini-cart-contents -->

		<!-- wp:separator -->
			<hr class="wp-block-separator" />
		<!-- /wp:separator -->';


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
		$this->assertTrue( $this->has_mini_cart_badge( $output ) );

		// Tests badge is not shown, because product count is not greater than zero when "greater_than_zero" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"greater_than_zero"} /-->' );
		$output = render_block( $block[0] );
		$this->assertTrue( $this->has_mini_cart_badge( $output ) );

		// Tests badge is not shown when "never" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"never"} /-->' );
		$output = render_block( $block[0] );
		$this->assertFalse( $this->has_mini_cart_badge( $output ) );
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
		$this->assertTrue( $this->has_mini_cart_badge( $output ) );

		// Tests badge *is* shown, because product count is greater than zero when "greater_than_zero" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"greater_than_zero"} /-->' );
		$output = render_block( $block[0] );
		$this->assertTrue( $this->has_mini_cart_badge( $output ) );

		// Tests badge is not shown with items in cart when "never" is selected.
		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart {"productCountVisibility":"never"} /-->' );
		$output = render_block( $block[0] );
		$this->assertFalse( $this->has_mini_cart_badge( $output ) );
	}

	/**
	 * Checks that process_template_contents returns exactly the same string if
	 * a template without wrapper divs is used.
	 *
	 * Note: This test has to be replaced when the minicart template part is
	 * substituted by the new template part without wrapper divs.
	 *
	 * @return void
	 */
	public function test_process_template_contents_with_upcoming_template() {
		$this->assertEquals( $this->upcoming_template, $this->mock->call_process_template_contents( $this->upcoming_template ) );
	}

	/**
	 * Checks that process_template_contents removes the wrapper divs from the
	 * current template.
	 *
	 * Note: This test has to be replaced when the minicart template part is
	 * substituted by the new template part without wrapper divs.
	 *
	 * @return void
	 */
	public function test_process_template_contents_with_current_template() {
		$current_template   = file_get_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			Package::get_path() . 'templates/' . BlockTemplateUtils::DIRECTORY_NAMES['TEMPLATE_PARTS'] . '/mini-cart.html'
		);
		$processed_template = $this->mock->call_process_template_contents( $current_template );

		foreach ( MiniCartBlock::MINI_CART_TEMPLATE_BLOCKS as $block_name ) {
			$p                        = new \WP_HTML_Tag_Processor( $processed_template );
			$class_name               = 'wp-block-' . str_replace( '/', '-', $block_name );
			$div_wrapper_still_exists = $p->next_tag(
				array(
					'tag_name'   => 'div',
					'class_name' => $class_name,
				)
			);
			$this->assertFalse( $div_wrapper_still_exists, "The div wrapper with class {$class_name} should have been removed." );
		}
	}

	/**
	 * Checks that process_template_contents removes the wrapper divs from the
	 * current template with user edits, but preserves the user edits.
	 *
	 * Note: This test has to be replaced when the minicart template part is
	 * substituted by the new template part without wrapper divs.
	 *
	 * @return void
	 */
	public function test_process_template_contents_with_user_edits() {
		$processed_template = $this->mock->call_process_template_contents( $this->current_template_with_user_edits );

		foreach ( MiniCartBlock::MINI_CART_TEMPLATE_BLOCKS as $block_name ) {
			$p                        = new \WP_HTML_Tag_Processor( $processed_template );
			$class_name               = 'wp-block-' . str_replace( '/', '-', $block_name );
			$div_wrapper_still_exists = $p->next_tag(
				array(
					'tag_name'   => 'div',
					'class_name' => $class_name,
				)
			);
			$this->assertFalse( $div_wrapper_still_exists, "The div wrapper with class {$class_name} should have been removed." );
		}

		$p = new \WP_HTML_Tag_Processor( $processed_template );

		$this->assertTrue(
			$p->next_tag(
				array(
					'tag_name'   => 'div',
					'class_name' => 'wp-block-group',
				)
			),
			'The div with class wp-block-group should be preserved.'
		);

		$this->assertTrue(
			$p->next_tag(
				array(
					'tag_name'   => 'img',
					'class_name' => 'wp-image-block',
				)
			),
			'The img with class wp-image-block should be preserved.'
		);

		$this->assertTrue(
			$p->next_tag(
				array(
					'tag_name'   => 'hr',
					'class_name' => 'wp-block-separator',
				)
			),
			'The hr with class wp-block-separator should be preserved.'
		);
	}

	/**
	 * Helper method to check if mini-cart badge exists in the HTML using WP_HTML_Tag_Processor.
	 *
	 * @param string $html The HTML to search in.
	 * @return bool True if mini-cart badge is found, false otherwise.
	 */
	private function has_mini_cart_badge( string $html ): bool {
		$processor = new \WP_HTML_Tag_Processor( $html );

		return $processor->next_tag(
			array(
				'tag_name'   => 'span',
				'class_name' => 'wc-block-mini-cart__badge',
			)
		);
	}

	/**
	 * Test that mini-cart does not render for logged-out users when coming soon mode is enabled for store pages only.
	 *
	 * @return void
	 */
	public function test_mini_cart_does_not_render_for_logged_out_users_when_store_coming_soon() {
		// Set up coming soon mode for store pages only.
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'yes' );

		// Ensure user is logged out.
		wp_set_current_user( 0 );

		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart /-->' );
		$output = render_block( $block[0] );

		// Mini-cart should not render (empty output).
		$this->assertEmpty( $output, 'Mini-cart should not render for logged-out users when store is in coming soon mode.' );

		// Clean up.
		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'woocommerce_store_pages_only', 'no' );
	}

	/**
	 * Test that mini-cart renders for logged-in users when coming soon mode is enabled for store pages only.
	 *
	 * @return void
	 */
	public function test_mini_cart_renders_for_logged_in_users_when_store_coming_soon() {
		// Set up coming soon mode for store pages only.
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'yes' );

		// Create and log in a user.
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );

		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart /-->' );
		$output = render_block( $block[0] );

		// Mini-cart should render (non-empty output).
		$this->assertNotEmpty( $output, 'Mini-cart should render for logged-in users even when store is in coming soon mode.' );

		// Clean up.
		wp_set_current_user( 0 );
		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'woocommerce_store_pages_only', 'no' );
	}

	/**
	 * Test that mini-cart renders for logged-out users when coming soon mode is disabled.
	 *
	 * @return void
	 */
	public function test_mini_cart_renders_for_logged_out_users_when_coming_soon_disabled() {
		// Ensure coming soon mode is disabled.
		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'woocommerce_store_pages_only', 'no' );

		// Ensure user is logged out.
		wp_set_current_user( 0 );

		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart /-->' );
		$output = render_block( $block[0] );

		// Mini-cart should render (non-empty output).
		$this->assertNotEmpty( $output, 'Mini-cart should render for logged-out users when coming soon mode is disabled.' );
	}

	/**
	 * Test that mini-cart renders for logged-out users when site-wide coming soon mode is enabled (not store pages only).
	 *
	 * @return void
	 */
	public function test_mini_cart_renders_when_site_wide_coming_soon_not_store_only() {
		// Set up site-wide coming soon mode (not store pages only).
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'no' );

		// Ensure user is logged out.
		wp_set_current_user( 0 );

		$block  = parse_blocks( '<!-- wp:woocommerce/mini-cart /-->' );
		$output = render_block( $block[0] );

		// Mini-cart should render (non-empty output) because the logic only checks for store pages coming soon.
		$this->assertNotEmpty( $output, 'Mini-cart should render when site-wide coming soon is enabled but not store pages only.' );

		// Clean up.
		update_option( 'woocommerce_coming_soon', 'no' );
		update_option( 'woocommerce_store_pages_only', 'no' );
	}
}
