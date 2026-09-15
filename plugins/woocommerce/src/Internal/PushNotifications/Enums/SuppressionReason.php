<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications\Enums;

/**
 * Reasons a notification was not sent to a device.
 */
final class SuppressionReason {

	/**
	 * The user turned this notification type off.
	 *
	 * @var string
	 */
	public const TYPE_DISABLED = 'type_disabled';

	/**
	 * The user turned off this stock event type.
	 *
	 * @var string
	 */
	public const EVENT_TYPE_DISABLED = 'event_type_disabled';

	/**
	 * The order could not be loaded.
	 *
	 * @var string
	 */
	public const ORDER_MISSING = 'order_missing';

	/**
	 * The order total is below the amount the user asked to be told about.
	 *
	 * @var string
	 */
	public const BELOW_MIN_AMOUNT = 'below_min_amount';

	/**
	 * The review could not be loaded.
	 *
	 * @var string
	 */
	public const COMMENT_MISSING = 'comment_missing';

	/**
	 * The review's rating is above the maximum the user asked to be told about.
	 *
	 * @var string
	 */
	public const ABOVE_MAX_RATING = 'above_max_rating';

	/**
	 * The token has no owning user, so there are no preferences to consult.
	 *
	 * @var string
	 */
	public const NO_USER = 'no_user';
}
