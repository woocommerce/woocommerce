<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use InvalidArgumentException;
use RuntimeException;

/**
 * The user-entered cash drawer name from the POS app settings.
 *
 * The sanitized spelling is kept for display and history. Comparisons use a case-insensitive key,
 * so "Front counter" and "front counter" identify the same drawer within a site.
 *
 * @since 11.3.0
 */
final class DrawerName {

	/**
	 * Maximum length in characters after sanitization.
	 */
	public const MAX_LENGTH = 128;

	/**
	 * Sanitize a drawer name and validate it.
	 *
	 * @since 11.3.0
	 *
	 * @param string $value Name as entered.
	 * @return string Sanitized display spelling.
	 * @throws InvalidArgumentException When the name is blank, too long or not valid UTF-8.
	 */
	public static function normalize( string $value ): string {
		$trimmed = preg_replace( '/^[\s\p{Z}\x{FEFF}]+|[\s\p{Z}\x{FEFF}]+$/uD', '', $value );

		if ( null === $trimmed ) {
			throw new InvalidArgumentException( 'The drawer name is not valid UTF-8.' );
		}
		$trimmed = trim( (string) preg_replace( '/[\x00-\x1F\x7F]/', '', sanitize_text_field( $trimmed ) ) );
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
	 * A hash of the lowercase name: it has a fixed width even when lowercasing adds characters, and SQL
	 * compares it exactly whatever the column collation, as the PHP binding checks do.
	 *
	 * @since 11.3.0
	 *
	 * @param string $name Normalized drawer name.
	 * @return string 64 hex characters.
	 * @throws RuntimeException When the mbstring extension is missing; WooCommerce does not require it.
	 */
	public static function key( string $name ): string {
		// No fallback: lowercasing without mbstring would give other keys for the same non-ASCII name.
		if ( ! wc_get_container()->get( LegacyProxy::class )->call_function( 'function_exists', 'mb_strtolower' ) ) {
			throw new RuntimeException( 'Comparing drawer names needs the mbstring PHP extension.' );
		}
		return hash( 'sha256', mb_strtolower( $name, 'UTF-8' ) );
	}
}
