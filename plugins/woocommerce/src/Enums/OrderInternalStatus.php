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
	const PENDING = 'wc-pending';

	/**
	 * The order is processing.
	 *
	 * @var string
	 */
	const PROCESSING = 'wc-processing';

	/**
	 * The order is on hold.
	 *
	 * @var string
	 */
	const ON_HOLD = 'wc-on-hold';

	/**
	 * The order is completed.
	 *
	 * @var string
	 */
	const COMPLETED = 'wc-completed';

	/**
	 * The order is cancelled.
	 *
	 * @var string
	 */
	const CANCELLED = 'wc-cancelled';

	/**
	 * The order is refunded.
	 *
	 * @var string
	 */
	const REFUNDED = 'wc-refunded';

	/**
	 * The order is failed.
	 *
	 * @var string
	 */
	const FAILED = 'wc-failed';
}
