<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the internal order statuses.
 * These statuses are used internally by WooCommerce to query database directly.
 */
final class OrderInternalStatus {
	/**
	 * The order is pending payment.
	 *
	 * @var string
	 */
	public const PENDING = 'wc-pending';

	/**
	 * The order is processing.
	 *
	 * @var string
	 */
	public const PROCESSING = 'wc-processing';

	/**
	 * The order is on hold.
	 *
	 * @var string
	 */
	public const ON_HOLD = 'wc-on-hold';

	/**
	 * The order is completed.
	 *
	 * @var string
	 */
	public const COMPLETED = 'wc-completed';

	/**
	 * The order is cancelled.
	 *
	 * @var string
	 */
	public const CANCELLED = 'wc-cancelled';

	/**
	 * The order is refunded.
	 *
	 * @var string
	 */
	public const REFUNDED = 'wc-refunded';

	/**
	 * The order is failed.
	 *
	 * @var string
	 */
	public const FAILED = 'wc-failed';

	/**
	 * Returns every internal order status value defined by this enum, as a flat list of `wc-`
	 * prefixed slugs.
	 *
	 * These are the seven core statuses, which is narrower than the set an order can hold. The enum
	 * has no `wc-checkout-draft`, a status WooCommerce registers through the `wc_order_statuses`
	 * filter and the Store API assigns to live orders during checkout. It cannot carry statuses an
	 * extension registers either.
	 *
	 * For the registered statuses, use wc_get_order_statuses(), which returns a value => label map
	 * on these same prefixed keys. OrderStatus lists a wider set of unprefixed slugs, including
	 * WordPress post statuses such as trash and auto-draft.
	 *
	 * @since 10.9.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::PENDING,
			self::PROCESSING,
			self::ON_HOLD,
			self::COMPLETED,
			self::CANCELLED,
			self::REFUNDED,
			self::FAILED,
		);
	}
}
