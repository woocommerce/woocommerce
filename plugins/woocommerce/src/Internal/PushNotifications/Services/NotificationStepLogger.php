<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Throwable;

/**
 * Records each step a push notification takes, from trigger to send, as
 * WooCommerce log lines that Mission Control can read back per notification
 * and per device.
 *
 * Notification-level steps go to one rolling source per notification type and
 * per-device steps to one rolling source per token, with the notification
 * identifier on every line linking the two. That identifier names the
 * notification's subject rather than one delivery, so a resource firing the
 * same type twice in a day repeats it, and a run is read from its trigger step
 * to its terminal outcome.
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
	 * Source for devices a notification was not sent to.
	 *
	 * One source for the whole store rather than one per device, because the
	 * count follows every token the store holds rather than the hundred a
	 * notification can reach, and a device nobody sent to has a reason rather
	 * than a delivery history to read.
	 */
	const SUPPRESSED_SOURCE = 'push-suppressed';

	/**
	 * Whether step logging is active for this request, or null until checked.
	 *
	 * @var bool|null
	 */
	private ?bool $active = null;

	/**
	 * Records a notification-level step.
	 *
	 * @param Notification $notification The notification the step belongs to.
	 * @param string       $step         Machine-readable step name, e.g. `recipients`.
	 * @param string       $outcome      Machine-readable outcome, e.g. `ok` or `no_tokens`.
	 * @param array        $context      Extra scalar fields to store with the line.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_notification_step( Notification $notification, string $step, string $outcome, array $context = array() ): void {
		$this->write( $notification, null, $step, $outcome, $context );
	}

	/**
	 * Records a per-device step for one token.
	 *
	 * @param Notification $notification The notification the step belongs to.
	 * @param int          $token_id     The push token post ID.
	 * @param int          $user_id      The user who owns the token.
	 * @param string       $step         Machine-readable step name, e.g. `token_included`.
	 * @param string       $outcome      Machine-readable outcome, e.g. `type_disabled`.
	 * @param array        $context      Extra scalar fields to store with the line.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_token_step( Notification $notification, int $token_id, int $user_id, string $step, string $outcome, array $context = array() ): void {
		$context['token_id'] = $token_id;
		$context['user_id']  = $user_id;

		$this->write( $notification, $token_id, $step, $outcome, $context );
	}

	/**
	 * Records a step for a device the notification was not sent to.
	 *
	 * These go to one store-wide source rather than the device's own, so the
	 * file count follows the notification types rather than every token the
	 * store holds.
	 *
	 * @param Notification $notification The notification the step belongs to.
	 * @param int          $token_id     The push token post ID.
	 * @param int          $user_id      The user who owns the token.
	 * @param string       $step         Machine-readable step name, e.g. `token_excluded`.
	 * @param string       $outcome      Machine-readable outcome, e.g. `type_disabled`.
	 * @param array        $context      Extra scalar fields to store with the line.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_suppressed_token_step( Notification $notification, int $token_id, int $user_id, string $step, string $outcome, array $context = array() ): void {
		$context['token_id'] = $token_id;
		$context['user_id']  = $user_id;

		$this->write( $notification, $token_id, $step, $outcome, $context, true );
	}

	/**
	 * Records a failure as an error or warning under the module's own source,
	 * which is never switched off, and as a step in the notification's journey.
	 * The error line carries the same identifying fields as the step, so a store
	 * whose log threshold drops `info` still has a line the read path can join
	 * to the notification.
	 *
	 * Where identifying the notification fails, the error line is still written,
	 * without those fields.
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
		try {
			$identified = self::identify( $notification, $step, $outcome, $context );
		} catch ( Throwable $e ) {
			$this->log_unattributed_failure( $step, $outcome, $level, $message, $context );
			return;
		}

		$this->write_error( $level, $message, $identified );
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
	 * Whether step logging is active, decided once per logger.
	 *
	 * The container resolves one logger per request, so in practice the filter
	 * runs once a request.
	 *
	 * A filter callback that throws turns logging off, since callers outside
	 * this class read the answer without guarding against it.
	 *
	 * @return bool
	 *
	 * @since 11.3.0
	 */
	public function is_active(): bool {
		if ( null !== $this->active ) {
			return $this->active;
		}

		try {
			/**
			 * Filters whether push notification step logging is enabled.
			 *
			 * Evaluated once per logger, and the container resolves one per
			 * request. Turning it off stops the journey lines only; the module's
			 * error and warning logging is unaffected.
			 *
			 * @since 11.3.0
			 *
			 * @param bool $enabled Whether step logging is enabled. Default true.
			 */
			$enabled = (bool) apply_filters( 'woocommerce_push_notification_step_logging_enabled', true );
		} catch ( Throwable $e ) {
			$enabled = false;
		}

		$this->active = $enabled;

		return $this->active;
	}

	/**
	 * Writes one line, swallowing any failure so logging can never break a send.
	 *
	 * The source is built here rather than by the caller, because
	 * get_notification_source() runs sanitize_title() and its public filter.
	 *
	 * @param Notification $notification The notification the step belongs to.
	 * @param int|null     $token_id     The push token post ID for a per-device line, or null for a notification-level line.
	 * @param string       $step         Machine-readable step name.
	 * @param string       $outcome      Machine-readable outcome.
	 * @param array        $context      Extra fields to store with the line.
	 * @param bool         $suppressed   True to write to the store-wide suppressed source instead of the device's own.
	 * @return void
	 */
	private function write( Notification $notification, ?int $token_id, string $step, string $outcome, array $context, bool $suppressed = false ): void {
		try {
			if ( ! $this->is_active() ) {
				return;
			}

			$context = self::identify( $notification, $step, $outcome, $context );

			if ( $suppressed ) {
				$context['source'] = self::SUPPRESSED_SOURCE;
			} elseif ( null === $token_id ) {
				$context['source'] = self::get_notification_source( $notification );
			} else {
				$context['source'] = self::get_token_source( $token_id );
			}

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
