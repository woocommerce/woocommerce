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
	 * Reproduce `WC_BIS_Notification_Data::get_verification_hash()` for one legacy notification.
	 *
	 * Same null-not-error convention as self::compute(): a row missing either secret, or one
	 * whose encryption fails, yields null so the caller can skip it rather than store a
	 * digest no link will ever match.
	 *
	 * @param string $code Legacy per-notification `_verification_code` meta value.
	 * @param string $key  Legacy per-notification `_verification_key` meta value.
	 * @param string $iv   Legacy per-notification `_verification_iv` meta value.
	 * @return string|null
	 */
	public static function compute_verification( string $code, string $key, string $iv ): ?string {
		if ( '' === $code || '' === $key || '' === $iv ) {
			return null;
		}

		$encrypted = openssl_encrypt( $code, 'AES-256-CBC', $key, 0, $iv );

		if ( false === $encrypted ) {
			return null;
		}

		return hash( 'sha256', $encrypted );
	}

	/**
	 * Build the meta value stored for one legacy id.
	 *
	 * The raw token is never stored: only its `wp_fast_hash()` digest. The legacy id is not
	 * in here — it is in the meta key (see `Constants`), which is the indexed column every
	 * lookup matches on.
	 *
	 * Unsubscribe links never expire, so they are stored as the bare `{digest}`. Verification
	 * links do, so they carry the expiry resolved at migration time in front of it:
	 * `{expires_at}:{digest}`. `wp_fast_hash()` output contains no colon, so the two shapes
	 * are unambiguous.
	 *
	 * @param string   $token      Token produced by self::compute() or self::compute_verification().
	 * @param int|null $expires_at Absolute expiry as a Unix timestamp, or null for a link that
	 *                             does not expire.
	 * @return string
	 */
	public static function to_meta_value( string $token, ?int $expires_at = null ): string {
		$digest = wp_fast_hash( $token );

		return null === $expires_at ? $digest : "{$expires_at}:{$digest}";
	}

	/**
	 * Split a stored meta value into its digest and expiry.
	 *
	 * Splits on the colon only, so the boundary never depends on the hash format's alphabet.
	 *
	 * @param string $meta_value Stored meta value.
	 * @return array{0:string,1:?int}|null Digest and expiry, or null when the value is in
	 *                                     neither stored shape.
	 */
	public static function parse( string $meta_value ): ?array {
		$parts = explode( ':', $meta_value, 2 );

		if ( 1 === count( $parts ) ) {
			return '' === $parts[0] ? null : array( $parts[0], null );
		}

		if ( '' === $parts[0] || ! ctype_digit( $parts[0] ) || '' === $parts[1] ) {
			return null;
		}

		return array( $parts[1], (int) $parts[0] );
	}

	/**
	 * Verify a token presented by an incoming legacy link against a stored meta value.
	 *
	 * Expiry is not checked here — the caller decides what an expired link means.
	 *
	 * @param string $meta_value Stored meta value.
	 * @param string $token      Token recomputed from the link's request parameters.
	 * @return bool
	 */
	public static function verify( string $meta_value, string $token ): bool {
		$parsed = self::parse( $meta_value );

		if ( null === $parsed ) {
			return false;
		}

		return wp_verify_fast_hash( $token, $parsed[0] );
	}
}
