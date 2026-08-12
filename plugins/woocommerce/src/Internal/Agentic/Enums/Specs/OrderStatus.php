<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Order status values as defined in the Agentic Commerce Protocol.
 *
 * @since 10.3.0
 */
class OrderStatus {
	/**
	 * Order has been created.
	 */
	const CREATED = 'created';

	/**
	 * Order requires manual review.
	 */
	const MANUAL_REVIEW = 'manual_review';

	/**
	 * Order has been confirmed.
	 */
	const CONFIRMED = 'confirmed';

	/**
	 * Order has been canceled.
	 */
	const CANCELED = 'canceled';

	/**
	 * Order has been shipped.
	 */
	const SHIPPED = 'shipped';

	/**
	 * Order has been fulfilled.
	 */
	const FULFILLED = 'fulfilled';

	/**
	 * Get all valid order statuses.
	 *
	 * @return array Array of valid order status values.
	 */
	public static function get_all() {
		return array(
			self::CREATED,
			self::MANUAL_REVIEW,
			self::CONFIRMED,
			self::CANCELED,
			self::SHIPPED,
			self::FULFILLED,
		);
	}

	/**
	 * Check if a status is valid.
	 *
	 * @param string $status Status to check.
	 * @return bool True if valid, false otherwise.
	 */
	public static function is_valid( $status ) {
		return in_array( $status, self::get_all(), true );
	}
}
