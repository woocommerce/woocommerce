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
	 * Generate a cart link token.
	 *
	 * Unlike a cart token, a link token can't be used as a Store API `Cart-Token`. It only lets another browser copy the
	 * cart through the `session` query arg, without the customer's details.
	 *
	 * @since 11.4.0
	 * @param string $customer_id The customer ID.
	 * @return string
	 */
	public static function get_cart_link_token( string $customer_id ): string {
		return JsonWebToken::create(
			array(
				'user_id' => $customer_id,
				'exp'     => self::get_cart_token_expiration(),
				'iss'     => 'cart-link',
			),
			self::get_cart_token_secret()
		);
	}

	/**
	 * Get the customer ID from a token passed in the `session` query arg.
	 *
	 * Accepts cart link tokens and cart tokens, so integrations that pass a cart token in the query arg keep working.
	 *
	 * @since 11.4.0
	 * @param string $token The cart link token or cart token.
	 * @return string The customer ID, or an empty string if the token is not valid.
	 */
	public static function get_cart_link_customer_id( string $token ): string {
		if ( ! self::validate_token( $token, array( 'cart-link', 'store-api' ) ) ) {
			return '';
		}

		return (string) ( JsonWebToken::get_parts( $token )->payload->user_id ?? '' );
	}

	/**
	 * Get the cart token sent with the current HTTP request.
	 *
	 * @since 11.1.0
	 * @return string
	 */
	public static function get_request_cart_token(): string {
		$cart_token = wc_clean( wp_unslash( $_SERVER['HTTP_CART_TOKEN'] ?? '' ) );

		return is_string( $cart_token ) ? $cart_token : '';
	}

	/**
	 * Validate the cart token.
	 *
	 * @since 11.4.0 Rejects tokens not issued by the Store API, such as cart link tokens.
	 * @param string $cart_token The cart token.
	 * @return bool
	 */
	public static function validate_cart_token( string $cart_token ): bool {
		return self::validate_token( $cart_token, array( 'store-api' ) );
	}

	/**
	 * Validate a token's signature and check it was issued by one of the given issuers.
	 *
	 * @param string   $token   The token.
	 * @param string[] $issuers Accepted `iss` claims.
	 * @return bool
	 */
	private static function validate_token( string $token, array $issuers ): bool {
		if ( ! JsonWebToken::validate( $token, self::get_cart_token_secret() ) ) {
			return false;
		}

		return in_array( JsonWebToken::get_parts( $token )->payload->iss ?? '', $issuers, true );
	}

	/**
	 * Get the cart token payload.
	 *
	 * Returns an empty payload unless the signature validates.
	 *
	 * @since 11.1.0 Returns an empty payload for tokens that fail signature validation.
	 * @param string $cart_token The cart token.
	 * @return array
	 */
	public static function get_cart_token_payload( string $cart_token ): array {
		if ( ! self::validate_cart_token( $cart_token ) ) {
			return array(
				'user_id' => '',
				'exp'     => 0,
				'iss'     => '',
			);
		}

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
	 * @since 11.1.0 Made public.
	 * @return int
	 */
	public static function get_cart_token_expiration(): int {
		/**
		 * Filters the session expiration.
		 *
		 * @since 5.0.0
		 * @param int $expiration Expiration in seconds.
		 */
		return time() + intval( apply_filters( 'wc_session_expiration', DAY_IN_SECONDS * 2 ) );
	}
}
