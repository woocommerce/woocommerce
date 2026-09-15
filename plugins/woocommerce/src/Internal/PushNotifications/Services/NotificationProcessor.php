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
	 * Above this many eligible tokens, per-device log lines are skipped and only
	 * the counts on the notification line are written. A per-device line is a
	 * file per token per day, and a store with thousands of tokens would
	 * otherwise create that many files for every notification.
	 */
	const TOKEN_LINE_CAP = 50;

	/**
	 * Suppression reason for a token with no owning user, so no preferences to consult.
	 */
	const SUPPRESSED_NO_USER = 'no_user';

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
	 * The step logger.
	 *
	 * @var NotificationStepLogger
	 */
	private NotificationStepLogger $step_logger;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param WpcomNotificationDispatcher    $dispatcher          The WPCOM dispatcher.
	 * @param PushTokensDataStore            $data_store          The push tokens data store.
	 * @param NotificationPreferencesService $preferences_service The notification preferences service.
	 * @param NotificationRetryHandler       $retry_handler       The retry handler.
	 * @param NotificationStepLogger         $step_logger         The step logger.
	 *
	 * @since 10.7.0
	 */
	final public function init(
		WpcomNotificationDispatcher $dispatcher,
		PushTokensDataStore $data_store,
		NotificationPreferencesService $preferences_service,
		NotificationRetryHandler $retry_handler,
		NotificationStepLogger $step_logger
	): void {
		$this->dispatcher          = $dispatcher;
		$this->data_store          = $data_store;
		$this->preferences_service = $preferences_service;
		$this->retry_handler       = $retry_handler;
		$this->step_logger         = $step_logger;
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
		$step_context = array(
			'attempt'  => $attempt,
			'is_retry' => $is_retry,
		);

		/**
		 * This notification has already been sent - don't continue.
		 */
		if ( $notification->has_meta( self::SENT_META_KEY ) ) {
			$this->step_logger->log_notification_step( $notification, 'skipped', 'already_sent', $step_context );
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
				$this->step_logger->log_notification_step( $notification, 'skipped', 'already_claimed', $step_context );
				return true;
			}

			$notification->write_meta( self::CLAIMED_META_KEY );
		}

		/**
		 * Non-paginated result from get_tokens_for_roles.
		 *
		 * @var PushToken[] $eligible_tokens
		 */
		$eligible_tokens = $this->data_store->get_tokens_for_roles(
			PushNotifications::ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED
		);

		/**
		 * Filter out tokens whose owning user does not want this notification.
		 * The decision is delegated to the notification itself via
		 * {@see Notification::should_send_to_user()} so per-type preference
		 * shapes (simple bool today, parametrized arrays in the future) stay
		 * encapsulated alongside the type's resource access.
		 */
		list( $tokens, $held_back ) = $this->filter_tokens_by_preferences( $eligible_tokens, $notification );

		$write_token_lines = count( $eligible_tokens ) <= self::TOKEN_LINE_CAP;

		$this->log_recipients( $notification, $eligible_tokens, $tokens, $held_back, $write_token_lines, $step_context );

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

		$this->log_send_outcome( $notification, $tokens, $result, $write_token_lines, $step_context );

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
	 * Each dropped token is returned with the reason, so the step log can say
	 * why a device did not receive the notification.
	 *
	 * @param PushToken[]  $tokens       The tokens to filter.
	 * @param Notification $notification The notification being processed.
	 *
	 * @return array{0: PushToken[], 1: array<int, string>} The tokens whose owner wants the notification, and the dropped tokens as token ID => reason.
	 *
	 * @since 10.9.0
	 */
	private function filter_tokens_by_preferences( array $tokens, Notification $notification ): array {
		$type           = $notification->get_type();
		$decision_cache = array();
		$kept           = array();
		$held_back      = array();

		foreach ( $tokens as $token ) {
			$user_id = $token->get_user_id();
			if ( ! $user_id ) {
				$held_back[ (int) $token->get_id() ] = self::SUPPRESSED_NO_USER;
				continue;
			}

			if ( ! array_key_exists( $user_id, $decision_cache ) ) {
				$pref_value = $this->preferences_service->get_preferences( $user_id )[ $type ] ?? null;

				$decision_cache[ $user_id ] = $notification->should_send_to_user( $pref_value )
					? null
					: $notification->get_suppression_reason( $pref_value );
			}

			if ( null === $decision_cache[ $user_id ] ) {
				$kept[] = $token;
			} else {
				$held_back[ (int) $token->get_id() ] = $decision_cache[ $user_id ];
			}
		}

		return array( $kept, $held_back );
	}

	/**
	 * Writes the recipient decision to the step log: one notification line
	 * with the counts, and one line per device while under the cap.
	 *
	 * @param Notification       $notification      The notification being processed.
	 * @param PushToken[]        $eligible_tokens   Tokens whose owner has a role that receives push notifications.
	 * @param PushToken[]        $recipients        The tokens the notification will be sent to.
	 * @param array<int, string> $held_back         Dropped tokens as token ID => reason.
	 * @param bool               $write_token_lines Whether per-device lines are written for this attempt.
	 * @param array              $step_context      Fields shared by every line of this attempt.
	 * @return void
	 */
	private function log_recipients(
		Notification $notification,
		array $eligible_tokens,
		array $recipients,
		array $held_back,
		bool $write_token_lines,
		array $step_context
	): void {
		if ( ! $this->step_logger->is_active() ) {
			return;
		}

		$recipient_ids = array_map( fn( PushToken $token ) => (int) $token->get_id(), $recipients );

		$context = array_merge(
			$step_context,
			array(
				'tokens_total'      => $this->data_store->count_tokens(),
				'tokens_eligible'   => count( $eligible_tokens ),
				'recipients'        => count( $recipients ),
				'held_back'         => count( $held_back ),
				'held_back_reasons' => array_count_values( $held_back ),
				'token_lines'       => $write_token_lines ? 'written' : 'skipped_over_cap',
			)
		);

		if ( empty( $eligible_tokens ) ) {
			$this->step_logger->log_notification_step( $notification, 'no_recipients', 'no_tokens', $context );
		} elseif ( empty( $recipients ) ) {
			$this->step_logger->log_notification_step( $notification, 'no_recipients', 'all_held_back', $context );
		} else {
			if ( $write_token_lines ) {
				$context['token_ids'] = $recipient_ids;
			}
			$this->step_logger->log_notification_step( $notification, 'cleared_to_send', 'ok', $context );
		}

		if ( ! $write_token_lines ) {
			return;
		}

		$tokens_by_id = array();
		foreach ( $eligible_tokens as $token ) {
			$tokens_by_id[ (int) $token->get_id() ] = $token;
		}

		foreach ( $held_back as $token_id => $reason ) {
			$this->step_logger->log_token_step(
				$notification,
				$token_id,
				(int) $tokens_by_id[ $token_id ]->get_user_id(),
				'held_back',
				$reason,
				$step_context
			);
		}

		foreach ( $recipients as $token ) {
			$this->step_logger->log_token_step(
				$notification,
				(int) $token->get_id(),
				(int) $token->get_user_id(),
				'cleared_to_send',
				'ok',
				$step_context
			);
		}
	}

	/**
	 * Writes one per-device line with the send outcome WPCOM reported. A token
	 * WPCOM refused is marked as invalid; the rest carry the batch outcome.
	 *
	 * @param Notification $notification      The notification being processed.
	 * @param PushToken[]  $recipients        The tokens the notification was sent to.
	 * @param array        $result            The dispatcher's return value.
	 * @param bool         $write_token_lines Whether per-device lines are written for this attempt.
	 * @param array        $step_context      Fields shared by every line of this attempt.
	 * @return void
	 */
	private function log_send_outcome(
		Notification $notification,
		array $recipients,
		array $result,
		bool $write_token_lines,
		array $step_context
	): void {
		if ( ! $write_token_lines || ! $this->step_logger->is_active() ) {
			return;
		}

		$outcome        = (string) ( $result['outcome'] ?? ( empty( $result['success'] ) ? 'failed' : 'accepted' ) );
		$invalid_tokens = array_flip( $result['invalid_tokens'] ?? array() );

		foreach ( $recipients as $token ) {
			$this->step_logger->log_token_step(
				$notification,
				(int) $token->get_id(),
				(int) $token->get_user_id(),
				'send',
				isset( $invalid_tokens[ $token->get_token() ] ) ? 'invalid_token' : $outcome,
				$step_context
			);
		}
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
			$this->step_logger->log_unattributed_failure(
				'safety_net',
				'invalid_notification',
				'error',
				sprintf( 'Safety net failed: %s', $e->getMessage() ),
				array(
					'type'        => $type,
					'resource_id' => $resource_id,
				)
			);
			return;
		}

		$this->step_logger->log_notification_step( $notification, 'safety_net', 'fired' );

		try {
			$this->process( $notification, true );
		} catch ( Exception $e ) {
			$this->step_logger->log_failure(
				$notification,
				'safety_net',
				'exception',
				'error',
				sprintf( 'Safety net failed: %s', $e->getMessage() )
			);
			$this->retry_handler->schedule( $notification, null, 0 );
		}
	}
}
