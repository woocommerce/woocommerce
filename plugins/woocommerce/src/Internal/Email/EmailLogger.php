<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Email;
use WC_Log_Levels;
use WC_Order;
use WC_Product;
use WP_Error;
use WP_User;

/**
 * Logs transactional email send attempts so store owners can inspect what WooCommerce attempted locally.
 *
 * Records are written to the WooCommerce logger under the `transactional-emails` source and include the email type,
 * related object, recipient identifier, and the local send state. The recipient is logged as the WordPress username
 * when the address is linked to an account, or as 'guest' for unrecognised addresses. Failure reasons are captured
 * from wp_mail_failed.
 *
 * @since 10.9.0
 * @internal
 */
class EmailLogger implements RegisterHooksInterface {

	/**
	 * Logger source used for all email log entries.
	 */
	private const LOG_SOURCE = 'transactional-emails';

	/**
	 * Holds the PHPMailer error message from the most recent failed wp_mail() call.
	 *
	 * @var string|null
	 */
	private ?string $last_mail_error = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_mail_failed', array( $this, 'capture_mail_error' ), 10, 1 );
		add_action( 'woocommerce_email_sent', array( $this, 'handle_woocommerce_email_sent' ), 10, 3 );
		add_action( 'woocommerce_email_disabled', array( $this, 'handle_woocommerce_email_disabled' ), 10, 2 );
		add_action( 'woocommerce_email_skipped', array( $this, 'handle_woocommerce_email_skipped' ), 10, 3 );
	}

	/**
	 * Capture the PHPMailer error from a failed wp_mail() call so it can be included in the log entry.
	 *
	 * Error attribution is best-effort: wp_mail_failed is a global hook, so any plugin's failed
	 * wp_mail() call will set $last_mail_error. The trailing edge is controlled — $last_mail_error
	 * is cleared immediately after each WooCommerce send — but the leading edge is unbounded: a
	 * non-WooCommerce wp_mail_failed fired before a WooCommerce send failure will be attributed
	 * to that WooCommerce send. This may produce misleading error reasons in stores where other
	 * plugins also call wp_mail().
	 *
	 * @param WP_Error $error The error returned by wp_mail.
	 * @return void
	 */
	public function capture_mail_error( WP_Error $error ): void {
		$this->last_mail_error = $error->get_error_message();
	}

	/**
	 * Handle the woocommerce_email_sent action.
	 *
	 * @param bool     $success  Whether the email was sent successfully.
	 * @param string   $email_id The email type ID (e.g. `customer_processing_order`).
	 * @param WC_Email $email    The WC_Email instance.
	 * @return void
	 */
	public function handle_woocommerce_email_sent( $success, string $email_id, WC_Email $email ): void {
		/**
		 * Filter whether to log this transactional email attempt.
		 *
		 * Return false to skip logging for a particular email or globally.
		 *
		 * @since 10.9.0
		 *
		 * @param bool     $enabled  Whether logging is enabled.
		 * @param string   $email_id The email type ID.
		 * @param WC_Email $email    The WC_Email instance.
		 */
		if ( ! apply_filters( 'woocommerce_email_log_enabled', true, $email_id, $email ) ) {
			$this->last_mail_error = null;
			return;
		}

		$object_context  = $this->get_object_context( $email->object );
		$object_label    = isset( $object_context['type'], $object_context['id'] )
			? sprintf( ' for %s #%d', $object_context['type'], $object_context['id'] )
			: '';
		$last_mail_error = $this->last_mail_error;

		$this->last_mail_error = null;

		$context = array(
			'source'     => self::LOG_SOURCE,
			'email_type' => $email_id,
			'status'     => $success ? 'sent' : 'failed',
			'recipient'  => $this->resolve_recipient( $email->get_recipient() ),
		);

		if ( ! empty( $object_context ) ) {
			$context[ $object_context['type'] ] = $object_context['id'] ?? null;
		}

		/**
		 * Filter the context array logged for each transactional email attempt.
		 *
		 * @since 10.9.0
		 *
		 * @param array    $context  The context array to be logged.
		 * @param string   $email_id The email type ID.
		 * @param WC_Email $email    The WC_Email instance.
		 */
		$context = (array) apply_filters( 'woocommerce_email_log_context', $context, $email_id, $email );

		$type_label = ! empty( $context['is_test'] ) ? 'Test email' : 'Email';

		if ( $success ) {
			$message = sprintf( '%s "%s"%s sent', $type_label, $email_id, $object_label );
		} else {
			$reason  = $last_mail_error ? ': ' . $this->redact_emails( $last_mail_error ) : '';
			$message = sprintf( '%s "%s"%s failed to send%s', $type_label, $email_id, $object_label, $reason );
		}

		$level = $success ? WC_Log_Levels::INFO : WC_Log_Levels::WARNING;
		wc_get_logger()->log( $level, $message, $context );

		$this->maybe_add_order_note( $email->object, $email_id, $email, (bool) $success, $last_mail_error );
	}

	/**
	 * Add a private order note when a transactional email is sent or fails for an order.
	 *
	 * Accepts mixed input because $email->object is loosely typed (any object the email subclass attaches),
	 * and we narrow to WC_Order at the top of the method before doing anything with it.
	 *
	 * @param mixed       $wc_object    The email's related object, or false/null when none is set.
	 * @param string      $email_id     The email type ID (e.g. `customer_processing_order`).
	 * @param WC_Email    $email        The WC_Email instance.
	 * @param bool        $success      Whether the email was sent successfully.
	 * @param string|null $error_reason The error message from wp_mail_failed, or null.
	 * @return void
	 */
	private function maybe_add_order_note( $wc_object, string $email_id, WC_Email $email, bool $success, ?string $error_reason ): void {
		if ( ! $wc_object instanceof WC_Order || ! $wc_object->get_object_read() ) {
			return;
		}

		/**
		 * Filter whether to add an order note for this transactional email attempt.
		 *
		 * Return false to suppress the order note for a particular email or globally,
		 * while still allowing the WooCommerce logger entry to be written.
		 *
		 * @since 10.9.0
		 *
		 * @param bool     $enabled  Whether to add the order note.
		 * @param string   $email_id The email type ID.
		 * @param WC_Email $email    The WC_Email instance.
		 * @param WC_Order $order    The order the note would be added to.
		 */
		if ( ! apply_filters( 'woocommerce_email_log_add_order_note', true, $email_id, $email, $wc_object ) ) {
			return;
		}

		$email_title = $email->get_title();
		$email_label = '' !== $email_title ? $email_title : $email_id;

		if ( $success ) {
			$note = sprintf(
				/* translators: %s: Email title or type identifier */
				__( 'Email "%s" sent.', 'woocommerce' ),
				$email_label
			);
		} elseif ( $error_reason ) {
			$note = sprintf(
				/* translators: 1: Email title or type identifier, 2: Error reason */
				__( 'Email "%1$s" failed to send: %2$s.', 'woocommerce' ),
				$email_label,
				$this->redact_emails( $error_reason )
			);
		} else {
			$note = sprintf(
				/* translators: %s: Email title or type identifier */
				__( 'Email "%s" failed to send.', 'woocommerce' ),
				$email_label
			);
		}

		$wc_object->add_order_note( $note, 0, false, array( 'note_group' => OrderNoteGroup::EMAIL_NOTIFICATION ) );
	}

	/**
	 * Handle the woocommerce_email_disabled action.
	 *
	 * @param string   $email_id The email type ID (e.g. `customer_processing_order`).
	 * @param WC_Email $email    The WC_Email instance.
	 * @return void
	 */
	public function handle_woocommerce_email_disabled( string $email_id, WC_Email $email ): void {
		$this->log_non_send_outcome( $email_id, $email, 'disabled' );
	}

	/**
	 * Handle the woocommerce_email_skipped action.
	 *
	 * @param string   $reason   Short identifier for why the email was skipped (e.g. 'no_recipient').
	 * @param string   $email_id The email type ID (e.g. `new_order`).
	 * @param WC_Email $email    The WC_Email instance.
	 * @return void
	 */
	public function handle_woocommerce_email_skipped( string $reason, string $email_id, WC_Email $email ): void {
		$this->log_non_send_outcome( $email_id, $email, 'skipped', $reason );
	}

	/**
	 * Write a log entry for an email that was not sent (disabled or skipped).
	 *
	 * Centralises the shared logic for disabled and skipped outcomes so that the context
	 * schema (`source`, `email_type`, `status`, `reason`, `recipient`, object key) is
	 * defined in exactly one place. Future additions (e.g. a `correlation_id` field) only
	 * need to be made here.
	 *
	 * @param string      $email_id The email type ID.
	 * @param WC_Email    $email    The WC_Email instance.
	 * @param string      $status   The outcome status: 'disabled' or 'skipped'.
	 * @param string|null $reason   Optional reason identifier (only set for 'skipped' status).
	 * @return void
	 */
	private function log_non_send_outcome( string $email_id, WC_Email $email, string $status, ?string $reason = null ): void {
		/**
		 * Filter whether to log this transactional email attempt.
		 *
		 * This filter is documented in src/Internal/Email/EmailLogger.php
		 *
		 * @since 10.9.0
		 */
		if ( ! apply_filters( 'woocommerce_email_log_enabled', true, $email_id, $email ) ) {
			return;
		}

		$object_context = $this->get_object_context( $email->object );
		$object_label   = isset( $object_context['type'], $object_context['id'] )
			? sprintf( ' for %s #%d', $object_context['type'], $object_context['id'] )
			: '';

		if ( 'disabled' === $status ) {
			$message = sprintf( 'Email "%s"%s not sent: email type is disabled', $email_id, $object_label );
		} else {
			$message = sprintf( 'Email "%s"%s not sent: %s', $email_id, $object_label, $reason );
		}

		$context = array(
			'source'     => self::LOG_SOURCE,
			'email_type' => $email_id,
			'status'     => $status,
			'recipient'  => $this->resolve_recipient( $email->get_recipient() ),
		);

		if ( null !== $reason ) {
			$context['reason'] = $reason;
		}

		if ( ! empty( $object_context ) ) {
			$context[ $object_context['type'] ] = $object_context['id'] ?? null;
		}

		/**
		 * Filter the context array logged for each transactional email attempt.
		 *
		 * This filter is documented in src/Internal/Email/EmailLogger.php
		 *
		 * @since 10.9.0
		 */
		$context = (array) apply_filters( 'woocommerce_email_log_context', $context, $email_id, $email );

		wc_get_logger()->log( WC_Log_Levels::NOTICE, $message, $context );
	}

	/**
	 * Resolve a recipient email string to an identifier safe for logging.
	 *
	 * Each address is mapped to the corresponding WordPress username when an account
	 * exists, or to the string 'guest' for addresses with no associated account.
	 * This avoids storing plain email addresses in logs while still giving support
	 * teams a useful identifier for troubleshooting.
	 *
	 * @param string $recipient Comma-separated recipient email string from WC_Email::get_recipient().
	 * @return string Comma-separated usernames or 'guest' labels.
	 */
	private function resolve_recipient( string $recipient ): string {
		if ( '' === $recipient ) {
			return 'guest';
		}

		$labels = array_map(
			function ( string $email ): string {
				$user = get_user_by( 'email', trim( $email ) );
				return $user instanceof WP_User ? $user->user_login : 'guest';
			},
			explode( ',', $recipient )
		);

		return implode( ', ', $labels );
	}

	/**
	 * Replace any email addresses in a log message fragment with `[redacted_email]`.
	 *
	 * PHPMailer / SMTP error strings frequently embed the recipient address
	 * (e.g. "SMTP Error: Could not send to foo@example.com"). Without redaction,
	 * the address would be written into the log message and — when the database
	 * log handler is active — surface in WC > Status > Logs to anyone with
	 * `manage_woocommerce`, defeating the username/`guest` resolution applied
	 * to the `recipient` context field.
	 *
	 * Mirrors the regex used by RemoteLogger::redact_user_data() so the privacy
	 * posture stays consistent across loggers.
	 *
	 * @param string $message The message fragment to scrub.
	 * @return string The fragment with any email addresses replaced.
	 */
	private function redact_emails( string $message ): string {
		return (string) preg_replace( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[redacted_email]', $message );
	}

	/**
	 * Extract loggable context from the WooCommerce object attached to the email.
	 *
	 * Returns a stable short type identifier rather than the raw class name so that log aggregation
	 * is not brittle across subclasses (e.g. WC_Order_Refund still returns type 'order').
	 *
	 * @param mixed $wc_object The email's related object (WC_Order, WC_Product, WP_User, etc.) or false/null.
	 * @return array{type: string, id?: int}|array{} Type and (when resolvable) ID of the object, or empty when no object is set.
	 */
	private function get_object_context( $wc_object ): array {
		if ( ! is_object( $wc_object ) ) {
			return array();
		}

		if ( $wc_object instanceof WC_Order ) {
			$type = 'order';
		} elseif ( $wc_object instanceof WC_Product ) {
			$type = 'product';
		} elseif ( $wc_object instanceof WP_User ) {
			$type = 'user';
		} else {
			$type = get_class( $wc_object );
		}

		$id = null;
		if ( $wc_object instanceof WC_Order || $wc_object instanceof WC_Product ) {
			// Both have an explicit get_id() — safe to call directly.
			$id = (int) $wc_object->get_id();
		} elseif ( $wc_object instanceof WP_User ) {
			// WP_User has no get_id() method; __call() returns false for unknown methods,
			// which casts to 0 and bypasses the ID-property fallback below.
			$id = (int) $wc_object->ID;
		} elseif ( method_exists( $wc_object, 'get_id' ) ) {
			try {
				$method = new \ReflectionMethod( $wc_object, 'get_id' );
				if ( 0 === $method->getNumberOfRequiredParameters() ) {
					$id = (int) $wc_object->get_id();
				}
			} catch ( \Throwable $e ) {
				$id = null;
			}
		}

		if ( null === $id ) {
			$public_props = get_object_vars( $wc_object );
			if ( array_key_exists( 'ID', $public_props ) ) {
				$id = (int) $public_props['ID'];
			}
		}

		if ( null === $id ) {
			return array( 'type' => $type );
		}

		return array(
			'type' => $type,
			'id'   => $id,
		);
	}
}
