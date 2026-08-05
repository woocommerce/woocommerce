<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the order item types.
 *
 * @since 10.8.0
 */
final class OrderItemType {
	/**
	 * Product line item type.
	 *
	 * @var string
	 */
	public const LINE_ITEM = 'line_item';

	/**
	 * Fee line item type.
	 *
	 * @var string
	 */
	public const FEE = 'fee';

	/**
	 * Shipping line item type.
	 *
	 * @var string
	 */
	public const SHIPPING = 'shipping';

	/**
	 * Tax line item type.
	 *
	 * @var string
	 */
	public const TAX = 'tax';

	/**
	 * Coupon (discount) line item type.
	 *
	 * @var string
	 */
	public const COUPON = 'coupon';
}
