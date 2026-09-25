<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductCustoms;

use WC_Countries;
use WC_Data_Exception;

/**
 * Normalizes customs data shared by product entry points.
 */
final class CustomsDataValidator {

	/**
	 * Customs product props, in the order normalize_fields() validates them.
	 */
	public const FIELDS = array( 'customs_commodity_code', 'customs_country_of_origin', 'customs_description' );

	/**
	 * Normalizes every supplied customs field before callers apply changes.
	 *
	 * @since 11.3.0
	 *
	 * @param array $data Product properties, including any customs fields to update.
	 * @return array<string, string|null> Normalized customs fields only.
	 * @throws WC_Data_Exception When any supplied customs field is invalid.
	 */
	public static function normalize_fields( array $data ): array {
		$normalized = array();
		foreach ( self::FIELDS as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$normalized[ $field ] = self::{ str_replace( 'customs_', 'normalize_', $field ) }( $data[ $field ] );
			}
		}
		return $normalized;
	}

	/**
	 * Removes commodity code punctuation and spacing while preserving leading zeroes.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $value Commodity code, or null to clear it.
	 * @return string|null
	 * @throws WC_Data_Exception When the code does not contain six to fourteen ASCII digits.
	 */
	public static function normalize_commodity_code( $value ): ?string {
		if ( null === $value || ( is_string( $value ) && preg_match( '/\A\s*\z/u', $value ) ) ) {
			return null;
		}

		if ( is_string( $value ) && preg_match( '/\A[0-9\p{P}\s]+\z/u', $value ) ) {
			$normalized = preg_replace( '/[\p{P}\s]+/u', '', $value ) ?? '';
			if ( preg_match( '/\A[0-9]{6,14}\z/', $normalized ) ) {
				return $normalized;
			}
		}

		throw new WC_Data_Exception(
			'woocommerce_product_invalid_customs_commodity_code',
			__( 'The customs commodity code must contain 6 to 14 digits, with optional punctuation or spaces.', 'woocommerce' ), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped when output.
			400
		);
	}

	/**
	 * Normalizes an origin country against the full WooCommerce country list.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $value Country code, or null to clear it.
	 * @return string|null
	 * @throws WC_Data_Exception When the country code is not recognized.
	 */
	public static function normalize_country_of_origin( $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		if ( is_string( $value ) ) {
			// Unicode-aware trim, so a pasted non-breaking space counts as empty.
			$code = strtoupper( (string) preg_replace( '/^\s+|\s+$/u', '', $value ) );
			if ( '' === $code ) {
				return null;
			}
			// WC()->countries is only set once WooCommerce has initialized.
			$countries = WC()->countries instanceof WC_Countries ? WC()->countries : new WC_Countries();
			if ( $countries->country_exists( $code ) ) {
				return $code;
			}
		}

		throw new WC_Data_Exception(
			'woocommerce_product_invalid_customs_country_of_origin',
			__( 'Select a valid country of origin.', 'woocommerce' ), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped when output.
			400
		);
	}

	/**
	 * Normalizes a plain text customs description of up to thirty-five Unicode code points.
	 * Only letters, digits, spaces, punctuation and printable ASCII symbols are allowed, so emoji (including keycap and variation-selector sequences) and symbols such as ™ are rejected.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $value Plain text description, or null to clear it.
	 * @return string|null
	 * @throws WC_Data_Exception When the description is not a string, has disallowed characters, or exceeds the length limit.
	 */
	public static function normalize_description( $value ): ?string {
		if ( null === $value || '' === $value ) {
			return null;
		}

		if ( is_string( $value ) && '' !== wp_check_invalid_utf8( $value ) ) {
			$text = wp_strip_all_tags( $value, true );
			if ( '' === $text ) {
				return null;
			}
			if ( mb_strlen( $text ) <= 35 && ! preg_match( '/[^\x20-\x7E\p{L}\p{Mn}\p{Mc}\p{N}\p{P}\s]|[\x{FE00}-\x{FE0F}\x{E0100}-\x{E01EF}]/u', $text ) ) {
				return $text;
			}
		}

		throw new WC_Data_Exception(
			'woocommerce_product_invalid_customs_description',
			__( 'The customs description must be plain text with no more than 35 characters, without emoji or special symbols.', 'woocommerce' ), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped when output.
			400
		);
	}

	/**
	 * Lightly normalizes a stored customs value read from the database, without validating it.
	 *
	 * @since 11.3.0
	 *
	 * @param string $prop  Customs prop name, such as customs_country_of_origin.
	 * @param mixed  $value Stored value.
	 * @return string|null
	 */
	public static function normalize_stored_value( string $prop, $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}

		return 'customs_country_of_origin' === $prop ? strtoupper( $value ) : $value;
	}
}
