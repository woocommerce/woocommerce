<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\CartItemUtils;

/**
 * Tests for the CartItemUtils class.
 */
class CartItemUtilsTest extends \WC_Unit_Test_Case {

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
		// Ensure the cart object is initialized so generate_cart_id() is callable.
		wc_empty_cart();
		$this->product_id = 42;
	}

	// -------------------------------------------------------------------------
	// Plain (standalone) line — has_cart_item_data must return false
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return false for a plain simple-product line whose stored key matches the empty-data recompute.
	 */
	public function test_returns_false_for_plain_simple_product_line(): void {
		$plain_key = WC()->cart->generate_cart_id( $this->product_id );

		$cart_item = array(
			'key'          => $plain_key,
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$this->assertFalse(
			CartItemUtils::has_cart_item_data( $cart_item ),
			'A plain simple-product line should not be reported as carrying cart item data.'
		);
	}

	/**
	 * @testdox Should return false for a plain variation line whose stored key matches the empty-data recompute.
	 */
	public function test_returns_false_for_plain_variation_line(): void {
		$variation_id = 7;
		$variation    = array( 'attribute_color' => 'red' );
		$plain_key    = WC()->cart->generate_cart_id( $this->product_id, $variation_id, $variation );

		$cart_item = array(
			'key'          => $plain_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$this->assertFalse(
			CartItemUtils::has_cart_item_data( $cart_item ),
			'A plain variation line (no extra cart item data) should not be reported as carrying cart item data.'
		);
	}

	// -------------------------------------------------------------------------
	// Meta-differentiated line — has_cart_item_data must return true
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return true for a simple-product line whose stored key was generated with non-empty cart_item_data.
	 */
	public function test_returns_true_for_simple_product_line_with_cart_item_data(): void {
		$cart_item_data    = array( '_bundle' => 'bundle-parent-123' );
		$meta_key          = WC()->cart->generate_cart_id( $this->product_id, 0, array(), $cart_item_data );

		$cart_item = array(
			'key'          => $meta_key,
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$this->assertTrue(
			CartItemUtils::has_cart_item_data( $cart_item ),
			'A line whose key was generated with non-empty cart_item_data should be reported as carrying cart item data.'
		);
	}

	/**
	 * @testdox Should return true for a variation line whose stored key was generated with non-empty cart_item_data.
	 */
	public function test_returns_true_for_variation_line_with_cart_item_data(): void {
		$variation_id   = 7;
		$variation      = array( 'attribute_color' => 'blue' );
		$cart_item_data = array( '_cart_line_identity' => 'composite-child-456' );
		$meta_key       = WC()->cart->generate_cart_id( $this->product_id, $variation_id, $variation, $cart_item_data );

		$cart_item = array(
			'key'          => $meta_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$this->assertTrue(
			CartItemUtils::has_cart_item_data( $cart_item ),
			'A variation line whose key was generated with non-empty cart_item_data should be reported as carrying cart item data.'
		);
	}

	/**
	 * @testdox Should return true for a variation line with cart_item_data even when variation attributes are the same.
	 */
	public function test_returns_true_for_variation_line_differentiates_from_plain_variation(): void {
		$variation_id = 7;
		$variation    = array( 'attribute_size' => 'large' );

		// The plain key has no cart_item_data.
		$plain_key = WC()->cart->generate_cart_id( $this->product_id, $variation_id, $variation );

		// The meta-differentiated key carries extra data.
		$meta_key = WC()->cart->generate_cart_id(
			$this->product_id,
			$variation_id,
			$variation,
			array( '_subscription_switch' => 'yes' )
		);

		// Sanity-check that the two keys are actually different.
		$this->assertNotSame( $plain_key, $meta_key );

		$plain_cart_item = array(
			'key'          => $plain_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$meta_cart_item = array(
			'key'          => $meta_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$this->assertFalse(
			CartItemUtils::has_cart_item_data( $plain_cart_item ),
			'The plain variation line must be reported as plain.'
		);

		$this->assertTrue(
			CartItemUtils::has_cart_item_data( $meta_cart_item ),
			'The meta-differentiated variation line must be reported as carrying cart item data.'
		);
	}

	// -------------------------------------------------------------------------
	// Defensive / edge cases
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should not fatal and should return false when WC()->cart is unavailable.
	 */
	public function test_returns_false_when_cart_is_unavailable(): void {
		// Temporarily detach the cart from the WC instance.
		$original_cart      = WC()->cart;
		WC()->cart          = null; // phpcs:ignore

		$cart_item = array(
			'key'          => 'some-key',
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$result = CartItemUtils::has_cart_item_data( $cart_item );

		// Restore cart immediately.
		WC()->cart = $original_cart; // phpcs:ignore

		$this->assertFalse(
			$result,
			'When WC()->cart is unavailable the helper must degrade gracefully and treat the line as plain.'
		);
	}

	/**
	 * @testdox Should not fatal when cart item is missing product_id, variation_id, and variation keys.
	 */
	public function test_does_not_fatal_for_malformed_cart_item_missing_keys(): void {
		// With defaults (product_id=0, variation_id=0, variation=[]), the recomputed
		// key is generate_cart_id(0). Build a matching stored key so the result is
		// deterministic and we can assert on it while confirming no fatal occurs.
		$default_key = WC()->cart->generate_cart_id( 0 );

		$cart_item = array(
			'key' => $default_key,
			// product_id, variation_id, variation intentionally absent.
		);

		$result = CartItemUtils::has_cart_item_data( $cart_item );

		$this->assertIsBool(
			$result,
			'The helper must return a bool and not fatal when product_id/variation_id/variation keys are absent.'
		);

		$this->assertFalse(
			$result,
			'When missing keys default to 0/[], the stored key should match the recomputed key, yielding false.'
		);
	}

	/**
	 * @testdox Should not introduce new WordPress action or filter hooks.
	 */
	public function test_does_not_add_new_hooks(): void {
		global $wp_filter;

		$hooks_before = array_keys( $wp_filter );

		// Call the method; any hooks it registers during the call would be visible.
		$plain_key = WC()->cart->generate_cart_id( $this->product_id );
		CartItemUtils::has_cart_item_data(
			array(
				'key'          => $plain_key,
				'product_id'   => $this->product_id,
				'variation_id' => 0,
				'variation'    => array(),
			)
		);

		$hooks_after = array_keys( $wp_filter );

		$new_hooks = array_diff( $hooks_after, $hooks_before );

		$this->assertEmpty(
			$new_hooks,
			'CartItemUtils::has_cart_item_data() must not register new WordPress hooks.'
		);
	}
}
