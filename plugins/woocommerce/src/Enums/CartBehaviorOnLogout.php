<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the 'woocommerce_cart_behavior_on_logout' option.
 *
 * @since 11.2.0
 */
final class CartBehaviorOnLogout {
	/**
	 * Carry the cart items over to the guest session created when the shopper logs out.
	 *
	 * @var string
	 */
	public const PRESERVE = 'preserve';

	/**
	 * Empty the cart when the shopper logs out.
	 *
	 * @var string
	 */
	public const CLEAR = 'clear';
}
