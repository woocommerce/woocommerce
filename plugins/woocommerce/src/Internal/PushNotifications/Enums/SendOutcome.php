<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications\Enums;

/**
 * What happened to one attempt at sending a notification.
 *
 * The WordPress.com endpoint queues every token in a request or none of them,
 * so one outcome describes the whole batch.
 */
final class SendOutcome {

	/**
	 * WordPress.com queued the notification.
	 *
	 * @var string
	 */
	public const ACCEPTED = 'accepted';

	/**
	 * WordPress.com had already received this notification and did not queue it again.
	 *
	 * @var string
	 */
	public const DEDUPLICATED = 'deduplicated';

	/**
	 * WordPress.com refused the request because a token cannot be delivered to.
	 *
	 * @var string
	 */
	public const REJECTED_INVALID_TOKEN = 'rejected_invalid_token';

	/**
	 * WordPress.com refused the request because the notification itself failed validation.
	 *
	 * @var string
	 */
	public const REJECTED_INVALID_NOTIFICATION = 'rejected_invalid_notification';

	/**
	 * We could not send, because the store has no Jetpack site ID.
	 *
	 * @var string
	 */
	public const SITE_ID_MISSING = 'site_id_missing';

	/**
	 * We could not send, because the order, review or product no longer exists.
	 *
	 * @var string
	 */
	public const RESOURCE_MISSING = 'resource_missing';

	/**
	 * The request to WordPress.com did not complete.
	 *
	 * @var string
	 */
	public const REQUEST_FAILED = 'request_failed';

	/**
	 * WordPress.com refused the request for a reason it did not name.
	 *
	 * @var string
	 */
	public const FAILED = 'failed';
}
