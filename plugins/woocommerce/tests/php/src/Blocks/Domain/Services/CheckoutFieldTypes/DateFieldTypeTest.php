<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services\CheckoutFieldTypes;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes\DateFieldType;
use WP_UnitTestCase;

/**
 * Tests for the DateFieldType class.
 */
class DateFieldTypeTest extends WP_UnitTestCase {
	/**
	 * The system under test.
	 *
	 * @var DateFieldType
	 */
	private $field_type;

	/**
	 * Setup test case.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field_type = new DateFieldType();
	}

	/**
	 * @testdox Absolute dates and ISO 8601-2 durations are accepted as constraints.
	 *
	 * @testWith ["2026-08-26", "2026-08-26"]
	 *           ["P0D", "today"]
	 *           ["P1D", "+1 day"]
	 *           ["-P5D", "-5 days"]
	 *           ["P2W", "+14 days"]
	 *           ["+P2W", "+14 days"]
	 *           ["-P18Y", "-18 years"]
	 *           ["P1Y2M3D", "+1 year +2 months +3 days"]
	 *           ["today", null]
	 *           ["+1 day", null]
	 *           ["now", null]
	 *           ["PT1H", null]
	 *           ["P", null]
	 *           ["garbage", null]
	 *           ["", null]
	 *           ["2026-02-31", null]
	 *           ["2026-8-6", null]
	 *
	 * @param string      $constraint The constraint to resolve.
	 * @param string|null $equivalent A date expression resolving to the same day, or null if unsupported.
	 */
	public function test_supported_date_constraint_vocabulary( string $constraint, ?string $equivalent ) {
		$expected = null === $equivalent ? null : $this->date_relative_to_today( $equivalent );

		$this->assertSame( $expected, $this->field_type->resolve_constraint( $constraint ) );
	}

	/**
	 * @testdox A DateInterval is accepted as a constraint, including one built from a relative string.
	 */
	public function test_date_interval_constraints() {
		$this->assertSame(
			$this->date_relative_to_today( '+1 day' ),
			$this->field_type->resolve_constraint( new \DateInterval( 'P1D' ) )
		);

		// The documented bridge for authors who would rather write a relative string than an ISO duration.
		$this->assertSame(
			$this->date_relative_to_today( '-18 years' ),
			$this->field_type->resolve_constraint( \DateInterval::createFromDateString( '-18 years' ) )
		);

		// ISO 8601-2 signs the whole duration, so a mix of directions has no equivalent.
		$this->assertNull(
			$this->field_type->resolve_constraint( \DateInterval::createFromDateString( '+1 month -3 days' ) )
		);
	}

	/**
	 * @testdox Month arithmetic clamps to the end of the target month, matching Temporal on the client.
	 *
	 * @testWith ["2026-01-31", "P1M", "2026-02-28"]
	 *           ["2026-03-31", "-P1M", "2026-02-28"]
	 *           ["2024-02-29", "P1Y", "2025-02-28"]
	 *           ["2026-01-31", "P1M15D", "2026-03-15"]
	 *
	 * @param string $today      The current date in the store timezone.
	 * @param string $constraint The constraint to resolve.
	 * @param string $expected   The expected resolved date.
	 */
	public function test_month_arithmetic_clamps_like_temporal( string $today, string $constraint, string $expected ) {
		// PHP's own DateInterval arithmetic would roll 31 January + P1M forward to 3 March and disagree
		// with the date the browser offers in the picker.
		$reference = new \DateTimeImmutable( $today, wp_timezone() );

		$this->assertSame( $expected, $this->field_type->resolve_constraint( $constraint, $reference ) );
	}

	/**
	 * @testdox get_constraints resolves both bounds and returns null for missing ones.
	 */
	public function test_get_constraints() {
		$this->assertSame(
			array(
				'min' => $this->date_relative_to_today( 'today' ),
				'max' => '2026-12-31',
			),
			$this->field_type->get_constraints(
				array(
					'min' => 'P0D',
					'max' => '2026-12-31',
				)
			)
		);

		$this->assertSame(
			array(
				'min' => null,
				'max' => null,
			),
			$this->field_type->get_constraints( array() )
		);
	}

	/**
	 * @testdox prepare_form_field exposes the resolved constraints as input attributes.
	 */
	public function test_prepare_form_field() {
		$form_field = $this->field_type->prepare_form_field(
			array(
				'type' => 'date',
				'min'  => 'P0D',
			)
		);

		$this->assertSame( array( 'min' => $this->date_relative_to_today( 'today' ) ), $form_field['custom_attributes'] );
	}

	/**
	 * Returns a Y-m-d date, resolved independently of the code under test.
	 *
	 * @param string $expression An absolute Y-m-d date, or an expression relative to today such as "+1 day".
	 * @return string
	 */
	private function date_relative_to_today( string $expression ): string {
		return ( new \DateTime( $expression, wp_timezone() ) )->format( 'Y-m-d' );
	}
}
