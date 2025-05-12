<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Cart;

/**
 * Class SavedCart
 *
 * Handles saved cart (or persistent cart) functionality.
 *
 * @package Automattic\WooCommerce\Internal\Cart
 */
class SavedCart {
	/**
	 * Whether the persistent cart is enabled.
	 *
	 * @var ?bool
	 */
	private static $enabled;

	/**
	 * Sets up the hooks.
	 *
	 * @internal
	 */
	final public function init() {
	}

	/**
	 * As of WooCommerce 9.6, Brands is enabled for all users.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		if ( null === self::$enabled ) {
			/**
			 * Filter whether the persistent cart is enabled.
			 *
			 * @since 3.2.0
			 * @param bool $enabled Whether the persistent cart is enabled.
			 */
			self::$enabled = apply_filters( 'woocommerce_persistent_cart_enabled', true );
		}
		return self::$enabled;
	}

	/**
	 * Get the persistent cart from the database for a specific user.
	 *
	 * @param int $user_id The user ID.
	 * @return array
	 */
	public static function get_saved_cart( int $user_id ) {
		if ( ! self::is_enabled() || ! $user_id ) {
			return array();
		}

		$saved_cart      = array();
		$saved_cart_meta = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );

		if ( isset( $saved_cart_meta['cart'] ) ) {
			$saved_cart = array_filter( (array) $saved_cart_meta['cart'] );
		}

		return $saved_cart;
	}

	/**
	 * Update the persistent cart for a specific user.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $cart The cart.
	 */
	public static function update_saved_cart( int $user_id, array $cart ) {
		if ( ! self::is_enabled() || ! $user_id ) {
			return;
		}

		update_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), array( 'cart' => $cart ) );
	}

	/**
	 * Delete the persistent cart for a specific user.
	 *
	 * @param int $user_id The user ID.
	 */
	public static function delete_saved_cart( int $user_id ) {
		if ( ! self::is_enabled() || ! $user_id ) {
			return;
		}

		delete_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id() );
	}
}
