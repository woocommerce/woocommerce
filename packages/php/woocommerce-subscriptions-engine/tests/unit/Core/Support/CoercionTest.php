<?php
/**
 * Unit tests for Coercion's array coercions.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Support;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion
 */
class CoercionTest extends TestCase {

	public function test_coerce_string_keyed_casts_keys_to_strings_and_keeps_values(): void {
		$result = Coercion::coerce_string_keyed(
			array(
				0   => 'zero',
				'a' => array( 1, 2 ),
				7   => null,
			)
		);

		$this->assertSame(
			array(
				'0' => 'zero',
				'a' => array( 1, 2 ),
				'7' => null,
			),
			$result
		);
	}

	public function test_coerce_string_keyed_keeps_empty_array(): void {
		$this->assertSame( array(), Coercion::coerce_string_keyed( array() ) );
	}

	/**
	 * @dataProvider provide_non_arrays
	 *
	 * @param mixed $value Non-array input.
	 */
	public function test_coerce_list_of_arrays_returns_empty_for_non_array( $value ): void {
		$this->assertSame( array(), Coercion::coerce_list_of_arrays( $value ) );
	}

	public function test_coerce_list_of_arrays_skips_non_array_rows_and_reindexes(): void {
		$result = Coercion::coerce_list_of_arrays(
			array(
				'x' => array( 'sku' => 'A' ),
				5   => 'not a row',
				9   => array( 0 => 'b' ),
				10  => null,
			)
		);

		$this->assertSame(
			array(
				array( 'sku' => 'A' ),
				array( '0' => 'b' ),
			),
			$result
		);
	}

	/**
	 * @dataProvider provide_non_arrays
	 *
	 * @param mixed $value Non-array input.
	 */
	public function test_coerce_map_of_arrays_returns_empty_for_non_array( $value ): void {
		$this->assertSame( array(), Coercion::coerce_map_of_arrays( $value ) );
	}

	public function test_coerce_map_of_arrays_keeps_keys_and_skips_non_array_entries(): void {
		$result = Coercion::coerce_map_of_arrays(
			array(
				'billing'  => array( 'city' => 'Lisbon' ),
				'shipping' => 'not an entry',
				3          => array( 1 => 'x' ),
			)
		);

		$this->assertSame(
			array(
				'billing' => array( 'city' => 'Lisbon' ),
				'3'       => array( '1' => 'x' ),
			),
			$result
		);
	}

	/**
	 * Non-array inputs.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provide_non_arrays(): array {
		return array(
			'null'   => array( null ),
			'string' => array( 'items' ),
			'int'    => array( 3 ),
			'object' => array( new \stdClass() ),
		);
	}
}
