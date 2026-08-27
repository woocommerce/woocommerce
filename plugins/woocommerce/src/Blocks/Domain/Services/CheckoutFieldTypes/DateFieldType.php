<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes;

use WP_Error;

/**
 * The "date" additional checkout field type.
 *
 * Values are calendar dates in YYYY-MM-DD format with no time or timezone component. The optional
 * min/max constraints are either absolute dates or ISO 8601-2 durations relative to today, which
 * are resolved every time they are read so they stay correct behind a page cache.
 */
class DateFieldType extends AbstractFieldType {

	/**
	 * Matches a date in YYYY-MM-DD format.
	 */
	private const ABSOLUTE_DATE = '/^\d{4}-\d{2}-\d{2}$/';

	/**
	 * Matches a canonical signed ISO 8601-2 duration, capturing its sign, years, months and days.
	 */
	private const DURATION = '/^(-?)P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)?$/';

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

			$normalized = $this->normalize_constraint( $options[ $constraint ] );

			if ( null === $normalized ) {
				return $this->registration_error(
					$id,
					sprintf( 'The "%s" property of a "date" field must be a date in YYYY-MM-DD format, an ISO 8601-2 duration such as "P1D" or "-P18Y", or a DateInterval.', $constraint ),
					'11.2.0'
				);
			}

