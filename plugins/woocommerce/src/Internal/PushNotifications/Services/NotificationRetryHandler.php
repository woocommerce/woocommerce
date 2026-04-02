<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Exception;

/**
 * Handles retry scheduling for failed WPCOM push notification sends.
 *
 * Uses ActionScheduler to schedule retries with exponential backoff.
 * After all retry attempts are exhausted, logs a permanent failure.
 *
 * @since 10.8.0
 */
class NotificationRetryHandler {

	/**
	 * ActionScheduler hook for retry jobs.
	 */
	const RETRY_HOOK = 'wc_push_notification_retry';

	/**
	 * Maximum number of retries before giving up (5 total attempts including
	 * the initial send).
	 */
	const MAX_RETRIES = 4;

	/**
	 * Backoff delays in seconds, indexed by attempt number (1-based).
	 *
	 * Attempt 1: 60s (1 minute)
	 * Attempt 2: 300s (5 minutes)
	 * Attempt 3: 900s (15 minutes)
	 * Attempt 4: 3600s (60 minutes)
	 *
	 * @var array<int, int>
	 */
	const BACKOFF_SCHEDULE = array(
		1 => 60,
		2 => 300,
		3 => 900,
		4 => 3600,
	);

	/**
	 * Registers the ActionScheduler hook for retry jobs.
	 *
	 * @return void
	 *
	 * @since 10.8.0
	 */
	public function register(): void {
		add_action( self::RETRY_HOOK, array( $this, 'handle_retry' ), 10, 3 );
	}

	/**
	 * Schedules a retry for a failed notification send.
	 *
	 * If the maximum number of retries has been reached, logs a permanent
	 * failure instead of scheduling another attempt.
	 *
	 * @param Notification $notification    The notification that failed.
	 * @param int|null     $retry_after     Optional Retry-After value from WPCOM (seconds).
	 * @param int          $current_attempt The attempt number that just failed (0-based).
	 * @return void
	 *
	 * @since 10.8.0
	 */
	public function schedule( Notification $notification, ?int $retry_after, int $current_attempt ): void {
		$next_attempt = $current_attempt + 1;

		if ( $next_attempt > self::MAX_RETRIES ) {
			wc_get_logger()->error(
				sprintf(
					'Push notification permanently failed after %d attempts (type=%s, resource_id=%d).',
					$next_attempt,
					$notification->get_type(),
					$notification->get_resource_id()
				),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);
			return;
		}

		$delay = $retry_after ?? self::BACKOFF_SCHEDULE[ $next_attempt ];

		as_schedule_single_action(
			time() + $delay,
			self::RETRY_HOOK,
			array(
				'type'        => $notification->get_type(),
				'resource_id' => $notification->get_resource_id(),
				'attempt'     => $next_attempt,
			),
			NotificationProcessor::ACTION_SCHEDULER_GROUP
		);
	}

	/**
	 * ActionScheduler callback for retry jobs.
	 *
	 * Reconstructs the notification from the stored type and resource ID,
	 * then delegates to the processor with is_retry=true.
	 *
	 * @param string $type        The notification type.
	 * @param int    $resource_id The resource ID.
	 * @param int    $attempt     The current retry attempt number (1-based).
	 * @return void
	 *
	 * @since 10.8.0
	 */
	public function handle_retry( string $type, int $resource_id, int $attempt ): void {
		try {
			$notification = Notification::from_array(
				array(
					'type'        => $type,
					'resource_id' => $resource_id,
				)
			);
		} catch ( Exception $e ) {
			wc_get_logger()->error(
				sprintf( 'Retry failed: %s', $e->getMessage() ),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);
			return;
		}

		try {
			wc_get_container()->get( NotificationProcessor::class )->process(
				$notification,
				true,
				$attempt
			);
		} catch ( Exception $e ) {
			wc_get_logger()->error(
				sprintf( 'Retry failed: %s', $e->getMessage() ),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);
			$this->schedule( $notification, null, $attempt );
		}
	}
}
