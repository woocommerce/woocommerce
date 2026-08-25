<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

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
	// Plain (standalone) line — is_standalone_line must return true
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return true for a plain simple-product line whose stored key matches the empty-data recompute.
	 */
	public function test_returns_true_for_plain_simple_product_line(): void {
		$plain_key = WC()->cart->generate_cart_id( $this->product_id );

		$cart_item = array(
			'key'          => $plain_key,
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$this->assertTrue(
			CartItemUtils::is_standalone_line( $cart_item ),
			'A plain simple-product line should be reported as a standalone line.'
		);
	}

	/**
	 * @testdox Should return true for a plain variation line whose stored key matches the empty-data recompute.
	 */
	public function test_returns_true_for_plain_variation_line(): void {
		$variation_id = 7;
		$variation    = array( 'attribute_color' => 'red' );
		$plain_key    = WC()->cart->generate_cart_id( $this->product_id, $variation_id, $variation );

		$cart_item = array(
			'key'          => $plain_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$this->assertTrue(
			CartItemUtils::is_standalone_line( $cart_item ),
			'A plain variation line (no extra cart item data) should be reported as a standalone line.'
		);
	}

	// -------------------------------------------------------------------------
	// Meta-differentiated line — is_standalone_line must return false
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return false for a simple-product line whose stored key was generated with non-empty cart_item_data.
	 */
	public function test_returns_false_for_simple_product_line_with_cart_item_data(): void {
		$cart_item_data = array( '_bundle' => 'bundle-parent-123' );
		$meta_key       = WC()->cart->generate_cart_id( $this->product_id, 0, array(), $cart_item_data );

		$cart_item = array(
			'key'          => $meta_key,
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$this->assertFalse(
			CartItemUtils::is_standalone_line( $cart_item ),
			'A line whose key was generated with non-empty cart_item_data should not be reported as a standalone line.'
		);
	}

	/**
	 * @testdox Should return false for a variation line whose stored key was generated with non-empty cart_item_data.
	 */
	public function test_returns_false_for_variation_line_with_cart_item_data(): void {
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

		$this->assertFalse(
			CartItemUtils::is_standalone_line( $cart_item ),
			'A variation line whose key was generated with non-empty cart_item_data should not be reported as a standalone line.'
		);
	}

	/**
	 * @testdox Should return false for a variation line with cart_item_data even when variation attributes are the same.
	 */
	public function test_returns_false_for_variation_line_differentiates_from_plain_variation(): void {
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

		$this->assertTrue(
			CartItemUtils::is_standalone_line( $plain_cart_item ),
			'The plain variation line must be reported as standalone.'
		);

		$this->assertFalse(
			CartItemUtils::is_standalone_line( $meta_cart_item ),
			'The meta-differentiated variation line must not be reported as a standalone line.'
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
		$original_cart = WC()->cart;
		WC()->cart     = null; // phpcs:ignore

		$cart_item = array(
			'key'          => 'some-key',
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$result = CartItemUtils::is_standalone_line( $cart_item );

		// Restore cart immediately.
		WC()->cart = $original_cart; // phpcs:ignore

		$this->assertFalse(
			$result,
			'When WC()->cart is unavailable the helper must degrade gracefully and return false.'
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

		$result = CartItemUtils::is_standalone_line( $cart_item );

		$this->assertIsBool(
			$result,
			'The helper must return a bool and not fatal when product_id/variation_id/variation keys are absent.'
		);

		$this->assertTrue(
			$result,
			'When missing keys default to 0/[], the stored key matches the recomputed key, so the line is treated as standalone.'
		);
	}

	// -------------------------------------------------------------------------
	// Signature of the retained method
	// -------------------------------------------------------------------------

	/**
	 * The utility is public API: extensions call it directly. Its behaviour is
	 * covered by the cases above; this pins the shape callers compile against.
	 *
	 * @testdox The method keeps its name, visibility, return type and single typed parameter.
	 */
	public function test_method_signature_is_unchanged(): void {
		$reflection = new \ReflectionMethod( CartItemUtils::class, 'is_standalone_line' );

		$this->assertSame( 'is_standalone_line', $reflection->getName() );
		$this->assertTrue( $reflection->isStatic(), 'is_standalone_line() must remain static.' );
		$this->assertTrue( $reflection->isPublic(), 'is_standalone_line() must remain public.' );
		$this->assertTrue( $reflection->hasReturnType(), 'is_standalone_line() must keep its declared return type.' );
		$this->assertSame( 'bool', (string) $reflection->getReturnType() );

		$parameters = $reflection->getParameters();
		$this->assertCount( 1, $parameters, 'is_standalone_line() must keep a single parameter.' );
		$this->assertSame( 'cart_item', $parameters[0]->getName() );
		$this->assertTrue( $parameters[0]->hasType() );
		$this->assertSame( 'array', (string) $parameters[0]->getType() );
	}

	// -------------------------------------------------------------------------
	// Canonical quantity index — build_canonical_quantity_index
	//
	// The method is pure: it consumes the Store API cart-item response `items`
	// shape (per line: id, type, quantity, is_canonical_product_line) and needs
	// no WC bootstrap. These cases exercise it directly, through the shared
	// entry point, with in-memory arrays.
	// -------------------------------------------------------------------------

	/**
	 * Build a minimal Store-API-shaped cart item entry.
	 *
	 * @param int       $id        The product ID.
	 * @param int|float $quantity  The line quantity.
	 * @param array     $overrides Additional or overriding keys, e.g. 'is_canonical_product_line', 'type'.
	 * @return array
	 */
	private function item( int $id, $quantity, array $overrides = array() ): array {
		return array_merge(
			array(
				'id'       => $id,
				'quantity' => $quantity,
			),
			$overrides
		);
	}

	/**
	 * @testdox Should skip entries without an id, including a literal empty-array entry, without error.
	 */
	public function test_skips_entry_without_id_including_literal_empty_array(): void {
		$index = CartItemUtils::build_canonical_quantity_index(
			array(
				array( 'quantity' => 5 ),
				array(),
				$this->item( 10, 2 ),
			)
		);

		$this->assertSame( array( 10 => 2 ), $index, 'Entries without an id must be skipped and the surviving entry must still be indexed.' );
	}

	/**
	 * @testdox Should skip an entry whose is_canonical_product_line is strictly false.
	 */
	public function test_skips_entry_with_is_canonical_product_line_strictly_false(): void {
		$index = CartItemUtils::build_canonical_quantity_index(
			array(
				$this->item( 10, 2, array( 'is_canonical_product_line' => false ) ),
			)
		);

		$this->assertSame( array(), $index, 'An entry with is_canonical_product_line strictly false must not be indexed.' );
	}

	/**
	 * @testdox Should count an entry whose is_canonical_product_line field is missing.
	 */
	public function test_counts_entry_with_missing_is_canonical_product_line_key(): void {
		$index = CartItemUtils::build_canonical_quantity_index(
			array(
				$this->item( 10, 2 ),
			)
		);

		$this->assertSame( array( 10 => 2 ), $index, 'A missing is_canonical_product_line field must degrade to counted, matching the client.' );
	}

	/**
	 * @testdox Should never index a variation-typed entry, whatever its is_canonical_product_line value.
	 * @dataProvider provider_variation_typed_entry_overrides
	 *
	 * @param array $overrides Overrides merged into the entry, in addition to `type => variation`.
	 */
	public function test_skips_variation_typed_entry_regardless_of_is_canonical_product_line( array $overrides ): void {
		$overrides['type'] = 'variation';

		$index = CartItemUtils::build_canonical_quantity_index(
			array(
				$this->item( 10, 2, $overrides ),
			)
		);

		$this->assertSame( array(), $index, 'A variation-typed entry must never be indexed by product ID alone.' );
	}

	/**
	 * Data provider of is_canonical_product_line overrides for variation-typed entries.
	 *
	 * @return array
	 */
	public function provider_variation_typed_entry_overrides(): array {
		return array(
			'is_canonical_product_line true'    => array( array( 'is_canonical_product_line' => true ) ),
			'is_canonical_product_line missing' => array( array() ),
			'is_canonical_product_line false'   => array( array( 'is_canonical_product_line' => false ) ),
		);
	}

	/**
	 * @testdox Should keep the first surviving line per id, in cart order, and never sum quantities.
	 */
	public function test_keeps_first_surviving_line_per_id_and_never_sums(): void {
		$index = CartItemUtils::build_canonical_quantity_index(
			array(
				$this->item( 10, 2 ),
				$this->item( 10, 3 ),
			)
		);

		$this->assertSame( array( 10 => 2 ), $index, 'The first surviving line in cart order must win.' );
		$this->assertNotSame( 5, $index[10] ?? null, 'The quantity must never be the sum of both lines.' );
	}

	/**
	 * @testdox Should return a fractional quantity unchanged as a float.
	 */
	public function test_returns_fractional_quantity_unchanged_as_float(): void {
		$index = CartItemUtils::build_canonical_quantity_index(
			array(
				$this->item( 10, 1.5 ),
			)
		);

		$this->assertSame( array( 10 => 1.5 ), $index );
		$this->assertIsFloat( $index[10], 'Nothing must cast the fractional quantity to int.' );
	}

	/**
	 * @testdox Should return an empty array for an empty input.
	 */
	public function test_returns_empty_array_for_empty_input(): void {
		$this->assertSame( array(), CartItemUtils::build_canonical_quantity_index( array() ) );
	}

	/**
	 * @testdox Should key the index by the entry's id.
	 */
	public function test_keys_index_by_entry_id(): void {
		$index = CartItemUtils::build_canonical_quantity_index(
			array(
				$this->item( 10, 2 ),
				$this->item( 20, 4 ),
			)
		);

		$this->assertSame(
			array(
				10 => 2,
				20 => 4,
			),
			$index
		);
	}
}
