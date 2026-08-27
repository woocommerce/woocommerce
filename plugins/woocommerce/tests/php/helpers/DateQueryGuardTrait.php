<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Helpers;

/**
 * Trait DateQueryGuardTrait.
 *
 * Shared date_query cases for the order stores, so the HPOS and legacy guards are held to the same
 * contract. The two are separate implementations that cannot share code, and they have drifted
 * apart four times: on status sibling handling, on whether a time key accepts a list, and twice on
 * the ordering between the clause args and the array-aware check. Each drift shipped a defect that
 * the suites did not catch, because each suite only exercised its own backend.
 *
 * Cases where the backends legitimately differ are deliberately absent rather than forced into
 * agreement. 'column' is the example: an unusable column fatals inside the HPOS column validation
 * but is ignored by WP_Query, so the two correctly answer it differently.
 */
trait DateQueryGuardTrait {

	/**
	 * A value usable as a string but not as a date component.
	 *
	 * WP_Date_Query hands numeric components to mktime(), which declares ?int, so this fatals
	 * there while passing any stringability check.
	 *
	 * @return object
	 */
	private static function stringable_date_component() {
		return new class() {
			/**
			 * Renders as a plausible year.
			 *
			 * @return string
			 */
			public function __toString(): string {
				return '2024';
			}
		};
	}

	/**
	 * date_query clauses that must keep matching, seeded against an order created 2024-06-01.
	 *
	 * The list forms are how WP_Date_Query expresses IN, NOT IN, BETWEEN and NOT BETWEEN, and are
	 * documented as supported, so a guard must validate their elements rather than the list. An
	 * empty list is included because it is the shape that broke twice: a guard that iterates the
	 * elements sees nothing to reject, which is correct here and wrong for 'compare' and
	 * 'relation' below. The unrecognised key carries an object rather than a string, so that a
	 * guard checking every leaf instead of the consumed keys fails here.
	 *
	 * @return array<string, array{0: array, 1: bool}>
	 */
	public function provider_date_query_must_match(): array {
		return array(
			'year scalar'      => array( array( array( 'year' => 2024 ) ) ),
			'year IN'          => array(
				array(
					array(
						'year'    => array( 2024, 2025 ),
						'compare' => 'IN',
					),
				),
			),
			'year NOT IN'      => array(
				array(
					array(
						'year'    => array( 2019, 2020 ),
						'compare' => 'NOT IN',
					),
				),
			),
			'year BETWEEN'     => array(
				array(
					array(
						'year'    => array( 2023, 2025 ),
						'compare' => 'BETWEEN',
					),
				),
			),
			'month IN'         => array(
				array(
					array(
						'month'   => array( 5, 6 ),
						'compare' => 'IN',
					),
				),
			),
			'day NOT IN'       => array(
				array(
					array(
						'day'     => array( 9, 10 ),
						'compare' => 'NOT IN',
					),
				),
			),
			'after date parts' => array( array( array( 'after' => array( 'year' => 2023 ) ) ) ),
			'year empty list'  => array(
				array(
					array(
						'year'    => array(),
						'compare' => 'IN',
					),
				),
			),
			'after empty list' => array( array( array( 'after' => array() ) ) ),
			'year IN no match' => array(
				array(
					array(
						'year'    => array( 2019, 2020 ),
						'compare' => 'IN',
					),
				),
				false,
			),
			'numeric string'   => array( array( array( 'year' => '2024' ) ) ),
			'unrecognised key' => array(
				array(
					array(
						'year'    => 2024,
						'ext_ctx' => new \stdClass(),
					),
				),
			),
		);
	}

	/**
	 * date_query clauses that must return nothing rather than fatal, and never widen the query.
	 *
	 * 'compare' and 'relation' holding an array are the dangerous pair: WP_Date_Query reads an
	 * array under either as another clause and recurses on it until memory runs out, so a guard
	 * that iterates the array instead of rejecting it takes the process down.
	 *
	 * @return array<string, array{0: array}>
	 */
	public function provider_date_query_must_fail_closed(): array {
		return array(
			'year object'          => array( array( array( 'year' => new \stdClass() ) ) ),
			'year list w/ object'  => array(
				array(
					array(
						'year'    => array( 2024, new \stdClass() ),
						'compare' => 'IN',
					),
				),
			),
			'after object'         => array( array( array( 'after' => new \stdClass() ) ) ),
			'before parts object'  => array( array( array( 'before' => array( 'year' => new \stdClass() ) ) ) ),
			'relation empty array' => array(
				array(
					'relation' => array(),
					array( 'year' => 2024 ),
				),
			),
			'relation object'      => array(
				array(
					'relation' => new \stdClass(),
					array( 'year' => 2024 ),
				),
			),
			'compare empty array'  => array(
				array(
					array(
						'compare' => array(),
						'year'    => 2024,
					),
				),
			),
			'compare object'       => array(
				array(
					array(
						'compare' => new \stdClass(),
						'year'    => 2024,
					),
				),
			),
			'stringable year'      => array(
				array(
					array( 'year' => self::stringable_date_component() ),
				),
			),
			'stringable before'    => array(
				array(
					array( 'before' => array( 'year' => self::stringable_date_component() ) ),
				),
			),
			'non-numeric year'     => array( array( array( 'year' => 'abc' ) ) ),
			'nested unknown key'   => array( array( array( 'ext_ctx' => array( 'year' => new \stdClass() ) ) ) ),
		);
	}
}
