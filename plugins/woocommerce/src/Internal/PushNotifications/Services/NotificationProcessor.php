<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\WpcomNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Exception;

/**
 * Shared orchestration for sending a single notification to WPCOM.
 *
 * Used by three callers:
 * 1. PushNotificationRestController — loopback endpoint (is_retry: false)
 * 2. ActionScheduler safety net — fallback when shutdown didn't fire (is_retry: true)
 * 3. NotificationRetryHandler — retry for failed sends (is_retry: true)
 *
 * @since 10.7.0
 */
class NotificationProcessor {
	/**
	 * ActionScheduler group for push notification jobs.
	 */
	const ACTION_SCHEDULER_GROUP = 'wc-push-notifications';

	/**
	 * Safety net delay in seconds.
	 */
	const SAFETY_NET_DELAY = 60;

	/**
	 * ActionScheduler hook for the safety net job.
	 */
	const SAFETY_NET_HOOK = 'wc_push_notification_safety_net';

	/**
	 * Meta key written before the WPCOM send attempt.
	 */
	const CLAIMED_META_KEY = '_wc_push_notification_claimed';

	/**
	 * Meta key written after successful WPCOM delivery.
	 */
	const SENT_META_KEY = '_wc_push_notification_sent';

	/**
	 * The WPCOM dispatcher.
	 *
	 * @var WpcomNotificationDispatcher
	 */
	private WpcomNotificationDispatcher $dispatcher;

	/**
	 * The push tokens data store.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $data_store;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param WpcomNotificationDispatcher $dispatcher The WPCOM dispatcher.
	 * @param PushTokensDataStore         $data_store The push tokens data store.
	 *
	 * @since 10.7.0
	 */
	final public function init(
		WpcomNotificationDispatcher $dispatcher,
		PushTokensDataStore $data_store
	): void {
		$this->dispatcher = $dispatcher;
		$this->data_store = $data_store;
	}

	/**
	 * Registers the ActionScheduler hook for the safety net job.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function register(): void {
		add_action( self::SAFETY_NET_HOOK, array( $this, 'handle_safety_net' ), 10, 2 );
	}

	/**
	 * Processes a single notification: checks meta, sends to WPCOM, marks sent.
	 *
	 * @param Notification $notification The notification to process.
	 * @param bool         $is_retry     Whether this is a retry or safety net attempt.
	 * @return bool True if successfully sent (or already sent).
	 *
	 * @since 10.7.0
	 */
	public function process( Notification $notification, bool $is_retry = false ): bool {
		/**
		 * This notification has already been sent - don't continue.
		 */
		if ( $notification->has_meta( self::SENT_META_KEY ) ) {
			return true;
		}

		if ( ! $is_retry ) {
			/**
			 * This notification has already been claimed for sending, and since
			 * this is not a retry, this is not expected and means some other
			 * process is handling the notification (e.g. race condition) -
			 * don't continue.
			 */
			if ( $notification->has_meta( self::CLAIMED_META_KEY ) ) {
				return true;
			}

			$notification->write_meta( self::CLAIMED_META_KEY );
		}

		/**
		 * Non-paginated result from get_tokens_for_roles.
		 *
		 * @var PushToken[] $tokens
		 */
		$tokens = $this->data_store->get_tokens_for_roles(
			PushNotifications::ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED
		);

		/**
		 * There are no recipients to send to. We don't want to retry as this
		 * isn't a 'recoverable error', so mark as sent and return.
		 */
		if ( empty( $tokens ) ) {
			$notification->write_meta( self::SENT_META_KEY );
			return true;
		}

		$result = $this->dispatcher->dispatch( $notification, $tokens );

		if ( ! empty( $result['success'] ) ) {
			$notification->write_meta( self::SENT_META_KEY );
			$notification->delete_meta( self::CLAIMED_META_KEY );
			return true;
		}

		/**
		 * Retry scheduling and safety net deletion will be added here when
		 * NotificationRetryHandler is added.
		 */

		return false;
	}

	/**
	 * ActionScheduler callback for the safety net job. This will be scheduled
	 * for 60 seconds in the future when a notification is added to the
	 * `PendingNotificationStore`. If the initial send succeeds, or fails and is
	 * able to schedule a retry, this action will be unscheduled. If the initial
	 * send does not occur, or fails and cannot schedule a retry (e.g. out of
	 * memory, retry scheduling error) then this safety net will run.
	 *
	 * @param string $type        The notification type.
	 * @param int    $resource_id The resource ID.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function handle_safety_net( string $type, int $resource_id ): void {
		try {
			$notification = Notification::from_array(
				array(
					'type'        => $type,
					'resource_id' => $resource_id,
				)
			);

			$this->process( $notification, true );
		} catch ( Exception $e ) {
			wc_get_logger()->error(
				sprintf( 'Safety net failed: %s', $e->getMessage() ),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);
		}
	}
}
