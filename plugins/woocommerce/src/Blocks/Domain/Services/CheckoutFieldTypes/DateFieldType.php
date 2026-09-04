<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes;

use WP_Error;

/**
 * The "date" additional checkout field type.
 *
 * Values are calendar dates in YYYY-MM-DD format with no time or timezone component. Field
 * also accept min/max constraints as absolute dates (i.e. YYYY-MM-DD) or relative
 * ISO 8601-2 periods (e.g. -P2Y, P1M).
 */
class DateFieldType extends AbstractFieldType {

	/**
	 * Matches a YYYY-MM-DD date with valid month and day ranges.
	 *
	 * Undelimited because a JSON Schema pattern takes a bare expression; the PHP uses add delimiters.
	 */
	private const DATE_PATTERN = '\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])';

	/**
	 * Processes the options for a date field and returns the new field_options array.
	 *
	 * @param array $field_data The field data array to be updated.
	 * @param array $options    The options supplied during field registration.
	 * @return array|false The updated $field_data array, or false if an error was encountered.
	 */
	protected function process_type_options( array $field_data, array $options ) {
		$id = $options['id'];

		foreach ( array( 'min', 'max' ) as $constraint ) {
			// Both are optional. Drop the key entirely rather than carrying a null through to the client.
			if ( ! isset( $options[ $constraint ] ) ) {
				unset( $field_data[ $constraint ] );
				continue;
			}

			$value = $options[ $constraint ];

			if ( ! is_string( $value ) ) {
				return $this->registration_error(
					$id,
					sprintf( 'The "%s" property of a "date" field must be a date in YYYY-MM-DD format or an ISO 8601-2 duration such as "P1D" or "-P18Y".', $constraint ),
					'11.2.0'
				);
			}

			$value = trim( $value );

			if ( $this->is_absolute_date( $value ) ) {
				if ( null === $this->parse_date( $value ) ) {
					return $this->registration_error(
						$id,
						sprintf( 'The "%s" property of a "date" field must be a real calendar date, and "%s" is not.', $constraint, $value ),
						'11.2.0'
					);
				}

				$field_data[ $constraint ] = $value;
				continue;
			}

			// DateInterval implements ISO 8601-1, which has no sign, so the ISO 8601-2 sign is peeled off first.
			$sign = 1;
			$body = $value;

			if ( '' !== $body && in_array( $body[0], array( '+', '-' ), true ) ) {
				$sign = '-' === $body[0] ? -1 : 1;
				$body = substr( $body, 1 );
			}

			try {
				$interval = new \DateInterval( $body );
			} catch ( \Exception $e ) {
				return $this->registration_error(
					$id,
					sprintf( 'The "%s" property of a "date" field must be a date in YYYY-MM-DD format or an ISO 8601-2 duration such as "P1D" or "-P18Y", and "%s" is neither.', $constraint, $value ),
					'11.2.0'
				);
			}

			// A date field has no sub-day precision, so a time component means the caller meant something else.
			if ( $interval->h || $interval->i || $interval->s || $interval->f ) {
				return $this->registration_error(
					$id,
					sprintf( 'The "%s" property of a "date" field must be a duration in whole days, but "%s" includes a time component.', $constraint, $value ),
					'11.2.0'
				);
			}

			// The body parsed as-is, so re-attaching the sign (dropping an explicit "+") makes it canonical
			// ISO 8601-2: at most one leading "-", which is all resolve_constraint() and the client expect.
			$field_data[ $constraint ] = ( -1 === $sign ? '-' : '' ) . $body;
		}

		$min = $field_data['min'] ?? null;
		$max = $field_data['max'] ?? null;

		// When having 2 constraints, we can end up in impossible cases, in which the min is later than the max.
		if ( isset( $min, $max )
			&& $this->is_absolute_date( $min ) === $this->is_absolute_date( $max )
			&& $this->resolve_constraint( $min ) > $this->resolve_constraint( $max ) ) {
			return $this->registration_error(
				$id,
				sprintf( 'The "min" constraint (%s) must not resolve to a date later than the "max" constraint (%s).', $min, $max ),
				'11.2.0'
			);
		}

		return $field_data;
	}

