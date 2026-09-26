<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

use DomainException;
use InvalidArgumentException;
use OverflowException;

/**
 * Exact cash amounts as integer minor units.
 *
 * Amounts travel as decimal strings in major units (like the order and refund APIs) and are stored and
 * summed as integers at the precision captured when the session opened, so no float math touches them.
 *
 * @since 11.3.0
 */
final class CashMoney {

	/**
	 * Largest single amount accepted, in minor units.
	 */
	public const MAX_AMOUNT_MINOR_UNITS = 999999999999999;

	/**
	 * Largest absolute total, in minor units. Far below PHP_INT_MAX, so adding two in-range values cannot overflow.
	 */
	public const MAX_TOTAL_MINOR_UNITS = 99999999999999999;

	/**
	 * Largest supported currency precision.
	 */
	public const MAX_PRECISION = 8;

	/**
	 * Parse a client-supplied nonnegative decimal string.
	 *
	 * Fractional digits beyond the precision are rejected, never rounded.
	 *
	 * @since 11.3.0
	 *
	 * @param string $value     Decimal string such as "148.50".
	 * @param int    $precision Number of fractional digits of the currency.
	 * @return int Minor units.
	 * @throws InvalidArgumentException When the value is malformed or too precise.
	 * @throws OverflowException When the value is above the supported maximum.
	 */
	public static function parse( string $value, int $precision ): int {
		$parts = self::split( $value );
		if ( null === $parts ) {
			throw new InvalidArgumentException( 'The amount must be a nonnegative decimal string such as "10.50".' );
		}
		if ( strlen( $parts[1] ) > $precision ) {
			throw new InvalidArgumentException( 'The amount has more decimal places than the currency allows.' );
		}

		$minor_units = self::to_minor_units( $parts[0], $parts[1], $precision );
		if ( null === $minor_units ) {
			throw new OverflowException( 'The amount is too large.' );
		}
		return $minor_units;
	}

	/**
	 * Parse an amount read from an order or refund.
	 *
	 * Trailing zeros beyond the precision are accepted. Nonzero extra digits mean the source does not
	 * match the session precision; that is reported instead of rounded.
	 *
	 * @since 11.3.0
	 *
	 * @param string $value     Decimal string.
	 * @param int    $precision Number of fractional digits of the session currency.
	 * @return int Minor units.
	 * @throws InvalidArgumentException When the value is malformed.
	 * @throws DomainException When the value has nonzero digits beyond the precision.
	 * @throws OverflowException When the value is above the supported maximum.
	 */
	public static function parse_source( string $value, int $precision ): int {
		$parts = self::split( $value );
		if ( null === $parts ) {
			throw new InvalidArgumentException( 'The amount must be a nonnegative decimal string such as "10.50".' );
		}
		if ( '' !== trim( (string) substr( $parts[1], $precision ), '0' ) ) {
			throw new DomainException( 'The source amount has more decimal places than the session currency.' );
		}

		$minor_units = self::to_minor_units( $parts[0], (string) substr( $parts[1], 0, $precision ), $precision );
		if ( null === $minor_units ) {
			throw new OverflowException( 'The amount is too large.' );
		}
		return $minor_units;
	}

	/**
	 * Canonical form of a decimal string, independent of precision, for comparing request payloads.
	 *
	 * @since 11.3.0
	 *
	 * @param string $value Decimal string.
	 * @return string Value without leading zeros or trailing fractional zeros.
	 * @throws InvalidArgumentException When the value is malformed.
	 */
	public static function canonical( string $value ): string {
		$parts = self::split( $value );
		if ( null === $parts ) {
			throw new InvalidArgumentException( 'The amount must be a nonnegative decimal string such as "10.50".' );
		}

		$integer  = ltrim( $parts[0], '0' );
		$fraction = rtrim( $parts[1], '0' );

		return ( '' === $integer ? '0' : $integer ) . ( '' === $fraction ? '' : '.' . $fraction );
	}

	/**
	 * Format minor units as a decimal string with exactly the given precision.
	 *
	 * @since 11.3.0
	 *
	 * @param int $minor_units Amount in minor units, may be negative.
	 * @param int $precision   Number of fractional digits.
	 * @return string
	 */
	public static function format( int $minor_units, int $precision ): string {
		$sign   = $minor_units < 0 ? '-' : '';
		$digits = str_pad( (string) abs( $minor_units ), $precision + 1, '0', STR_PAD_LEFT );

		if ( 0 === $precision ) {
			return $sign . $digits;
		}

		return $sign . substr( $digits, 0, -$precision ) . '.' . substr( $digits, -$precision );
	}

	/**
	 * Add two amounts, rejecting results outside the supported total range.
	 *
	 * @since 11.3.0
	 *
	 * @param int $a Minor units.
	 * @param int $b Minor units.
	 * @return int
	 * @throws OverflowException When the result is out of range.
	 */
	public static function add( int $a, int $b ): int {
		if ( abs( $a ) > self::MAX_TOTAL_MINOR_UNITS || abs( $b ) > self::MAX_TOTAL_MINOR_UNITS ) {
			throw new OverflowException( 'The cash total is too large.' );
		}

		$sum = $a + $b;
		if ( abs( $sum ) > self::MAX_TOTAL_MINOR_UNITS ) {
			throw new OverflowException( 'The cash total is too large.' );
		}

		return $sum;
	}

	/**
	 * Split a strict decimal string into integer and fraction digits.
	 *
	 * @param string $value Decimal string.
	 * @return array{0: string, 1: string}|null Null when the value is not a plain nonnegative decimal.
	 */
	private static function split( string $value ): ?array {
		if ( ! preg_match( '/^([0-9]+)(?:\.([0-9]+))?$/D', $value, $matches ) ) {
			return null;
		}

		return array( $matches[1], $matches[2] ?? '' );
	}

	/**
	 * Combine digit strings into minor units.
	 *
	 * @param string $integer   Integer digits.
	 * @param string $fraction  Fraction digits, at most $precision long.
	 * @param int    $precision Number of fractional digits.
	 * @return int|null Null when the value is above the supported maximum.
	 */
	private static function to_minor_units( string $integer, string $fraction, int $precision ): ?int {
		$digits = ltrim( $integer . str_pad( $fraction, $precision, '0' ), '0' );

		if ( strlen( $digits ) > strlen( (string) self::MAX_AMOUNT_MINOR_UNITS ) ) {
			return null;
		}

		return (int) $digits;
	}
}
