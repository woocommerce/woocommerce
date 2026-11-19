<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RegulatoryFields\Formats;

/**
 * The Brazilian taxpayer number: a CPF for a person or a CNPJ for a company.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class BrazilTaxId implements FormatInterface {

	private const CPF_LENGTH  = 11;
	private const CNPJ_LENGTH = 14;

	/**
	 * Remove punctuation and spaces, and uppercase the letters a CNPJ can contain.
	 *
	 * @param string $value Value as typed by the shopper.
	 * @return string
	 */
	public function normalize( string $value ): string {
		return strtoupper( (string) preg_replace( '/[\s.\/-]/', '', $value ) );
	}

	/**
	 * Check that a normalized value is a CPF or a CNPJ with correct check digits.
	 *
	 * @param string $value Normalized value.
	 * @return bool
	 */
	public function is_valid( string $value ): bool {
		return $this->is_valid_cpf( $value ) || $this->is_valid_cnpj( $value );
	}

	/**
	 * Add the usual punctuation: 000.000.000-00 for a CPF, 00.000.000/0000-00 for a CNPJ.
	 *
	 * @param string $value Normalized value.
	 * @return string The formatted value, or the value unchanged when it is neither length.
	 */
	public function format( string $value ): string {
		if ( self::CPF_LENGTH === strlen( $value ) ) {
			return substr( $value, 0, 3 ) . '.' . substr( $value, 3, 3 ) . '.' . substr( $value, 6, 3 ) . '-' . substr( $value, 9, 2 );
		}

		if ( self::CNPJ_LENGTH === strlen( $value ) ) {
			return substr( $value, 0, 2 ) . '.' . substr( $value, 2, 3 ) . '.' . substr( $value, 5, 3 ) . '/' . substr( $value, 8, 4 ) . '-' . substr( $value, 12, 2 );
		}

		return $value;
	}

	/**
	 * Message shown when the value is not valid.
	 *
	 * @return string
	 */
	public function get_invalid_message(): string {
		return __( 'Please enter a valid CPF or CNPJ.', 'woocommerce' );
	}

	/**
	 * Check a CPF: 11 digits, the last two being check digits.
	 *
	 * @param string $value Normalized value.
	 * @return bool
	 */
	private function is_valid_cpf( string $value ): bool {
		if ( 1 !== preg_match( '/^\d{11}$/', $value ) || $this->is_single_repeated_character( $value ) ) {
			return false;
		}

		$first  = $this->get_check_digit( substr( $value, 0, 9 ), range( 10, 2 ) );
		$second = $this->get_check_digit( substr( $value, 0, 10 ), range( 11, 2 ) );

		return substr( $value, 9, 2 ) === $first . $second;
	}

	/**
	 * Check a CNPJ: 12 digits or letters followed by two check digits.
	 *
	 * Letters are allowed in the first 12 positions for CNPJs issued from July 2026.
	 *
	 * @param string $value Normalized value.
	 * @return bool
	 */
	private function is_valid_cnpj( string $value ): bool {
		if ( 1 !== preg_match( '/^[0-9A-Z]{12}\d{2}$/', $value ) || $this->is_single_repeated_character( $value ) ) {
			return false;
		}

		$first  = $this->get_check_digit( substr( $value, 0, 12 ), array( 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ) );
		$second = $this->get_check_digit( substr( $value, 0, 13 ), array( 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ) );

		return substr( $value, 12, 2 ) === $first . $second;
	}

	/**
	 * Calculate a mod 11 check digit.
	 *
	 * Each character is worth its ASCII code minus 48, so digits keep their value and A-Z are 17-42.
	 *
	 * @param string $characters Characters the digit is calculated from.
	 * @param int[]  $weights    One weight per character.
	 * @return string
	 */
	private function get_check_digit( string $characters, array $weights ): string {
		$sum = 0;

		foreach ( str_split( $characters ) as $index => $character ) {
			$sum += ( ord( $character ) - 48 ) * $weights[ $index ];
		}

		$remainder = $sum % 11;

		return (string) ( $remainder < 2 ? 0 : 11 - $remainder );
	}

	/**
	 * Whether the value is one character repeated, such as 00000000000, which passes the check digits but is never issued.
	 *
	 * @param string $value Normalized value.
	 * @return bool
	 */
	private function is_single_repeated_character( string $value ): bool {
		return 1 === count( array_unique( str_split( $value ) ) );
	}
}
