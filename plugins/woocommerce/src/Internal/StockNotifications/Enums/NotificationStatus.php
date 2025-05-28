<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Enums;

/**
 * Enum class for all the notification statuses.
 */
final class NotificationStatus {

	/**
	 * Status: 'pending'.
	 * Initial state when Double Opt-In (DOI) is active, awaiting user email verification.
	 * Not eligible for "back in stock" notifications until confirmed.
	 *
	 * @var string
	 */
	public const PENDING = 'pending';

	/**
	 * Status: 'active'.
	 * User's subscription is confirmed and they are waiting for a "back in stock" alert.
	 * This is the default for new subscriptions if DOI is disabled, or after DOI confirmation.
	 * Notifications in this state are processed when the product is available.
	 *
	 * @var string
	 */
	public const ACTIVE = 'active';

	/**
	 * Status: 'sent'.
	 * The "back in stock" notification email has been successfully dispatched.
	 * Typically a final state for that notification event.
	 *
	 * @var string
	 */
	public const SENT = 'sent';

	/**
	 * Status: 'cancelled'.
	 * The notification is no longer active and will not be sent.
	 * The reason for cancellation should be in the `cancellation_source` field.
	 *
	 * @var string
	 */
	public const CANCELLED = 'cancelled';

	/**
	 * Get all available notification statuses.
	 *
	 * @return array<string> Notification statuses.
	 */
	public static function get_valid_statuses(): array {
		return array(
			self::PENDING,
			self::ACTIVE,
			self::SENT,
			self::CANCELLED,
		);
	}
}
