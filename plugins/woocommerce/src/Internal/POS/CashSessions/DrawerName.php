<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;

/**
 * The user-entered cash drawer name from the POS app settings.
 *
 * The trimmed spelling is kept for display and history. Comparisons use a case-insensitive key,
 * so "Front counter" and "front counter" identify the same drawer within a site.
 *
 * @since 11.3.0
 */
final class DrawerName {

	/**
	 * Maximum length in characters after trimming.
	 */
	public const MAX_LENGTH = 128;

	/**
	 * Trim a drawer name and validate it.
	 *
	 * @since 11.3.0
	 *
	 * @param string $value Name as entered.
	 * @return string Trimmed display spelling.
	 * @throws InvalidArgumentException When the name is blank, too long or not valid UTF-8.
	 */
	public static function normalize( string $value ): string {
		$trimmed = preg_replace( '/^[\s\p{Z}\x{FEFF}]+|[\s\p{Z}\x{FEFF}]+$/uD', '', $value );

		if ( null === $trimmed ) {
			throw new InvalidArgumentException( 'The drawer name is not valid UTF-8.' );
		}
		if ( '' === $trimmed ) {
			throw new InvalidArgumentException( 'The drawer name cannot be blank.' );
		}
		if ( mb_strlen( $trimmed, 'UTF-8' ) > self::MAX_LENGTH ) {
			throw new InvalidArgumentException( 'The drawer name is too long.' );
		}

		return $trimmed;
	}

	/**
	 * Comparison key for a normalized drawer name.
	 *
	 * @since 11.3.0
	 *
	 * @param string $name Normalized drawer name.
	 * @return string
	 */
	public static function key( string $name ): string {
		return mb_strtolower( $name, 'UTF-8' );
	}
}
