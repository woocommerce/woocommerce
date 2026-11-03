<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications\Enums;

/**
 * Outcomes of a stale push token cleanup run.
 */
final class CleanupOutcome {

	/**
	 * Every stale token the run selected was handled.
	 *
	 * @var string
	 */
	public const COMPLETED = 'completed';

	/**
	 * The run hit its deletion limit, leaving stale tokens for the next run.
	 *
	 * @var string
	 */
	public const CAPPED = 'capped';

	/**
	 * Push notifications are not enabled on the store, so nothing was deleted.
	 *
	 * @var string
	 */
	public const SKIPPED_DISABLED = 'skipped_disabled';

	/**
	 * The cleanup was switched off with a filter, so nothing was deleted.
	 *
	 * @var string
	 */
	public const SKIPPED_FILTER = 'skipped_filter';

	/**
	 * Push notifications came back on less than a retention window ago, so the
	 * apps have not yet had the chance to check in and nothing was deleted.
	 *
	 * @var string
	 */
	public const SKIPPED_RECENTLY_RESUMED = 'skipped_recently_resumed';

	/**
	 * The run stopped on an unexpected error.
	 *
	 * @var string
	 */
	public const FAILED = 'failed';
}
