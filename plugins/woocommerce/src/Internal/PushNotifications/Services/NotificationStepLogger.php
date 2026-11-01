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
 * Every line goes to one rolling source per notification type, except the
 * tokens a notification was not sent to, which share one store-wide source. No
 * source is per token, so the file count follows the notification types rather
 * than the devices a store holds; a line names its devices in its context
 * instead, and the read side turns those back into a row per device.
 *
 * The notification identifier on every line links them. That identifier names
 * the notification's subject rather than one delivery, so a resource firing the
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
	 * Source for devices a notification was not sent to.
	 *
	 * One source for the whole store rather than one per device, because the
	 * count follows every token the store holds rather than the hundred a
	 * notification can reach, and a device nobody sent to has a reason rather
	 * than a delivery history to read.
	 */
	const SUPPRESSED_SOURCE = 'push-suppressed';

	/**
	 * The most characters a line's message may carry.
	 *
	 * Exception messages reach the log and an exception can name a value the
	 * caller sent, so nothing written here is assumed to be short.
	 */
	const MAX_MESSAGE_LENGTH = 1000;

	/**
	 * The most characters any one context value may carry.
	 */
	const MAX_VALUE_LENGTH = 200;

	/**
	 * The most entries any one context array may name. What is left out is
	 * counted in a sibling field rather than dropped silently.
	 */
	const MAX_ARRAY_ITEMS = 100;

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
		$this->write( $notification, $step, $outcome, $context );
	}

	/**
	 * Records a step for the tokens a notification was not sent to.
	 *
	 * Goes to one store-wide source rather than a source per token, so the
	 * file count follows the notification types rather than every token the
	 * store holds. The tokens themselves are named in the context.
	 *
	 * @param Notification $notification The notification the step belongs to.
	 * @param string       $step         Machine-readable step name, e.g. `token_excluded`.
	 * @param string       $outcome      Machine-readable outcome, e.g. `notifications_off`.
	 * @param array        $context      Extra fields to store with the line.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_suppressed_step( Notification $notification, string $step, string $outcome, array $context = array() ): void {
		$this->write( $notification, $step, $outcome, $context, true );
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
	 * @param string       $step         Machine-readable step name, e.g. `dispatched`.
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
	 * Records a step that covers several notifications at once, such as the
	 * single loopback request a batch is sent in.
	 *
	 * Written under the module's own source, since a batch can span
	 * notification types and so belongs to no one type's source. The
	 * notifications it covers are named by identifier in the context.
	 *
	 * @param string $step    Machine-readable step name, e.g. `loopback_requested`.
	 * @param string $outcome Machine-readable outcome, e.g. `ok`.
	 * @param array  $context Extra fields to store with the line.
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function log_batch_step( string $step, string $outcome, array $context = array() ): void {
		try {
			if ( ! $this->is_active() ) {
				return;
			}

			$context['step']           = $step;
			$context['outcome']        = $outcome;
			$context['source']         = PushNotifications::FEATURE_NAME;
			$context['remote-logging'] = false;

			wc_get_logger()->info( self::format_message( $step, $outcome ), self::bound( $context ) );
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Deliberately ignored: a logging failure must not affect the send.
		}
	}

	/**
	 * Records a failure that happened before a notification could be
	 * identified, such as a loopback request that failed authorization.
	 *
	 * Written under the module's own source only, since there is no journey to
	 * attach it to; the read path includes that source for the days in range.
	 *
	 * @param string $step    Machine-readable step name, e.g. `loopback_started`.
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
		return self::get_notification_source_for_type( $notification->get_type() );
	}

	/**
	 * Builds the log source for a notification type name.
	 *
	 * @param string $type The notification type, e.g. `store_order`.
	 * @return string
	 *
	 * @since 11.3.0
	 */
	public static function get_notification_source_for_type( string $type ): string {
		return sanitize_title( self::NOTIFICATION_SOURCE_PREFIX . str_replace( '_', '-', $type ) );
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
	 * @param string       $step         Machine-readable step name.
	 * @param string       $outcome      Machine-readable outcome.
	 * @param array        $context      Extra fields to store with the line.
	 * @param bool         $suppressed   True to write to the store-wide suppressed source instead of the notification type's.
	 * @return void
	 */
	private function write( Notification $notification, string $step, string $outcome, array $context, bool $suppressed = false ): void {
		try {
			if ( ! $this->is_active() ) {
				return;
			}

			$context = self::bound( self::identify( $notification, $step, $outcome, $context ) );

			$context['source'] = $suppressed
				? self::SUPPRESSED_SOURCE
				: self::get_notification_source( $notification );

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

			wc_get_logger()->log( $level, self::clean( $message, self::MAX_MESSAGE_LENGTH ), self::bound( $context ) );
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Deliberately ignored: a logging failure must not affect the send.
		}
	}

	/**
	 * Strips control characters from a string and caps its length.
	 *
	 * A newline in a message would split the entry, since the handler writes
	 * the message into the line unencoded, so a caller able to reach a logged
	 * exception could otherwise forge a line the readers parse as genuine.
	 *
	 * @param string $value The value.
	 * @param int    $limit The most characters to keep.
	 * @return string
	 */
	private static function clean( string $value, int $limit ): string {
		// Matched bytewise, because a caller controls these bytes and they need
		// not be valid UTF-8.
		$value = (string) preg_replace( '/[\x00-\x1F\x7F]+/', ' ', $value );

		if ( mb_strlen( $value ) <= $limit ) {
			return $value;
		}

		return mb_substr( $value, 0, $limit ) . '... (truncated)';
	}

	/**
	 * Caps every value in a context, naming what was left out.
	 *
	 * Arrays are cut to {@see self::MAX_ARRAY_ITEMS} and gain a sibling
	 * counting the entries omitted, so a reader can tell a short list from a
	 * truncated one. A map of lists, such as the excluded tokens grouped by
	 * reason, is cut per list and counted per key.
	 *
	 * @param array $context The context.
	 * @return array
	 */
	private static function bound( array $context ): array {
		$bounded = array();

		foreach ( $context as $key => $value ) {
			if ( is_string( $value ) ) {
				$bounded[ $key ] = self::clean( $value, self::MAX_VALUE_LENGTH );
				continue;
			}

			if ( ! is_array( $value ) ) {
				$bounded[ $key ] = $value;
				continue;
			}

			$omitted = array();

			foreach ( $value as $inner_key => $inner ) {
				if ( ! is_array( $inner ) ) {
					continue;
				}

				$left = count( $inner ) - self::MAX_ARRAY_ITEMS;

				if ( $left > 0 ) {
					$value[ $inner_key ]   = array_slice( $inner, 0, self::MAX_ARRAY_ITEMS );
					$omitted[ $inner_key ] = $left;
				}
			}

			$left = count( $value ) - self::MAX_ARRAY_ITEMS;

			if ( empty( $omitted ) && $left > 0 ) {
				$value   = array_slice( $value, 0, self::MAX_ARRAY_ITEMS );
				$omitted = $left;
			}

			$bounded[ $key ] = $value;

			if ( ! empty( $omitted ) ) {
				$bounded[ $key . '_omitted' ] = $omitted;
			}
		}

		return $bounded;
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
