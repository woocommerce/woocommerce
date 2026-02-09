<?php
/**
 * Cart token utility functions.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Authentication;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;

/**
 * Cart token utility functions.
 */
class CartTokenUtils {
	/**
	 * Generate a cart token.
	 *
	 * @param string $customer_id The customer ID.
	 * @return string
	 */
	public static function get_cart_token( string $customer_id ): string {
		return JsonWebToken::create(
			array(
				'user_id' => $customer_id,
				'exp'     => self::get_cart_token_expiration(),
				'iss'     => 'store-api',
			),
			self::get_cart_token_secret()
		);
	}

	/**
	 * Validate the cart token.
	 *
	 * @param string $cart_token The cart token.
	 * @return bool
	 */
	public static function validate_cart_token( string $cart_token ): bool {
		return JsonWebToken::validate( $cart_token, self::get_cart_token_secret() );
	}

	/**
	 * Get the cart token payload.
	 *
	 * @param string $cart_token The cart token.
	 * @return array
	 */
	public static function get_cart_token_payload( string $cart_token ): array {
		$parts = JsonWebToken::get_parts( $cart_token )->payload;

		return array(
			'user_id' => $parts->user_id ?? '',
			'exp'     => $parts->exp ?? 0,
			'iss'     => $parts->iss ?? '',
		);
	}

	/**
	 * Get the cart token secret.
	 *
	 * @return string
	 */
	private static function get_cart_token_secret(): string {
		return '@' . wp_salt();
	}

	/**
	 * Gets the expiration of the cart token. Defaults to 48h.
	 *
	 * @return int
	 */
	private static function get_cart_token_expiration(): int {
		/**
		 * Filters the session expiration.
		 *
		 * @since 5.0.0
		 * @param int $expiration Expiration in seconds.
		 */
		return time() + intval( apply_filters( 'wc_session_expiration', DAY_IN_SECONDS * 2 ) );
	}
}
