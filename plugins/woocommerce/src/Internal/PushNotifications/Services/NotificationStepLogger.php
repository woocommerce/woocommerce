<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Throwable;

/**
 * Records each step a push notification takes, from trigger to send, as
 * WooCommerce log lines that Mission Control can read back per notification
 * and per device.
 *
 * Notification-level steps go to one rolling source per notification type;
 * per-device steps go to one rolling source per token, so "attempts for this
 * device" is a filename lookup. Every line carries the notification identifier
 * in its context, which is the join key between the two.
 *
 * Nothing is written on a store that has never registered a push token, so
 * stores that do not use push notifications never see these sources.
 *
 * @since 11.3.0
 */
class NotificationStepLogger {
	/**
	 * Source prefix for notification-level lines; the notification type follows.
	 */
	const NOTIFICATION_SOURCE_PREFIX = 'push-notifications-';

	/**
	 * Source prefix for per-device lines; the token post ID follows.
	 */
	const TOKEN_SOURCE_PREFIX = 'push-token-';

	/**
	 * The push tokens data store.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $data_store;

	/**
	 * Whether step logging is active for this request, or null until checked.
	 *
	 * @var bool|null
	 */
	private ?bool $active = null;

	/**
	 * Initialize injected dependencies.
	 *
	 * @internal
	 *
	 * @param PushTokensDataStore $data_store The push tokens data store.
	 *
	 * @since 11.3.0
	 */
	final public function init( PushTokensDataStore $data_store ): void {
		$this->data_store = $data_store;
	}

	/**
	 * Records a notification-level step.
	 *
	 * @param Notification $notification The notification the step belongs to.
	 * @param string       $step         Machine-readable step name, e.g. `cleared_to_send`.
	 * @param string       $outcome      Machine-readable outcome, e.g. `ok` or `no_tokens`.
	 * @param array        $context      Extra scalar fields to store with the line.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_notification_step( Notification $notification, string $step, string $outcome, array $context = array() ): void {
		$this->write(
			self::get_notification_source( $notification ),
			$notification,
			$step,
			$outcome,
			$context
		);
	}

	/**
	 * Records a per-device step for one token.
	 *
	 * @param Notification $notification The notification the step belongs to.
	 * @param int          $token_id     The push token post ID.
	 * @param int          $user_id      The user who owns the token.
	 * @param string       $step         Machine-readable step name, e.g. `held_back`.
	 * @param string       $outcome      Machine-readable outcome, e.g. `type_disabled`.
	 * @param array        $context      Extra scalar fields to store with the line.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_token_step( Notification $notification, int $token_id, int $user_id, string $step, string $outcome, array $context = array() ): void {
		$context['token_id'] = $token_id;
		$context['user_id']  = $user_id;

		$this->write(
			self::get_token_source( $token_id ),
			$notification,
			$step,
			$outcome,
			$context
		);
	}

	/**
	 * Records a failure twice: as an error or warning under the module's own
	 * source, which is the store's error log and is never switched off, and as
	 * a step in the notification's journey.
	 *
	 * The error line carries the same identifying fields as the step, so a
	 * store whose log threshold drops `info` still has a line the read path can
	 * join to the notification.
	 *
	 * @param Notification $notification The notification the failure belongs to.
	 * @param string       $step         Machine-readable step name, e.g. `send`.
	 * @param string       $outcome      Machine-readable outcome, e.g. `request_failed`.
	 * @param string       $level        `error` or `warning`.
	 * @param string       $message      Human-readable message for the error log.
	 * @param array        $context      Extra scalar fields to store with both lines.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_failure( Notification $notification, string $step, string $outcome, string $level, string $message, array $context = array() ): void {
		$this->write_error( $level, $message, self::identify( $notification, $step, $outcome, $context ) );
		$this->log_notification_step( $notification, $step, $outcome, $context );
	}

	/**
	 * Records a failure that happened before a notification could be
	 * identified, such as a loopback request that failed authorization.
	 *
	 * Written under the module's own source only, since there is no journey to
	 * attach it to; the read path includes that source for the days in range.
	 *
	 * @param string $step    Machine-readable step name, e.g. `received`.
	 * @param string $outcome Machine-readable outcome, e.g. `auth_failed`.
	 * @param string $level   `error` or `warning`.
	 * @param string $message Human-readable message for the error log.
	 * @param array  $context Extra scalar fields to store with the line.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_unattributed_failure( string $step, string $outcome, string $level, string $message, array $context = array() ): void {
		$context['step']    = $step;
		$context['outcome'] = $outcome;

		$this->write_error( $level, $message, $context );
	}

	/**
	 * Builds the log source for a notification type.
	 *
	 * @param Notification $notification The notification.
	 * @return string
	 *
	 * @since 11.3.0
	 */
	public static function get_notification_source( Notification $notification ): string {
		return sanitize_title( self::NOTIFICATION_SOURCE_PREFIX . str_replace( '_', '-', $notification->get_type() ) );
	}

