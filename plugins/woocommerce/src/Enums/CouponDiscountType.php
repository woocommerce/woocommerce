<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible discount types of a coupon (as returned by wc_get_coupon_types()).
 *
 * @since 11.0.0
 */
final class CouponDiscountType {
	/**
	 * Percentage discount.
	 *
	 * @var string
	 */
	public const PERCENT = 'percent';

	/**
	 * Fixed cart discount.
	 *
	 * @var string
	 */
	public const FIXED_CART = 'fixed_cart';

	/**
	 * Fixed product discount.
	 *
	 * @var string
	 */
	public const FIXED_PRODUCT = 'fixed_product';
}
