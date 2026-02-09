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
}