	/**
	 * Trims whitespace from submitted date values before they are validated and stored.
	 *
	 * @param mixed $value The submitted value.
	 * @param array $field The field.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, array $field ) {
		return is_string( $value ) ? trim( $value ) : $value;
	}

	/**
	 * Validates that a submitted value is a real calendar date within the field's min/max constraints.
	 *
	 * @param mixed $value The submitted value.
	 * @param array $field The field.
	 * @return WP_Error|null Error if the value is not valid, null otherwise.
	 */
	public function validate( $value, array $field ) {
		// An empty value is not a type error. Required fields are handled by the field's validation callback.
		if ( null === $value || '' === $value ) {
			return null;
		}

		if ( ! is_string( $value ) || null === $this->parse_date( $value ) ) {
			return new WP_Error(
				'woocommerce_invalid_checkout_field',
				sprintf(
					/* translators: %s: is the field label */
					__( 'Please provide a valid %s in YYYY-MM-DD format.', 'woocommerce' ),
					$field['label']
				)
			);
		}

		[ 'min' => $min, 'max' => $max ] = $this->get_constraints( $field );

		// Both sides are YYYY-MM-DD, so a string comparison orders them correctly.
		if ( $min && $value < $min ) {
			return new WP_Error(
				'woocommerce_invalid_checkout_field',
				sprintf(
					/* translators: 1: is the field label, 2: is the earliest date allowed */
					__( 'Please provide a %1$s on or after %2$s.', 'woocommerce' ),
					$field['label'],
					$this->format_value( $min, $field )
				)
			);
		}

		if ( $max && $value > $max ) {
			return new WP_Error(
				'woocommerce_invalid_checkout_field',
				sprintf(
					/* translators: 1: is the field label, 2: is the latest date allowed */
					__( 'Please provide a %1$s on or before %2$s.', 'woocommerce' ),
					$field['label'],
					$this->format_value( $max, $field )
				)
			);
		}

		return null;
	}

	/**
	 * Converts a YYYY-MM-DD date to the YYYYMMDD integer used in schema validation.
	 *
	 * @param mixed $value The value.
	 * @param array $field The field.
	 * @return int|null The date as YYYYMMDD, or null when there is no date to compare.
	 */
	public function to_document_value( $value, array $field ) {
		$date = is_string( $value ) && '' !== $value ? $this->parse_date( $value ) : null;

		// Null rather than 0, so a blank date is skipped by numeric keywords instead of ordered by them.
		return null === $date ? null : (int) $date->format( 'Ymd' );
	}

	/**
	 * Formats a stored YYYY-MM-DD date using the store's date format.
	 *
	 * @param mixed $value The stored value.
	 * @param array $field The field.
	 * @return mixed The formatted date, or the value unchanged if it could not be parsed.
	 */
	public function format_value( $value, array $field ) {
		$date = is_string( $value ) ? $this->parse_date( $value ) : null;

		if ( null === $date ) {
			return $value;
		}

		$formatted = wp_date( wc_date_format(), $date->getTimestamp() );

		return false === $formatted ? $value : $formatted;
	}

	/**
	 * Constrains date values to YYYY-MM-DD strings in the REST API value schema.
	 *
	 * @param array $field_schema The schema built for the field so far.
	 * @param array $field        The field.
	 * @return array The updated schema.
	 */
	public function prepare_value_schema( array $field_schema, array $field ): array {
		// Optional, because an empty value is not a type error.
		$field_schema['pattern'] = '^(' . self::DATE_PATTERN . ')?$';

		return $field_schema;
	}

	/**
	 * Adds the resolved min/max constraints as input attributes for woocommerce_form_field().
	 *
	 * These forms are rendered server side, so the constraints are resolved here rather than by the client.
	 *
	 * @param array $form_field The woocommerce_form_field() arguments built from the field.
	 * @return array The updated arguments.
	 */
	public function prepare_form_field( array $form_field ): array {
		$form_field['custom_attributes'] = array_merge(
			isset( $form_field['custom_attributes'] ) && is_array( $form_field['custom_attributes'] ) ? $form_field['custom_attributes'] : array(),
			array_filter( $this->get_constraints( $form_field ) )
		);

		return $form_field;
	}