	/**
	 * Builds the log source for a push token.
	 *
	 * @param int $token_id The push token post ID.
	 * @return string
	 *
	 * @since 11.3.0
	 */
	public static function get_token_source( int $token_id ): string {
		return self::TOKEN_SOURCE_PREFIX . $token_id;
	}

	/**
	 * Whether step logging is active for this request.
	 *
	 * Decided once per request, so a notification is logged in full or not at
	 * all: the kill switch filter must allow it, and the store must have
	 * registered a push token at some point.
	 *
	 * @return bool
	 *
	 * @since 11.3.0
	 */
	public function is_active(): bool {
		if ( null !== $this->active ) {
			return $this->active;
		}

		/**
		 * Filters whether push notification step logging is enabled.
		 *
		 * Evaluated once per request. Turning it off stops the journey lines
		 * only; the module's error and warning logging is unaffected.
		 *
		 * @since 11.3.0
		 *
		 * @param bool $enabled Whether step logging is enabled. Default true.
		 */
		$enabled = (bool) apply_filters( 'woocommerce_push_notification_step_logging_enabled', true );

		$this->active = $enabled && $this->data_store->has_ever_had_tokens();

		return $this->active;
	}

	/**
	 * Writes one line, swallowing any failure so logging can never break a send.
	 *
	 * @param string       $source       The log source.
	 * @param Notification $notification The notification the step belongs to.
	 * @param string       $step         Machine-readable step name.
	 * @param string       $outcome      Machine-readable outcome.
	 * @param array        $context      Extra fields to store with the line.
	 * @return void
	 */
	private function write( string $source, Notification $notification, string $step, string $outcome, array $context ): void {
		try {
			if ( ! $this->is_active() ) {
				return;
			}

			$context           = self::identify( $notification, $step, $outcome, $context );
			$context['source'] = $source;

			wc_get_logger()->info( self::format_message( $step, $outcome ), $context );
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Deliberately ignored: a logging failure must not affect the send.
		}
	}

	/**
	 * Writes one line to the module's own error source, swallowing any failure.
	 *
	 * @param string $level   `error` or `warning`.
	 * @param string $message Human-readable message.
	 * @param array  $context Fields to store with the line.
	 * @return void
	 */
	private function write_error( string $level, string $message, array $context ): void {
		try {
			$context['source']         = PushNotifications::FEATURE_NAME;
			$context['remote-logging'] = false;

			wc_get_logger()->log( $level, $message, $context );
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Deliberately ignored: a logging failure must not affect the send.
		}
	}

	/**
	 * Adds the fields every line needs to be joined back to its notification.
	 * The caller's context cannot overwrite them.
	 *
	 * @param Notification $notification The notification.
	 * @param string       $step         Machine-readable step name.
	 * @param string       $outcome      Machine-readable outcome.
	 * @param array        $context      The caller's context.
	 * @return array
	 */
	private static function identify( Notification $notification, string $step, string $outcome, array $context ): array {
		return array_merge(
			$context,
			array(
				'identifier'     => $notification->get_identifier(),
				'type'           => $notification->get_type(),
				'resource_id'    => $notification->get_resource_id(),
				'step'           => $step,
				'outcome'        => $outcome,
				'remote-logging' => false,
			)
		);
	}

	/**
	 * Builds the human-readable message for a line. Readers filter on the
	 * context fields, never on this text.
	 *
	 * @param string $step    Machine-readable step name.
	 * @param string $outcome Machine-readable outcome.
	 * @return string
	 */
	private static function format_message( string $step, string $outcome ): string {
		return sprintf( '%s: %s', ucfirst( str_replace( '_', ' ', $step ) ), str_replace( '_', ' ', $outcome ) );
	}
}