			$field_data[ $constraint ] = $normalized;
		}

		// Two constraints of the same kind usually keep their order whenever they are resolved, so an impossible
		// range can be caught here. A mix of absolute and duration can be valid today and not tomorrow, so it is
		// left to per-request validation, as is a duration pair that only inverts when a short February is in the way.
		if ( isset( $field_data['min'], $field_data['max'] )
			&& $this->is_absolute_date( $field_data['min'] ) === $this->is_absolute_date( $field_data['max'] )
			&& $this->resolve_constraint( $field_data['min'] ) > $this->resolve_constraint( $field_data['max'] ) ) {
			return $this->registration_error(
				$id,
				sprintf( 'The "min" constraint (%s) must not resolve to a date later than the "max" constraint (%s).', $field_data['min'], $field_data['max'] ),
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

		$constraints = $this->get_constraints( $field );

		// Both sides are YYYY-MM-DD, so a string comparison orders them correctly.
		if ( $constraints['min'] && $value < $constraints['min'] ) {
			return new WP_Error(
				'woocommerce_invalid_checkout_field',
				sprintf(
					/* translators: 1: is the field label, 2: is the earliest date allowed */
					__( 'Please provide a %1$s on or after %2$s.', 'woocommerce' ),
					$field['label'],
					$this->format_value( $constraints['min'], $field )
				)
			);
		}

		if ( $constraints['max'] && $value > $constraints['max'] ) {
			return new WP_Error(
				'woocommerce_invalid_checkout_field',
				sprintf(
					/* translators: 1: is the field label, 2: is the latest date allowed */
					__( 'Please provide a %1$s on or before %2$s.', 'woocommerce' ),
					$field['label'],
					$this->format_value( $constraints['max'], $field )
				)
			);
		}

		return null;
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
	 * Adds the resolved min/max constraints as input attributes for woocommerce_form_field().
	 *
	 * These forms are rendered server side, so the constraints are resolved here rather than by the client.
	 *
	 * @param array $form_field The woocommerce_form_field() arguments built from the field.
	 * @return array The updated arguments.
	 */
	public function prepare_form_field( array $form_field ): array {
		$form_field['custom_attributes'] = array_filter( $this->get_constraints( $form_field ) );

		return $form_field;
	}

	/**
	 * Returns the resolved min and max dates for a date field.
	 *
	 * @param array $field The field.
	 * @return array Array with "min" and "max" keys, each a YYYY-MM-DD date or null when unconstrained.
	 */
	public function get_constraints( array $field ): array {
		return array(
			'min' => isset( $field['min'] ) ? $this->resolve_constraint( $field['min'] ) : null,
			'max' => isset( $field['max'] ) ? $this->resolve_constraint( $field['max'] ) : null,
		);
	}

	/**
	 * Resolves a date field min/max constraint to a YYYY-MM-DD date in the store's timezone.
	 *
	 * Durations are resolved every time they are read, never at registration, so they follow the current
	 * date rather than freezing into whatever markup a page cache stored.
	 *
	 * @param mixed                   $value     The constraint to resolve.
	 * @param \DateTimeInterface|null $reference Date a duration is relative to. Defaults to today in the store timezone.
	 * @return string|null The resolved date, or null if the constraint could not be parsed.
	 */
	public function resolve_constraint( $value, $reference = null ) {
		$constraint = $this->normalize_constraint( $value );

		if ( null === $constraint || $this->is_absolute_date( $constraint ) ) {
			return $constraint;
		}

		preg_match( self::DURATION, $constraint, $matches );

		$sign   = '-' === $matches[1] ? -1 : 1;
		$months = $sign * ( (int) ( $matches[2] ?? 0 ) * 12 + (int) ( $matches[3] ?? 0 ) );
		$days   = $sign * (int) ( $matches[4] ?? 0 );
		$today  = new \DateTimeImmutable( $reference ? $reference->format( 'Y-m-d' ) : 'today', wp_timezone() );

		// Months are applied with the day clamped to the target month, then days are added. This is Temporal's
		// default "constrain" overflow, which the client uses; PHP would instead roll 31 January + P1M into March.
		$target   = $today->modify( 'first day of this month' )->modify( sprintf( '%+d months', $months ) );
		$resolved = $target->setDate(
			(int) $target->format( 'Y' ),
			(int) $target->format( 'n' ),
			min( (int) $today->format( 'j' ), (int) $target->format( 't' ) )
		);

		return $resolved->modify( sprintf( '%+d days', $days ) )->format( 'Y-m-d' );
	}

	/**
	 * Normalizes a date field min/max constraint to the form stored on the field and sent to the client.
	 *
	 * A constraint is either an absolute YYYY-MM-DD date, an ISO 8601-2 duration relative to today ("P1D",
	 * "-P18Y"), or a DateInterval. Durations are normalized to a canonical signed ISO 8601-2 string, which
	 * is what the client parses.
	 *
	 * @param mixed $value The constraint as supplied at registration.
	 * @return string|null The normalized constraint, or null if it could not be parsed.
	 */
	private function normalize_constraint( $value ) {
		$sign = 1;

		if ( is_string( $value ) ) {
			$value = trim( $value );

			if ( $this->is_absolute_date( $value ) ) {
				return null === $this->parse_date( $value ) ? null : $value;
			}

			// DateInterval implements ISO 8601-1, which has no sign, so the ISO 8601-2 sign is peeled off first.
			$sign = '-' === substr( $value, 0, 1 ) ? -1 : 1;

			try {
				$value = new \DateInterval( ltrim( $value, '+-' ) );
			} catch ( \Exception $e ) {
				return null;
			}
		}

		if ( ! $value instanceof \DateInterval ) {
			return null;
		}

		// A date field has no sub-day precision, so a time component means the caller meant something else.
		if ( $value->h || $value->i || $value->s || $value->f ) {
			return null;
		}

		if ( $value->invert ) {
			$sign = -$sign;
		}

		$units = array_filter(
			array(
				'Y' => $value->y,
				'M' => $value->m,
				'D' => $value->d,
			)
		);

		// DateInterval::createFromDateString() reports "-5 days" as a negative field rather than by setting invert,
		// so the sign is read off the values. ISO 8601-2 signs the whole duration, so a mix of directions such as
		// "+1 month -3 days" has no equivalent.
		$negative = count( array_filter( $units, fn( $amount ) => 0 > $amount ) );

		if ( $negative && count( $units ) !== $negative ) {
			return null;
		}

		if ( ! $units ) {
			return 'P0D';
		}

		$duration = '';

		foreach ( $units as $symbol => $amount ) {
			$duration .= abs( $amount ) . $symbol;
		}

		return 0 > ( $negative ? -$sign : $sign ) ? '-P' . $duration : 'P' . $duration;
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
		return is_string( $value ) && 1 === preg_match( self::ABSOLUTE_DATE, $value );
	}
}