	/**
	 * Returns the resolved min and max dates for a date field.
	 *
	 * @param array $field The field.
	 * @return array Array with "min" and "max" keys, each a YYYY-MM-DD date or null when unconstrained.
	 */
	private function get_constraints( array $field ): array {
		return array(
			'min' => isset( $field['min'] ) ? $this->resolve_constraint( $field['min'] ) : null,
			'max' => isset( $field['max'] ) ? $this->resolve_constraint( $field['max'] ) : null,
		);
	}

	/**
	 * Resolves a date field min/max constraint to a YYYY-MM-DD date in the store's timezone.
	 *
	 * Durations are resolved at read time, so they follow the current date rather
	 * than freezing into whatever markup a page cache stored.
	 *
	 * We use DateInterval to resolve durations but it has 2 issues that we must solve here:
	 * 1. Negative constraints are not supported with this version, so we must account for them ourselves.
	 * 2. DateInterval has an inconsistent behavior compared to ISO 8601-2 in which adding
	 * a month to a date equals adding 31 days instead of a calendar month,
	 * resulting in P1M after Jan 31 giving you Mar 3 instead of Feb 28.
	 *
	 * @param string                  $value     The constraint, as validated and normalized at registration.
	 * @param \DateTimeInterface|null $reference Date a duration is relative to. Defaults to today in the store timezone.
	 * @return string The resolved date.
	 */
	private function resolve_constraint( string $value, $reference = null ): string {
		if ( $this->is_absolute_date( $value ) ) {
			return $value;
		}

		$sign     = '-' === $value[0] ? -1 : 1;
		$interval = new \DateInterval( ltrim( $value, '-' ) );
		$today    = new \DateTimeImmutable( $reference ? $reference->format( 'Y-m-d' ) : 'today', wp_timezone() );

		// Handles the DateInterval bug in which months days overflow to the next month instead of staying in the current month.
		// P1M to Jan 31 gives you Mar 3 instead of Feb 28.
		// So we deconstruct the period into years, months, and days, and then add them back separately.
		$months = $sign * ( $interval->y * 12 + $interval->m );
		$days   = $sign * $interval->d;
		// Adding x months to today and asking for the last day of that month, then clamping the day to today if it exceeds it.
		$end_of_month = $today->modify( sprintf( 'last day of %+d months', $months ) );
		$resolved     = $end_of_month->setDate(
			(int) $end_of_month->format( 'Y' ),
			(int) $end_of_month->format( 'n' ),
			// Asking for min will get us the correct end of month if we're about to overflow, or the actual date.
			// Because adding P1M to Jan 17 should give you Feb 17 but adding P1M to Jan 28-31 should give you Feb 28 (or Feb 29 on leap years).
			min( (int) $today->format( 'j' ), (int) $end_of_month->format( 'j' ) )
		);

		// Add the days back from the original interval.
		return $resolved->modify( sprintf( '%+d days', $days ) )->format( 'Y-m-d' );
	}

	/**
	 * Parses a YYYY-MM-DD date in the store's timezone.
	 *
	 * @param string $value The date to parse.
	 * @return \DateTimeImmutable|null The date, or null if it is not a real calendar date.
	 */
	private function parse_date( string $value ) {
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );

		// Comparing the round trip rejects impossible dates such as 2026-02-31, which PHP would roll forward.
		return $date && $date->format( 'Y-m-d' ) === $value ? $date : null;
	}

	/**
	 * Returns true if the given value is a date in YYYY-MM-DD format.
	 *
	 * @param mixed $value The value to check.
	 * @return bool
	 */
	private function is_absolute_date( $value ): bool {
		return is_string( $value ) && 1 === preg_match( '/^' . self::DATE_PATTERN . '$/', $value );
	}
}
