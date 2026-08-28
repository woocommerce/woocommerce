<?php
/**
 * LegacyHash class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping;

defined( 'ABSPATH' ) || exit;

/**
 * Reproduces the legacy Back In Stock Notifications unsubscribe token, and shapes the
 * value the migration stores for it.
 *
 * Pure: no database or WordPress hook access, only the WordPress hashing functions.
 */
final class LegacyHash {

	/**
	 * Reproduce `WC_BIS_Notification_Data::get_hash()` for one legacy notification.
	 *
	 * Returns null when either secret is missing or `openssl_encrypt()` fails, so the
	 * caller can count the row separately rather than treat it as a lost link.
	 *
	 * @param int    $legacy_id           Legacy notification id.
	 * @param int    $product_id          Product id.
	 * @param int    $legacy_create_date  Legacy `create_date`, as an integer timestamp.
	 * @param string $hash_key            Legacy per-notification `_hash_key` meta value.
	 * @param string $hash_iv             Legacy per-notification `_hash_iv` meta value.
	 * @return string|null
	 */
	public static function compute( int $legacy_id, int $product_id, int $legacy_create_date, string $hash_key, string $hash_iv ): ?string {
		if ( '' === $hash_key || '' === $hash_iv ) {
			return null;
		}

		$input     = "{$legacy_id}-{$product_id}-{$legacy_create_date}";
		$encrypted = openssl_encrypt( $input, 'AES-256-CBC', $hash_key, 0, $hash_iv );

		if ( false === $encrypted ) {
			return null;
		}

		return hash( 'sha256', $encrypted );
	}

	/**
	 * Build the `_wc_bis_legacy_unsub_hash` meta value stored for one legacy id.
	 *
	 * The raw token is never stored: only its `wp_fast_hash()` digest, prefixed with the
	 * legacy id so several legacy rows can adopt the same Core notification.
	 *
	 * @param int    $legacy_id Legacy notification id.
	 * @param string $token     Token produced by self::compute().
	 * @return string
	 */
	public static function to_meta_value( int $legacy_id, string $token ): string {
		return "{$legacy_id}:" . wp_fast_hash( $token );
	}

	/**
	 * Split a stored `_wc_bis_legacy_unsub_hash` value into its legacy id and digest.
	 *
	 * Splits on the first colon only, so the boundary never depends on the hash
	 * format's alphabet.
	 *
	 * @param string $meta_value Stored meta value.
	 * @return array{0:int,1:string}|null Null when the value is not in `id:hash` shape.
	 */
	public static function parse( string $meta_value ): ?array {
		$parts = explode( ':', $meta_value, 2 );

		if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] || ! ctype_digit( $parts[0] ) ) {
			return null;
		}

		return array( (int) $parts[0], $parts[1] );
	}

	/**
	 * Verify a token presented by an incoming unsubscribe link against a stored meta value.
	 *
	 * @param string $meta_value Stored `_wc_bis_legacy_unsub_hash` value.
	 * @param string $token      Token recomputed from the link's request parameters.
	 * @return bool
	 */
	public static function verify( string $meta_value, string $token ): bool {
		$parsed = self::parse( $meta_value );

		if ( null === $parsed ) {
			return false;
		}

		return wp_verify_fast_hash( $token, $parsed[1] );
	}
}
