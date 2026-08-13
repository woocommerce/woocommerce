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
	 * The notification preferences service.
	 *
	 * @var NotificationPreferencesService
	 */
	private NotificationPreferencesService $preferences_service;

	/**
	 * The retry handler.
	 *
	 * @var NotificationRetryHandler
	 */
	private NotificationRetryHandler $retry_handler;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param WpcomNotificationDispatcher    $dispatcher          The WPCOM dispatcher.
	 * @param PushTokensDataStore            $data_store          The push tokens data store.
	 * @param NotificationPreferencesService $preferences_service The notification preferences service.
	 * @param NotificationRetryHandler       $retry_handler The retry handler.
	 *
	 * @since 10.7.0
	 */
	final public function init(
		WpcomNotificationDispatcher $dispatcher,
		PushTokensDataStore $data_store,
		NotificationPreferencesService $preferences_service,
		NotificationRetryHandler $retry_handler
	): void {
		$this->dispatcher          = $dispatcher;
		$this->data_store          = $data_store;
		$this->preferences_service = $preferences_service;
		$this->retry_handler       = $retry_handler;
	}

	/**
	 * Registers the ActionScheduler hook for the safety net job.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function register(): void {
		add_action( self::SAFETY_NET_HOOK, array( $this, 'handle_safety_net' ), 10, 3 );
	}

	/**
	 * Processes a single notification: checks meta, sends to WPCOM, marks sent.
	 *
	 * @param Notification $notification The notification to process.
	 * @param bool         $is_retry     Whether this is a retry or safety net attempt.
	 * @param int          $attempt      The current attempt number (0 = first attempt).
	 * @return bool True if successfully sent (or already sent).
	 *
	 * @since 10.7.0
	 */
	public function process( Notification $notification, bool $is_retry = false, int $attempt = 0 ): bool {
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
		 * Filter out tokens whose owning user does not want this notification.
		 * The decision is delegated to the notification itself via
		 * {@see Notification::should_send_to_user()} so per-type preference
		 * shapes (simple bool today, parametrized arrays in the future) stay
		 * encapsulated alongside the type's resource access.
		 */
		$tokens = $this->filter_tokens_by_preferences( $tokens, $notification );

		/**
		 * There are no recipients to send to (either no tokens at all, or
		 * every owning user opted out of this notification type). We don't
		 * want to retry as this isn't a 'recoverable error', so mark as sent
		 * and return.
		 */
		if ( empty( $tokens ) ) {
			$notification->write_meta( self::SENT_META_KEY );
			$this->cancel_safety_net( $notification );
			return true;
		}

		$result = $this->dispatcher->dispatch( $notification, $tokens );

		if ( ! empty( $result['success'] ) ) {
			$notification->write_meta( self::SENT_META_KEY );
			$notification->delete_meta( self::CLAIMED_META_KEY );
			$this->cancel_safety_net( $notification );
			return true;
		}

		$this->retry_handler->schedule( $notification, $result['retry_after'] ?? null, $attempt );
		$this->cancel_safety_net( $notification );

		return false;
	}

	/**
	 * Returns the subset of $tokens whose owning user wants $notification.
	 *
	 * The decision is delegated to {@see Notification::should_send_to_user()}
	 * so per-type preference shapes (simple bool today, parametrized arrays
	 * in the future) stay encapsulated alongside the type's resource access.
	 * Tokens with no owning user are dropped — there are no preferences to
	 * consult.
	 *
	 * Decisions are memoized per user for the duration of one call, since
	 * the same user can have several registered tokens (iOS, iPad, Android,
	 * browser) and we don't want to re-read user meta or re-fetch the
	 * resource for every token.
	 *
	 * @param PushToken[]  $tokens       The tokens to filter.
	 * @param Notification $notification The notification being processed.
	 *
	 * @return PushToken[] The tokens whose owner wants the notification.
	 *
	 * @since 10.9.0
	 */
	private function filter_tokens_by_preferences( array $tokens, Notification $notification ): array {
		$type           = $notification->get_type();
		$decision_cache = array();

		return array_values(
			array_filter(
				$tokens,
				function ( PushToken $token ) use ( $notification, $type, &$decision_cache ) {
					$user_id = $token->get_user_id();
					if ( ! $user_id ) {
						return false;
					}

					if ( ! isset( $decision_cache[ $user_id ] ) ) {
						$prefs                      = $this->preferences_service->get_preferences( $user_id );
						$decision_cache[ $user_id ] = $notification->should_send_to_user( $prefs[ $type ] ?? null );
					}

					return $decision_cache[ $user_id ];
				}
			)
		);
	}

	/**
	 * Cancels the pending safety net ActionScheduler job for a notification.
	 *
	 * Called after the processor handles the notification (whether success or
	 * failure with retry scheduled) so the safety net doesn't fire redundantly.
	 *
	 * @param Notification $notification The notification whose safety net to cancel.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	private function cancel_safety_net( Notification $notification ): void {
		// Must match the shape PendingNotificationStore::schedule_safety_net() used;
		// both derive the args from Notification::get_safety_net_args() so the
		// exact-equality match Action Scheduler performs succeeds.
		as_unschedule_all_actions(
			self::SAFETY_NET_HOOK,
			$notification->get_safety_net_args(),
			self::ACTION_SCHEDULER_GROUP
		);
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
	 * @param array  $extra       Optional subclass-specific extras (e.g. event_type, stock_quantity_at_trigger).
	 *                            Empty for notification types whose state is fully described by type + resource_id.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function handle_safety_net( string $type, int $resource_id, array $extra = array() ): void {
		try {
			// Use the `+` array union operator (not array_merge) so the positional
			// $type and $resource_id always win over any colliding keys in $extra.
			// Defends against a malformed payload reconstructing the wrong target.
			$data = array(
				'type'        => $type,
				'resource_id' => $resource_id,
			) + $extra;

			$notification = Notification::from_array( $data );
		} catch ( Exception $e ) {
			wc_get_logger()->error(
				sprintf( 'Safety net failed: %s', $e->getMessage() ),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);
			return;
		}

		try {
			$this->process( $notification, true );
		} catch ( Exception $e ) {
			wc_get_logger()->error(
				sprintf( 'Safety net failed: %s', $e->getMessage() ),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);
			$this->retry_handler->schedule( $notification, null, 0 );
		}
	}
}
