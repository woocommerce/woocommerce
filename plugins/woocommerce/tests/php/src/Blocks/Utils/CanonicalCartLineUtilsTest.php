<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\CanonicalCartLineUtils;

/**
 * Tests for the CanonicalCartLineUtils class.
 */
class CanonicalCartLineUtilsTest extends \WC_Unit_Test_Case {

	// -------------------------------------------------------------------------
	// Canonical quantity index
	//
	// The utility is pure: it consumes the Store API cart-item response `items`
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
		$index = CanonicalCartLineUtils::get_first_canonical_line_quantities(
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
		$index = CanonicalCartLineUtils::get_first_canonical_line_quantities(
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
		$index = CanonicalCartLineUtils::get_first_canonical_line_quantities(
			array(
				$this->item( 10, 2 ),
			)
		);

		$this->assertSame( array( 10 => 2 ), $index, 'A missing is_canonical_product_line field must degrade to counted, matching the client.' );
	}

	/**
	 * @testdox Should count an entry with a missing quantity as zero.
	 */
	public function test_counts_entry_with_missing_quantity_as_zero(): void {
		$index = CanonicalCartLineUtils::get_first_canonical_line_quantities(
			array(
				array( 'id' => 10 ),
			)
		);

		$this->assertSame( array( 10 => 0 ), $index, 'An entry with an id but no quantity must be indexed with a quantity of zero.' );
	}

	/**
	 * @testdox Should never index a variation-typed entry, whatever its is_canonical_product_line value.
	 * @dataProvider provider_variation_typed_entry_overrides
	 *
	 * @param array $overrides Overrides merged into the entry, in addition to `type => variation`.
	 */
	public function test_skips_variation_typed_entry_regardless_of_is_canonical_product_line( array $overrides ): void {
		$overrides['type'] = 'variation';

		$index = CanonicalCartLineUtils::get_first_canonical_line_quantities(
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
		$index = CanonicalCartLineUtils::get_first_canonical_line_quantities(
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
		$index = CanonicalCartLineUtils::get_first_canonical_line_quantities(
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
		$this->assertSame( array(), CanonicalCartLineUtils::get_first_canonical_line_quantities( array() ) );
	}

	/**
	 * @testdox Should key the index by the entry's id.
	 */
	public function test_keys_index_by_entry_id(): void {
		$index = CanonicalCartLineUtils::get_first_canonical_line_quantities(
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
