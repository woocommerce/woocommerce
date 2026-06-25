<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductButtonMock;

/**
 * Tests for the ProductButton server-side in-cart count seed.
 *
 * The `get_cart_item_quantities_by_product_id()` private method computes the
 * quantity shown in the server-rendered button before the Interactivity API
 * hydrates the client. Only plain (non-meta-differentiated) cart lines should
 * be counted so that the first-paint seed is consistent with the client-side
 * value produced by the Store API cart-item schema.
 *
 * @since 11.0.0
 */
class ProductButtonInCartCountTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductButtonMock
	 */
	private $sut;

	/**
	 * The original block type registry entry for the product-button block,
	 * saved so it can be restored in tearDown.
	 *
	 * @var \WP_Block_Type|null
	 */
	private $original_block_type;

	/**
	 * A simple product ID used across tests.
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// The block is already registered by WooCommerce bootstrap. Unregister it
		// so the mock constructor can re-register without triggering a conflict.
		$registry                  = \WP_Block_Type_Registry::get_instance();
		$this->original_block_type = null;
		if ( $registry->is_registered( 'woocommerce/product-button' ) ) {
			$this->original_block_type = $registry->get_registered( 'woocommerce/product-button' );
			$registry->unregister( 'woocommerce/product-button' );
		}

		$this->sut = new ProductButtonMock();

		// Ensure the cart is initialised and empty before each test.
		wc_empty_cart();

		$this->product_id = 42;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wc_empty_cart();

		// Restore the original block type registration.
		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/product-button' ) ) {
			$registry->unregister( 'woocommerce/product-button' );
		}
		if ( $this->original_block_type ) {
			$registry->register( $this->original_block_type );
		}

		parent::tearDown();
	}

	/**
	 * Build and inject a cart item array into WC()->cart->cart_contents for testing.
	 *
	 * Populates the minimum fields expected by
	 * {@see \Automattic\WooCommerce\StoreApi\Utilities\CartItemUtils::has_cart_item_data()}
	 * and the method under test. The stored key is generated via
	 * {@see \WC_Cart::generate_cart_id()} so the plain-vs-meta distinction works correctly.
	 *
	 * @param int   $product_id   The parent product ID for the cart line.
	 * @param int   $quantity     The line quantity.
	 * @param array $extra_data   Optional. Non-empty array means the key will be
	 *                            meta-differentiated (has_cart_item_data returns true).
	 * @param int   $variation_id Optional. Variation ID; 0 for simple products.
	 * @param array $variation    Optional. Variation attributes array.
	 * @return string The cart item key that was inserted.
	 */
	private function add_cart_item(
		int $product_id,
		int $quantity,
		array $extra_data = array(),
		int $variation_id = 0,
		array $variation = array()
	): string {
		$key = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation, $extra_data );

		WC()->cart->cart_contents[ $key ] = array(
			'key'          => $key,
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
			'quantity'     => $quantity,
		);

		return $key;
	}

	// -------------------------------------------------------------------------
	// WC()->cart unavailability
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return 0 when WC()->cart is unavailable, without fataling.
	 */
	public function test_returns_zero_when_cart_is_unavailable(): void {
		$original_cart = WC()->cart;
		WC()->cart     = null; // phpcs:ignore

		$result = $this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id );

		WC()->cart = $original_cart; // phpcs:ignore

		$this->assertSame(
			0,
			$result,
			'When WC()->cart is unavailable the method must return 0 without fataling.'
		);
	}

	// -------------------------------------------------------------------------
	// Product with no cart lines at all
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return 0 when the cart is completely empty.
	 */
	public function test_returns_zero_for_empty_cart(): void {
		$this->assertSame(
			0,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'An empty cart must yield a count of 0.'
		);
	}

	/**
	 * @testdox Should return 0 when the cart contains plain lines for a different product only.
	 */
	public function test_returns_zero_when_cart_has_only_different_product(): void {
		$this->add_cart_item( 99, 3 );

		$this->assertSame(
			0,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'Lines for a different product must not contribute to the count.'
		);
	}

	// -------------------------------------------------------------------------
	// Only meta-differentiated lines → count must be 0
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return 0 when product exists in the cart only as a meta-differentiated line.
	 */
	public function test_returns_zero_when_only_meta_differentiated_line_exists(): void {
		$this->add_cart_item( $this->product_id, 5, array( '_bundle' => 'parent-1' ) );

		$this->assertSame(
			0,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'A meta-differentiated cart line must not be counted in the server seed.'
		);
	}

	/**
	 * @testdox Should return 0 when there are multiple meta-differentiated lines for the same product.
	 */
	public function test_returns_zero_when_multiple_meta_differentiated_lines_exist(): void {
		$this->add_cart_item( $this->product_id, 2, array( '_bundle' => 'parent-1' ) );
		$this->add_cart_item( $this->product_id, 3, array( '_bundle' => 'parent-2' ) );

		$this->assertSame(
			0,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'Multiple meta-differentiated lines must all be excluded from the count.'
		);
	}

	// -------------------------------------------------------------------------
	// Only plain (standalone) lines → count equals the plain quantity
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return the standalone-line quantity when only a plain line exists at quantity 2.
	 */
	public function test_returns_plain_line_quantity_of_two(): void {
		$this->add_cart_item( $this->product_id, 2 );

		$this->assertSame(
			2,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'A single plain cart line with quantity 2 must be fully counted.'
		);
	}

	/**
	 * @testdox Should return the standalone-line quantity when only a plain line exists at quantity 1.
	 */
	public function test_returns_plain_line_quantity_of_one(): void {
		$this->add_cart_item( $this->product_id, 1 );

		$this->assertSame(
			1,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'A single plain cart line with quantity 1 must be fully counted.'
		);
	}

	// -------------------------------------------------------------------------
	// Mixed plain and meta-differentiated lines → only plain is counted
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return 1 when there is one plain line (qty 1) plus one meta-differentiated line.
	 */
	public function test_returns_plain_quantity_when_mixed_lines_exist(): void {
		$this->add_cart_item( $this->product_id, 1 );
		$this->add_cart_item( $this->product_id, 4, array( '_bundle' => 'parent-1' ) );

		$this->assertSame(
			1,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'The meta-differentiated line must be excluded; only the plain line quantity counts.'
		);
	}

	/**
	 * @testdox Should return plain-line quantity and ignore multiple meta-differentiated lines from several products.
	 */
	public function test_returns_plain_quantity_with_mixed_products_and_multiple_meta_lines(): void {
		$this->add_cart_item( $this->product_id, 3 );
		$this->add_cart_item( $this->product_id, 2, array( '_bundle' => 'parent-A' ) );
		$this->add_cart_item( $this->product_id, 1, array( '_bundle' => 'parent-B' ) );
		$this->add_cart_item( 99, 7 );

		$this->assertSame(
			3,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'Only the plain line for the queried product must be counted.'
		);
	}

	// -------------------------------------------------------------------------
	// Scoping: only lines whose product_id matches are considered
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should not count plain lines that belong to a different product.
	 */
	public function test_does_not_count_plain_lines_of_different_product(): void {
		$this->add_cart_item( 99, 10 );
		$this->add_cart_item( 100, 5 );

		$this->assertSame(
			0,
			$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id ),
			'Plain lines for other product IDs must not influence the count for the queried product.'
		);
	}

	// -------------------------------------------------------------------------
	// No new hooks introduced
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should not register any new WordPress action or filter hooks.
	 */
	public function test_does_not_add_new_hooks(): void {
		global $wp_filter;

		$this->add_cart_item( $this->product_id, 1 );

		$hooks_before = array_keys( $wp_filter );

		$this->sut->call_get_cart_item_quantities_by_product_id( $this->product_id );

		$hooks_after = array_keys( $wp_filter );
		$new_hooks   = array_diff( $hooks_after, $hooks_before );

		$this->assertEmpty(
			$new_hooks,
			'get_cart_item_quantities_by_product_id() must not register new WordPress hooks.'
		);
	}
}
