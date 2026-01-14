<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Utilities;

/**
 * Helper class for hashing.
 *
 * Hint: This is a copy of the hashing functions introduced in WordPress 6.8.
 * Once WooCommerce Core requires WordPress 6.8, we can remove/replace this class.
 *
 * @internal
 */
class HasherHelper {

	/**
	 * Hash a string.
	 *
	 * @param string $key The string to hash.
	 * @return string The hashed string.
	 */
	public static function wp_fast_hash( string $key ): string {
		if ( function_exists( 'wp_fast_hash' ) ) {
			return wp_fast_hash( $key );
		}

		$hashed = sodium_crypto_generichash( $key, 'wp_fast_hash_6.8+', 30 );
		return '$generic$' . sodium_bin2base64( $hashed, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING );
	}

	/**
	 * Verify a string.
	 *
	 * @param string $key The string to verify.
	 * @param string $hash The hash to verify.
	 * @return bool Whether the string matches the hash.
	 */
	public static function wp_verify_fast_hash( string $key, string $hash ): bool {
		if ( function_exists( 'wp_verify_fast_hash' ) ) {
			return wp_verify_fast_hash( $key, $hash );
		}

		if ( ! str_starts_with( $hash, '$generic$' ) ) {
			return false;
		}

		return hash_equals( $hash, self::wp_fast_hash( $key ) );
	}
}
