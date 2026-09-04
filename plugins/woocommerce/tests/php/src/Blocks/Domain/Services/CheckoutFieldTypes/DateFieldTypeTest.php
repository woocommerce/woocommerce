<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services\CheckoutFieldTypes;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes\DateFieldType;
use WP_Error;
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
	 * @testdox Absolute dates and ISO 8601-2 durations resolve to the expected dates.
	 *
	 * @testWith ["2025-08-26", "2025-08-26"]
	 *           ["P0D", "today"]
	 *           ["P1D", "+1 day"]
	 *           ["-P5D", "-5 days"]
	 *           ["P2W", "+14 days"]
	 *           ["-P18Y", "-18 years"]
	 *           ["P1Y2M3D", "+1 year +2 months +3 days"]
	 *
	 * @param string $constraint The constraint to resolve.
	 * @param string $equivalent A date expression resolving to the same day.
	 */
	public function test_supported_date_constraint_vocabulary( string $constraint, string $equivalent ) {
		$form_field = $this->field_type->prepare_form_field( $this->date_field( array( 'min' => $constraint ) ) );

		$this->assertSame( $this->date_relative_to_today( $equivalent ), $form_field['custom_attributes']['min'] );
	}

	/**
	 * @testdox Invalid constraints fail registration with an error saying why they were rejected.
	 *
	 * @testWith ["min", "2026-02-31", "real calendar date"]
	 *           ["max", "2026-02-31", "real calendar date"]
	 *           ["min", "2026-8-6", "ISO 8601-2 duration"]
	 *           ["min", "PT1H", "time component"]
	 *           ["min", "P1DT2H", "time component"]
	 *           ["max", "garbage", "ISO 8601-2 duration"]
	 *           ["min", "--P1D", "ISO 8601-2 duration"]
	 *           ["min", "today", "ISO 8601-2 duration"]
	 *           ["min", "+1 day", "ISO 8601-2 duration"]
	 *           ["min", "P", "ISO 8601-2 duration"]
	 *           ["max", "", "ISO 8601-2 duration"]
	 *
	 * @param string $key        The constraint being set.
	 * @param string $constraint The invalid value.
	 * @param string $expected   A phrase expected in the registration error.
	 */
	public function test_invalid_constraints_are_registration_errors( string $key, string $constraint, string $expected ) {
		$this->setExpectedIncorrectUsage( 'woocommerce_register_additional_checkout_field' );

		$message = null;
		add_action(
			'doing_it_wrong_run',
			function ( $function_name, $function_message ) use ( &$message ) {
				// Avoid parameter-not-used PHPCS errors.
				unset( $function_name );
				$message = $function_message;
			},
			10,
			2
		);

		$this->assertFalse( $this->register( array( $key => $constraint ) ) );
		$this->assertStringContainsString( $expected, (string) $message );
	}

	/**
	 * @testdox A constraint that is not a string fails registration.
	 */
	public function test_registration_rejects_non_string_constraints() {
		$this->setExpectedIncorrectUsage( 'woocommerce_register_additional_checkout_field' );

		$this->assertFalse( $this->register( array( 'min' => 20260826 ) ) );
		$this->assertFalse( $this->register( array( 'max' => new \DateInterval( 'P1D' ) ) ) );
	}

	/**
	 * @testdox Registration trims a duration and drops an explicit plus sign.
	 */
	public function test_registration_normalizes_constraints() {
		$this->assertSame( 'P2W', $this->register( array( 'min' => ' +P2W ' ) )['min'] );
	}

	/**
	 * @testdox Durations are stored on the field as registered, not resolved.
	 */
	public function test_constraints_are_stored_unresolved() {
		// A resolved value here would freeze into any page cache holding the rendered form.
		$field_data = $this->register(
			array(
				'min' => 'P0D',
				'max' => 'P30D',
			)
		);

		$this->assertSame( 'P0D', $field_data['min'] );
		$this->assertSame( 'P30D', $field_data['max'] );
	}

	/**
	 * @testdox A field registered without constraints carries no min or max key.
	 */
	public function test_missing_constraints_are_dropped() {
		$field_data = $this->register( array() );

		$this->assertArrayNotHasKey( 'min', $field_data );
		$this->assertArrayNotHasKey( 'max', $field_data );
	}

	/**
	 * @testdox A min that resolves later than the max fails registration.
	 *
	 * @testWith ["2025-12-31", "2025-01-01"]
	 *           ["P2M", "P1M"]
	 *           ["-P13Y", "-P18Y"]
	 *
	 * @param string $min The min constraint.
	 * @param string $max The max constraint.
	 */
	public function test_inverted_constraints_are_registration_errors( string $min, string $max ) {
		$this->setExpectedIncorrectUsage( 'woocommerce_register_additional_checkout_field' );

		$this->assertFalse(
			$this->register(
				array(
					'min' => $min,
					'max' => $max,
				)
			)
		);
	}

	/**
	 * @testdox An ordered min/max pair is accepted, as is a mixed absolute/duration pair.
	 *
	 * @testWith ["P1D", "P1M"]
	 *           ["P28D", "P1M"]
	 *           ["P12M", "P370D"]
	 *           ["-P18Y", "-P13Y"]
	 *           ["P0D", "2999-12-31"]
	 *           ["2020-01-01", "P0D"]
	 *
	 * @param string $min The min constraint.
	 * @param string $max The max constraint.
	 */
	public function test_ordered_constraints_are_accepted( string $min, string $max ) {
		$this->assertIsArray(
			$this->register(
				array(
					'min' => $min,
					'max' => $max,
				)
			)
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
		// with the date the browser offers in the picker. Resolution is relative to today, so the
		// private resolver is called directly to pin the day the arithmetic starts from.
		$resolve = new \ReflectionMethod( $this->field_type, 'resolve_constraint' );
		$resolve->setAccessible( true );

		$this->assertSame( $expected, $resolve->invoke( $this->field_type, $constraint, new \DateTimeImmutable( $today, wp_timezone() ) ) );
	}

	/**
	 * @testdox prepare_form_field exposes both resolved constraints as input attributes.
	 */
	public function test_prepare_form_field() {
		$form_field = $this->field_type->prepare_form_field(
			$this->date_field(
				array(
					'min' => 'P0D',
					'max' => '2026-12-31',
				)
			)
		);

		$this->assertSame(
			array(
				'min' => $this->date_relative_to_today( 'today' ),
				'max' => '2026-12-31',
			),
			$form_field['custom_attributes']
		);
	}

	/**
	 * @testdox An unconstrained field gets no min or max input attribute.
	 */
	public function test_prepare_form_field_without_constraints() {
		$form_field = $this->field_type->prepare_form_field( $this->date_field() );

		$this->assertSame( array(), $form_field['custom_attributes'] );
	}

	/**
	 * @testdox Only a real calendar date in Y-m-d format is accepted.
	 *
	 * @testWith ["2025-08-26", false]
	 *           ["", false]
	 *           ["2025-02-31", true]
	 *           ["2025-8-6", true]
	 *           ["26-08-2025", true]
	 *           ["not-a-date", true]
	 *
	 * @param string $value      The submitted value.
	 * @param bool   $has_errors Whether the value should be rejected.
	 */
	public function test_only_real_calendar_dates_are_valid( string $value, bool $has_errors ) {
		$this->assert_rejects( $has_errors, $value, $this->date_field() );
	}

	/**
	 * @testdox Absolute date constraints are enforced, inclusive of both bounds.
	 *
	 * @testWith ["2025-01-01", false]
	 *           ["2025-06-15", false]
	 *           ["2025-12-31", false]
	 *           ["2024-12-31", true]
	 *           ["2026-01-01", true]
	 *
	 * @param string $value      The submitted value.
	 * @param bool   $has_errors Whether the value should be rejected.
	 */
	public function test_absolute_date_constraints_are_enforced( string $value, bool $has_errors ) {
		$this->assert_rejects(
			$has_errors,
			$value,
			$this->date_field(
				array(
					'min' => '2025-01-01',
					'max' => '2025-12-31',
				)
			)
		);
	}

	/**
	 * @testdox Relative date constraints are enforced against the current date.
	 *
	 * @testWith ["today", false]
	 *           ["+1 day", false]
	 *           ["+30 days", false]
	 *           ["-1 day", true]
	 *           ["+31 days", true]
	 *
	 * @param string $offset     The submitted value, relative to today.
	 * @param bool   $has_errors Whether the value should be rejected.
	 */
	public function test_relative_date_constraints_are_enforced( string $offset, bool $has_errors ) {
		$this->assert_rejects(
			$has_errors,
			$this->date_relative_to_today( $offset ),
			$this->date_field(
				array(
					'min' => 'P0D',
					'max' => 'P30D',
				)
			)
		);
	}

	/**
	 * @testdox A field without constraints accepts any real calendar date.
	 */
	public function test_unconstrained_fields_have_no_bounds() {
		$this->assert_rejects( false, '1901-01-01', $this->date_field() );
		$this->assert_rejects( false, '2222-12-31', $this->date_field() );
	}

	/**
	 * @testdox An out of range date is rejected with a message naming the boundary in the site date format.
	 */
	public function test_out_of_range_date_error_message() {
		update_option( 'date_format', 'F j, Y' );

		$field = $this->date_field(
			array(
				'min' => '2025-01-01',
				'max' => '2025-12-31',
			)
		);

		$this->assertSame(
			'Please provide a Promotion date on or after January 1, 2025.',
			$this->field_type->validate( '2024-12-31', $field )->get_error_message()
		);
		$this->assertSame(
			'Please provide a Promotion date on or before December 31, 2025.',
			$this->field_type->validate( '2026-01-01', $field )->get_error_message()
		);
	}

	/**
	 * @testdox Values are displayed using the site date format, in the site timezone.
	 *
	 * @testWith ["UTC", "F j, Y", "August 26, 2025"]
	 *           ["America/New_York", "Y-m-d", "2025-08-26"]
	 *           ["Pacific/Auckland", "Y-m-d", "2025-08-26"]
	 *
	 * @param string $timezone    The site timezone.
	 * @param string $date_format The site date format.
	 * @param string $expected    The expected formatted value.
	 */
	public function test_value_formatting( string $timezone, string $date_format, string $expected ) {
		update_option( 'timezone_string', $timezone );
		update_option( 'date_format', $date_format );

		$this->assertSame( $expected, $this->field_type->format_value( '2025-08-26', $this->date_field() ), 'The stored calendar date should never shift when it is formatted.' );
	}

	/**
	 * @testdox Values that are not a real calendar date are displayed as stored.
	 *
	 * @testWith ["2025-02-31"]
	 *           ["2025-13-01"]
	 *           ["not-a-date"]
	 *           [""]
	 *
	 * @param string $value The stored value.
	 */
	public function test_invalid_value_is_not_reformatted( string $value ) {
		update_option( 'date_format', 'F j, Y' );

		$this->assertSame( $value, $this->field_type->format_value( $value, $this->date_field() ), 'A value that is not a real date should be shown as stored rather than rolled forward.' );
	}

	/**
	 * Asserts whether validating a value against a field produces an error.
	 *
	 * @param bool   $has_errors Whether the value should be rejected.
	 * @param string $value      The submitted value.
	 * @param array  $field      The field to validate against.
	 */
	private function assert_rejects( bool $has_errors, string $value, array $field ) {
		$error = $this->field_type->validate( $value, $field );

		$this->assertSame( $has_errors, $error instanceof WP_Error, sprintf( 'Unexpected validation result for "%s".', $value ) );
	}

	/**
	 * Returns a registered date field, with the given constraints applied.
	 *
	 * @param array $constraints The min and/or max to apply.
	 * @return array
	 */
	private function date_field( array $constraints = array() ): array {
		return array_merge(
			array(
				'type'  => 'date',
				'label' => 'Promotion date',
			),
			$constraints
		);
	}

	/**
	 * Processes registration options through the field type, as field registration does.
	 *
	 * @param array $options The options supplied during field registration.
	 * @return array|false The processed field data, or false if an error should prevent registration.
	 */
	private function register( array $options ) {
		$field_data = array(
			'id'         => 'test/date-of-birth',
			'attributes' => array(),
		);

		return $this->field_type->process_options( $field_data, array_merge( array( 'id' => $field_data['id'] ), $options ) );
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
