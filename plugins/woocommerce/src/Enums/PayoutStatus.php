<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the high-level statuses of a payment provider payout.
 *
 * @since 11.2.0
 */
final class PayoutStatus {
	/**
	 * The payout has been initiated but not yet deposited.
	 *
	 * @var string
	 */
	public const PENDING = 'pending';

	/**
	 * The payout has been deposited.
	 *
	 * @var string
	 */
	public const COMPLETE = 'complete';

	/**
	 * The payout failed.
	 *
	 * @var string
	 */
	public const FAILED = 'failed';

	/**
	 * Returns every payout status value defined by this enum, as a flat list values.
	 *
	 * @since 11.2.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::PENDING,
			self::COMPLETE,
			self::FAILED,
		);
	}
}
