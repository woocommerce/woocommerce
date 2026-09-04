<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes;

use WP_Error;

/**
 * The "date" additional checkout field type.
 *
 * Values are calendar dates in YYYY-MM-DD format with no time or timezone component.
 */
class DateFieldType extends AbstractFieldType {

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
	 * Validates that a submitted value is a real calendar date.
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
}
