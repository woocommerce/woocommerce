<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Utilities;

/**
 * Canonical form for customer emails stored in stock notifications.
 *
 * Lookups on `wc_stock_notifications.user_email` use plain SQL equality, so every
 * write and every query must go through the same normalization or the same customer
 * can appear more than once under a different letter case.
 *
 * @internal
 */
final class EmailNormalizer {

	/**
	 * Normalize an already validated email address.
	 *
	 * Trims and lowercases. No validation is performed.
	 *
	 * @param string $email The email address.
	 * @return string
	 */
	public static function normalize( string $email ): string {
		return strtolower( trim( $email ) );
	}

	/**
	 * Sanitize and normalize an untrusted email address.
	 *
	 * @param string $email The raw email address.
	 * @return string The normalized address, or an empty string when it is not a valid email.
	 */
	public static function sanitize( string $email ): string {
		$email = self::normalize( sanitize_email( $email ) );

		return is_email( $email ) ? $email : '';
	}
}
